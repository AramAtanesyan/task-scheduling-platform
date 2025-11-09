<?php

namespace App\Http\Controllers;

use App\Models\Task;
use App\Services\AvailabilityService;
use App\Services\AvailabilityLockService;
use App\Http\Requests\StoreTaskRequest;
use App\Http\Requests\UpdateTaskRequest;
use App\Http\Traits\ApiResponseTrait;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Jobs\UpdateUserAvailabilityJob;
use App\Notifications\TaskAssignedNotification;
use App\Notifications\TaskReassignedNotification;
use App\Notifications\TaskDeletedNotification;

class TaskController extends Controller
{
    use ApiResponseTrait;

    protected $availabilityService;
    protected $lockService;

    public function __construct(
        AvailabilityService $availabilityService,
        AvailabilityLockService $lockService
    ) {
        $this->availabilityService = $availabilityService;
        $this->lockService = $lockService;
    }

    /**
     * Display a listing of the resource.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function index(Request $request)
    {
        $query = Task::with(['user', 'status']);

        // Search by title or description
        if ($request->has('search') && $request->search) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%");
            });
        }

        // Filter by status
        if ($request->has('status_id') && $request->status_id) {
            $query->where('status_id', $request->status_id);
        }

        // Filter by assignee
        if ($request->has('user_id') && $request->user_id) {
            $query->where('user_id', $request->user_id);
        }

        // Filter by due date
        if ($request->has('due_date_filter') && $request->due_date_filter) {
            $now = now();
            switch ($request->due_date_filter) {
                case 'overdue':
                    $query->where('end_date', '<', $now);
                    break;
                case 'today':
                    $query->whereDate('end_date', $now);
                    break;
                case 'this_week':
                    $query->whereBetween('end_date', [$now->startOfWeek(), $now->copy()->endOfWeek()]);
                    break;
                case 'this_month':
                    $query->whereBetween('end_date', [$now->startOfMonth(), $now->copy()->endOfMonth()]);
                    break;
            }
        }

        $tasks = $query->orderBy('created_at', 'desc')->get();

        return response()->json([
            'success' => true,
            'data' => $tasks
        ]);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param StoreTaskRequest $request
     * @return JsonResponse
     */
    public function store(StoreTaskRequest $request)
    {
        // Wait for user lock to be released (if locked)
        if (!$this->lockService->waitForUnlock($request->user_id)) {
            Log::warning("Lock timeout for user availability update", [
                'user_id' => $request->user_id,
                'action' => 'store_task'
            ]);
            return $this->errorResponse(
                "The system is processing a previous request. Please try again in a moment.",
                null,
                422
            );
        }

        // Validate user availability
        $availabilityCheck = $this->availabilityService->validateAvailability(
            $request->user_id,
            $request->start_date,
            $request->end_date
        );

        if (!$availabilityCheck['available']) {
            return $this->errorResponse(
                $availabilityCheck['message']
            );
        }

        DB::beginTransaction();
        try {
            $task = Task::create($request->validated());

            // Acquire lock before dispatching job
            $lock = $this->lockService->acquireLock($request->user_id);

            if (!$lock) {
                throw new \Exception('Failed to acquire availability lock');
            }

            UpdateUserAvailabilityJob::dispatch($task->id, $lock->id);

            $task->user->notify(new TaskAssignedNotification($task->id));

            DB::commit();

            $task->load(['user', 'status']);

            return $this->successResponse($task, 'Task created successfully', 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->serverErrorResponse('Error creating task: ' . $e->getMessage());
        }
    }

    /**
     * Update the specified resource in storage.
     *
     * @param UpdateTaskRequest $request
     * @param int $id
     * @return JsonResponse
     */
    public function update(UpdateTaskRequest $request, Task $task)
    {
        $data = $request->validated();

        if ($errorResponse = $this->validateUpdatingOnlyStatus($data)) {
            return $errorResponse;
        }

        // Check for overlapping tasks if user or dates are being changed
        if ($request->has('user_id') || $request->has('start_date') || $request->has('end_date')) {
            $userId = $request->user_id ?? $task->user_id;
            $startDate = $request->start_date ?? $task->start_date;
            $endDate = $request->end_date ?? $task->end_date;

            // Wait for user lock to be released (if locked)
            if (!$this->lockService->waitForUnlock($userId)) {
                Log::warning("Lock timeout for user availability update", [
                    'user_id' => $userId,
                    'task_id' => $task->id,
                    'action' => 'update_task'
                ]);
                return $this->errorResponse(
                    "The system is processing a previous request. Please try again in a moment.",
                    null,
                    422
                );
            }

            // Validate user availability
            $availabilityCheck = $this->availabilityService->validateAvailability(
                $userId,
                $startDate,
                $endDate,
                $task->id
            );

            if (!$availabilityCheck['available']) {
                return $this->errorResponse(
                    $availabilityCheck['message'],
                    ['overlapping_task' => $availabilityCheck['overlapping_task']],
                    422
                );
            }
        }

        DB::beginTransaction();
        try {
            $userIdChanged = $request->has('user_id') && $request->user_id != $task->user_id;
            $datesChanged = ($request->has('start_date') && $request->start_date != $task->start_date->format('Y-m-d'))
                            ||
                            ($request->has('end_date') && $request->end_date != $task->end_date->format('Y-m-d'));

            $previousUser = $userIdChanged ? $task->user : null;

            $task->update($data);

            if ($userIdChanged || $datesChanged) {
                $task->refresh();

                $lock = $this->lockService->acquireLock($task->user_id);

                if (!$lock) {
                    throw new \Exception('Failed to acquire availability lock');
                }

                UpdateUserAvailabilityJob::dispatch($task->id, $lock->id);

                if ($userIdChanged) {
                    $task->user->notify(new TaskReassignedNotification($task->id, $previousUser->id));
                }
            }

            DB::commit();

            $task->load(['user', 'status']);

            return $this->successResponse($task, 'Task updated successfully');
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->serverErrorResponse('Error updating task: ' . $e->getMessage());
        }
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param int $id
     * @return JsonResponse
     */
    public function destroy($id)
    {
        $task = Task::findOrFail($id);

        DB::beginTransaction();
        try {
            $task->availability()->delete();

            $task->user->notify(new TaskDeletedNotification($task->id));

            $task->delete();

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Task deleted successfully'
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Error deleting task',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * @param array $data
     * @return JsonResponse|null
     */
    private function validateUpdatingOnlyStatus(array $data): ?JsonResponse
    {
        if (auth()->user()->isAdmin()) {
            return null;
        }

        $allowedFields = ['status_id'];

        $attemptsToUpdateOtherFields = collect($data)
            ->keys()
            ->contains(function($field) use ($allowedFields) {
                return !in_array($field, $allowedFields);
            });

        if (!$attemptsToUpdateOtherFields) {
            return null;
        }

        return $this->errorResponse(
            'Unauthorized. Regular users can only update the task status.',
            null,
            403
        );
    }

}
