<?php

namespace App\Http\Controllers;

use App\Models\Task;
use App\Services\AvailabilityService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use App\Jobs\UpdateUserAvailabilityJob;

class TaskController extends Controller
{
    protected $availabilityService;

    public function __construct(AvailabilityService $availabilityService)
    {
        $this->availabilityService = $availabilityService;
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

        $tasks = $query->orderBy('created_at', 'desc')->get();

        return response()->json($tasks);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'user_id' => 'required|exists:users,id',
            'status_id' => 'required|exists:task_statuses,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 422);
        }

        // Check for overlapping tasks
        if ($this->availabilityService->hasOverlappingTask(
            $request->user_id,
            $request->start_date,
            $request->end_date
        )) {
            $overlappingTask = $this->availabilityService->getOverlappingTask(
                $request->user_id,
                $request->start_date,
                $request->end_date
            );

            return response()->json([
                'message' => 'User already has an overlapping task during this period',
                'overlapping_task' => $overlappingTask->task
            ], 422);
        }

        DB::beginTransaction();
        try {
            $task = Task::create($request->all());

            // Dispatch job to update availability asynchronously
            UpdateUserAvailabilityJob::dispatch($task);

            DB::commit();

            $task->load(['user', 'status']);

            return response()->json($task, 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Error creating task',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display the specified resource.
     *
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function show($id)
    {
        $task = Task::with(['user', 'status'])->findOrFail($id);
        return response()->json($task);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param Request $request
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(Request $request, $id)
    {
        $task = Task::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'title' => 'sometimes|required|string|max:255',
            'description' => 'nullable|string',
            'start_date' => 'sometimes|required|date',
            'end_date' => 'sometimes|required|date|after_or_equal:start_date',
            'user_id' => 'sometimes|required|exists:users,id',
            'status_id' => 'sometimes|required|exists:task_statuses,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 422);
        }

        // Check for overlapping tasks if user or dates are being changed
        if ($request->has('user_id') || $request->has('start_date') || $request->has('end_date')) {
            $userId = $request->user_id ?? $task->user_id;
            $startDate = $request->start_date ?? $task->start_date;
            $endDate = $request->end_date ?? $task->end_date;

            if ($this->availabilityService->hasOverlappingTask(
                $userId,
                $startDate,
                $endDate,
                $task->id
            )) {
                $overlappingTask = $this->availabilityService->getOverlappingTask(
                    $userId,
                    $startDate,
                    $endDate,
                    $task->id
                );

                return response()->json([
                    'message' => 'User already has an overlapping task during this period',
                    'overlapping_task' => $overlappingTask->task
                ], 422);
            }
        }

        DB::beginTransaction();
        try {
            $task->update($request->all());

            // Dispatch job to update availability asynchronously
            UpdateUserAvailabilityJob::dispatch($task);

            DB::commit();

            $task->load(['user', 'status']);

            return response()->json($task);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Error updating task',
                'error' => $e->getMessage()
            ], 500);
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
        $task = Task::findOrFail($id);

        DB::beginTransaction();
        try {
            // Delete availability record
            $task->availability()->delete();

            // Delete task
            $task->delete();

            DB::commit();

            return response()->json(['message' => 'Task deleted successfully']);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Error deleting task',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Reassign a task to another user.
     *
     * @param Request $request
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function reassign(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'user_id' => 'required|exists:users,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 422);
        }

        $task = Task::findOrFail($id);

        // Check for overlapping tasks
        if ($this->availabilityService->hasOverlappingTask(
            $request->user_id,
            $task->start_date,
            $task->end_date,
            $task->id
        )) {
            $overlappingTask = $this->availabilityService->getOverlappingTask(
                $request->user_id,
                $task->start_date,
                $task->end_date,
                $task->id
            );

            return response()->json([
                'message' => 'User already has an overlapping task during this period',
                'overlapping_task' => $overlappingTask->task
            ], 422);
        }

        DB::beginTransaction();
        try {
            $task->update(['user_id' => $request->user_id]);

            // Dispatch job to update availability asynchronously
            UpdateUserAvailabilityJob::dispatch($task);

            DB::commit();

            $task->load(['user', 'status']);

            return response()->json($task);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Error reassigning task',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
