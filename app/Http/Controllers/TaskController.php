<?php

namespace App\Http\Controllers;

use App\Models\Task;
use App\Services\AvailabilityService;
use App\Services\AvailabilityLockService;
use App\Http\Requests\StoreTaskRequest;
use App\Http\Requests\UpdateTaskRequest;
use App\Http\Traits\ApiResponseTrait;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Jobs\UpdateUserAvailabilityJob;
use App\Notifications\TaskAssignedNotification;
use App\Notifications\TaskReassignedNotification;

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
     * @return \Illuminate\Http\JsonResponse
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
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(StoreTaskRequest $request)
    {
        // Only admins can create tasks
        if (!auth()->user()->isAdmin()) {
            return $this->errorResponse(
                'Unauthorized. Only administrators can create tasks.',
                null,
                403
            );
        }

        // Check if user is currently locked (availability being processed)
        if ($this->lockService->isLocked($request->user_id)) {
            return $this->errorResponse(
                "This user's availability is currently being updated. Please wait a moment and try again.",
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
                $availabilityCheck['message'],
                ['overlapping_task' => $availabilityCheck['overlapping_task']],
                422
            );
        }

        DB::beginTransaction();
        try {
            // Create the task
            $task = Task::create($request->validated());

            // Acquire lock before dispatching job
            $lock = $this->lockService->acquireLock($request->user_id, $task->id);

            if (!$lock) {
                throw new \Exception('Failed to acquire availability lock');
            }

            // Dispatch job to update availability asynchronously
            UpdateUserAvailabilityJob::dispatch($task);

            // Send notification to assigned user
            $task->user->notify(new TaskAssignedNotification($task));

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
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(UpdateTaskRequest $request, $id)
    {
        $task = Task::findOrFail($id);

        // Check if user is trying to update non-status fields
        $nonStatusFields = collect($request->validated())
            ->except(['status_id'])
            ->isNotEmpty();

        // Regular users can only update status
        if (!auth()->user()->isAdmin() && $nonStatusFields) {
            return $this->errorResponse(
                'Unauthorized. Regular users can only update task status.',
                null,
                403
            );
        }

        // Check for overlapping tasks if user or dates are being changed
        if ($request->has('user_id') || $request->has('start_date') || $request->has('end_date')) {
            $userId = $request->user_id ?? $task->user_id;
            $startDate = $request->start_date ?? $task->start_date;
            $endDate = $request->end_date ?? $task->end_date;

            // Check if user is currently locked
            if ($this->lockService->isLocked($userId)) {
                return $this->errorResponse(
                    "This user's availability is currently being updated. Please wait a moment and try again.",
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
            // Check if user is changing
            $userIdChanged = $request->has('user_id') && $request->user_id != $task->user_id;
            $datesChanged = $request->has('start_date') || $request->has('end_date');

            // Store previous user for notification if user is being reassigned
            $previousUser = $userIdChanged ? $task->user : null;

            $task->update($request->validated());

            // Only acquire lock and update availability if dates or user changed
            if ($userIdChanged || $datesChanged) {
                $task->refresh(); // Reload task with updated data

                // If a lock already exists for this task, release it first
                // This can happen if we're updating dates but not user
                $existingLock = \App\Models\UserAvailabilityLock::where('task_id', $task->id)
                    ->where('is_processing', true)
                    ->first();

                if ($existingLock) {
                    $this->lockService->releaseLock($existingLock->user_id, $task->id);
                }

                // Acquire new lock for the current user
                $lock = $this->lockService->acquireLock($task->user_id, $task->id);

                if (!$lock) {
                    throw new \Exception('Failed to acquire availability lock');
                }

                // Dispatch job to update availability asynchronously
                UpdateUserAvailabilityJob::dispatch($task);

                // Send notification if user was reassigned
                if ($userIdChanged && $previousUser) {
                    $task->user->notify(new TaskReassignedNotification($task, $previousUser));
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
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy($id)
    {
        // Only admins can delete tasks
        if (!auth()->user()->isAdmin()) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized. Only administrators can delete tasks.'
            ], 403);
        }

        $task = Task::findOrFail($id);

        DB::beginTransaction();
        try {
            // Delete availability record
            $task->availability()->delete();

            // Delete task
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

}
