<?php

namespace App\Repositories;

use App\Models\UserAvailability;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;

class UserAvailabilityRepository
{
    /**
     * Find overlapping availability for a user within a date range.
     *
     * @param int $userId
     * @param string|\Carbon\Carbon $startDate
     * @param string|\Carbon\Carbon $endDate
     * @param int|null $excludeTaskId
     * @return UserAvailability|null
     */
    public function findOverlapping(int $userId, $startDate, $endDate, ?int $excludeTaskId = null): ?UserAvailability
    {
        $start = $startDate instanceof Carbon ? $startDate : Carbon::parse($startDate);
        $end = $endDate instanceof Carbon ? $endDate : Carbon::parse($endDate);

        $query = UserAvailability::where('user_id', $userId)
            ->where(function ($q) use ($start, $end) {
                $q->where('start_date', '<=', $end)
                   ->where('end_date', '>=', $start);
            })
            ->with('task');

        if ($excludeTaskId) {
            $query->where('task_id', '!=', $excludeTaskId);
        }

        return $query->first();
    }

    /**
     * Check if there are overlapping availability records for a user.
     *
     * @param int $userId
     * @param string|\Carbon\Carbon $startDate
     * @param string|\Carbon\Carbon $endDate
     * @param int|null $excludeTaskId
     * @return bool
     */
    public function hasOverlapping(int $userId, $startDate, $endDate, ?int $excludeTaskId = null): bool
    {
        $start = $startDate instanceof Carbon ? $startDate : Carbon::parse($startDate);
        $end = $endDate instanceof Carbon ? $endDate : Carbon::parse($endDate);

        $query = UserAvailability::where('user_id', $userId)
            ->where(function ($q) use ($start, $end) {
                $q->where('start_date', '<=', $end)
                   ->where('end_date', '>=', $start);
            });

        if ($excludeTaskId) {
            $query->where('task_id', '!=', $excludeTaskId);
        }

        return $query->exists();
    }

    /**
     * Get all availability records for a user.
     *
     * @param int $userId
     * @return Collection
     */
    public function getByUser(int $userId): Collection
    {
        return UserAvailability::where('user_id', $userId)
            ->with('task')
            ->orderBy('start_date')
            ->get();
    }

    /**
     * Create a new availability record.
     *
     * @param array $data
     * @return UserAvailability
     */
    public function create(array $data): UserAvailability
    {
        return UserAvailability::create($data);
    }

    /**
     * Delete availability records by task ID.
     *
     * @param int $taskId
     * @return int Number of records deleted
     */
    public function deleteByTask(int $taskId): int
    {
        return UserAvailability::where('task_id', $taskId)->delete();
    }

    /**
     * Delete availability records by user ID and task ID.
     *
     * @param int $userId
     * @param int $taskId
     * @return int Number of records deleted
     */
    public function deleteByUserAndTask(int $userId, int $taskId): int
    {
        return UserAvailability::where('user_id', $userId)
            ->where('task_id', $taskId)
            ->delete();
    }

    /**
     * Get availability record by task ID.
     *
     * @param int $taskId
     * @return UserAvailability|null
     */
    public function findByTask(int $taskId): ?UserAvailability
    {
        return UserAvailability::where('task_id', $taskId)->first();
    }
}

