<?php

namespace App\Services;

use App\Models\UserAvailability;
use Carbon\Carbon;

class AvailabilityService
{
    /**
     * Check if a user has overlapping tasks in the given date range.
     *
     * @param int $userId
     * @param string $startDate
     * @param string $endDate
     * @param int|null $excludeTaskId
     * @return bool
     */
    public function hasOverlappingTask(int $userId, string $startDate, string $endDate, ?int $excludeTaskId = null): bool
    {
        $start = Carbon::parse($startDate);
        $end = Carbon::parse($endDate);

        $query = UserAvailability::where('user_id', $userId)
            ->where(function ($q) use ($start, $end) {
                $q->where(function ($q2) use ($start, $end) {
                    // Check if existing task overlaps with new date range
                    // Overlap occurs when: start_date <= new_end_date AND end_date >= new_start_date
                    $q2->where('start_date', '<=', $end)
                       ->where('end_date', '>=', $start);
                });
            });

        if ($excludeTaskId) {
            $query->where('task_id', '!=', $excludeTaskId);
        }

        return $query->exists();
    }

    /**
     * Get overlapping task for a user.
     *
     * @param int $userId
     * @param string $startDate
     * @param string $endDate
     * @param int|null $excludeTaskId
     * @return UserAvailability|null
     */
    public function getOverlappingTask(int $userId, string $startDate, string $endDate, ?int $excludeTaskId = null): ?UserAvailability
    {
        $start = Carbon::parse($startDate);
        $end = Carbon::parse($endDate);

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
}

