<?php

namespace App\Http\Controllers;

use App\Models\Task;
use App\Services\AvailabilityService;
use App\Services\AvailabilityLockService;
use App\Services\FilterService;
use App\Http\Requests\StoreTaskRequest;
use App\Http\Requests\UpdateTaskRequest;
use App\Http\Traits\ApiResponseTrait;
use App\Rules\ValidDueDateFilter;
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
    protected $filterService;

    public function __construct(
        AvailabilityService $availabilityService,
        AvailabilityLockService $lockService,
        FilterService $filterService
    ) {
        $this->availabilityService = $availabilityService;
        $this->lockService = $lockService;
        $this->filterService = $filterService;
    }

    /**
     * Display a listing of the resource.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function index(Request $request)
    {
        $request->validate([
            'search' => 'nullable|string|max:255',
            'status_id' => 'nullable|integer|exists:task_statuses,id',
            'user_id' => 'nullable|integer|exists:users,id',
            'due_date_filter' => ['nullable', 'string', new ValidDueDateFilter()],
        ]);

        $query = Task::with(['user', 'status']);

        // Search by title or description
        if ($request->has('search') && $request->search) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%");
            });
        }

        if ($request->has('status_id') && $request->status_id) {
            $query->where('status_id', $request->status_id);
        }

        if ($request->has('user_id') && $request->user_id) {
            $query->where('user_id', $request->user_id);
        }

        if ($request->has('due_date_filter') && $request->due_date_filter) {
            $query = $this->filterService->applyDueDateFilter($query, $request->due_date_filter);
        }

        $tasks = $query->orderByDesc('id')->get();

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

        if ($errorResponse = $this->validateNonAdminUpdate($task, $data)) {
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
     * Validate that non-admin users can only update their own tasks and only the status field.
     *
     * @param Task $task
     * @param array $data
     * @return JsonResponse|null
     */
    private function validateNonAdminUpdate(Task $task, array $data): ?JsonResponse
    {
        // Admins can update any task with any fields
        if (auth()->user()->isAdmin()) {
            return null;
        }

        // Check if user owns the task
        if ($task->user_id !== auth()->user()->id) {
            return $this->errorResponse(
                'Unauthorized. You can only update your own tasks.',
                null,
                403
            );
        }

        // Check if user is trying to update fields other than status_id
        $allowedFields = ['status_id'];

        $attemptsToUpdateOtherFields = collect($data)
            ->keys()
            ->contains(function($field) use ($allowedFields) {
                return !in_array($field, $allowedFields);
            });

        if ($attemptsToUpdateOtherFields) {
            return $this->errorResponse(
                'Unauthorized. Regular users can only update the task status.',
                null,
                403
            );
        }

        return null;
    }

}
