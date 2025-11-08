<?php

namespace App\Http\Controllers;

use App\Models\TaskStatus;
use App\Http\Requests\StoreTaskStatusRequest;
use App\Http\Requests\UpdateTaskStatusRequest;

class TaskStatusController extends Controller
{
    /**
     * Display a listing of task statuses.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function index()
    {
        $statuses = TaskStatus::orderBy('name')->get();

        return response()->json([
            'success' => true,
            'data' => $statuses
        ]);
    }

    /**
     * Store a newly created task status.
     *
     * @param StoreTaskStatusRequest $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(StoreTaskStatusRequest $request)
    {
        $data = $request->validated();

        // If is_default is true, unset other defaults
        if (!empty($data['is_default'])) {
            TaskStatus::where('is_default', true)->update(['is_default' => false]);
        }

        $status = TaskStatus::create($data);

        return response()->json([
            'success' => true,
            'data' => $status,
            'message' => 'Status created successfully'
        ], 201);
    }

    /**
     * Update the specified task status.
     *
     * @param UpdateTaskStatusRequest $request
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(UpdateTaskStatusRequest $request, TaskStatus $status)
    {
        $data = $request->validated();

        // If setting this status as default, unset other defaults
        if (!empty($data['is_default']) && $data['is_default']) {
            TaskStatus::where('id', '!=', $status->id)
                ->where('is_default', true)
                ->update(['is_default' => false]);
        }

        $status->update($data);

        return response()->json([
            'success' => true,
            'data' => $status,
            'message' => 'Status updated successfully'
        ]);
    }

    /**
     * Remove the specified task status.
     *
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy(TaskStatus $status)
    {
        // Check if status is the default
        if ($status->is_default) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot delete the default status. Please set another status as default first.'
            ], 422);
        }

        // Check if status is being used by any tasks
        if ($status->tasks()->count() > 0) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot delete status that is assigned to tasks.'
            ], 422);
        }

        $status->delete();

        return response()->json([
            'success' => true,
            'message' => 'Status deleted successfully'
        ]);
    }
}

