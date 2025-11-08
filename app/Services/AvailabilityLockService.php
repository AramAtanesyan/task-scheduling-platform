<?php

namespace App\Services;

use App\Models\UserAvailabilityLock;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class AvailabilityLockService
{
    /**
     * Acquire a lock for a user and task.
     *
     * @param int $userId
     * @param int $taskId
     * @return UserAvailabilityLock|null
     */
    public function acquireLock(int $userId, int $taskId): ?UserAvailabilityLock
    {
        try {
            return UserAvailabilityLock::create([
                'user_id' => $userId,
                'task_id' => $taskId,
                'is_processing' => true,
                'locked_at' => Carbon::now(),
            ]);
        } catch (\Exception $e) {
            // If lock already exists, return null
            return null;
        }
    }

    /**
     * Release a lock after processing is complete.
     *
     * @param int $userId
     * @param int $taskId
     * @return bool
     */
    public function releaseLock(int $userId, int $taskId): bool
    {
        // Delete the lock record instead of updating to avoid unique constraint issues
        // This allows a new lock to be acquired for the same task immediately
        return UserAvailabilityLock::where('user_id', $userId)
            ->where('task_id', $taskId)
            ->delete() > 0;
    }

    /**
     * Check if a user has any pending availability updates.
     *
     * @param int $userId
     * @return bool
     */
    public function isLocked(int $userId): bool
    {
        return UserAvailabilityLock::where('user_id', $userId)
            ->where('is_processing', true)
            ->exists();
    }

    /**
     * Get active lock for a user.
     *
     * @param int $userId
     * @return UserAvailabilityLock|null
     */
    public function getActiveLock(int $userId): ?UserAvailabilityLock
    {
        return UserAvailabilityLock::where('user_id', $userId)
            ->where('is_processing', true)
            ->with('task')
            ->first();
    }

    /**
     * Clear stale locks (older than 5 minutes).
     * This handles cases where jobs failed without properly releasing the lock.
     *
     * @return int Number of locks cleared
     */
    public function clearStaleLocks(): int
    {
        $fiveMinutesAgo = Carbon::now()->subMinutes(5);
        
        // Delete stale locks instead of updating them
        return UserAvailabilityLock::where('is_processing', true)
            ->where('locked_at', '<', $fiveMinutesAgo)
            ->delete();
    }

    /**
     * Delete old locks (cleanup).
     * Note: Since we now delete locks when released, this mainly catches any edge cases.
     *
     * @param int $daysOld
     * @return int Number of locks deleted
     */
    public function deleteOldLocks(int $daysOld = 7): int
    {
        $cutoffDate = Carbon::now()->subDays($daysOld);
        
        // Clean up any locks older than specified days (edge cases)
        return UserAvailabilityLock::where('created_at', '<', $cutoffDate)
            ->delete();
    }
}

