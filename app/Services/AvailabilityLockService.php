<?php

namespace App\Services;

use App\Models\UserAvailabilityLock;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class AvailabilityLockService
{
    /**
     * Acquire a lock for a user.
     *
     * @param int $userId
     * @return UserAvailabilityLock|null
     */
    public function acquireLock(int $userId): ?UserAvailabilityLock
    {
        try {
            return UserAvailabilityLock::create([
                'user_id' => $userId,
                'locked_at' => Carbon::now(),
            ]);
        } catch (\Exception $e) {
            Log::error("Error while trying to acquire a lock for user", [
                'user_id' => $userId,
                'message' => $e->getMessage(),
            ]);
            return null;
        }
    }

    /**
     * Release a lock by its ID.
     * This is more reliable when you have the exact lock instance.
     *
     * @param int $lockId
     * @return bool
     */
    public function releaseLockById(int $lockId): bool
    {
        return UserAvailabilityLock::where('id', $lockId)->delete() > 0;
    }


    /**
     * Wait for a user's lock to be released.
     * Checks up to 3 times with 1 second sleep between checks.
     * @param int $userId
     * @return bool True if lock was released, false if still locked after 3 attempts
     */
    public function waitForUnlock(int $userId): bool
    {
        $maxAttempts = 3;

        for ($attempt = 0; $attempt < $maxAttempts; $attempt++) {
            $isLocked = $this->isLocked($userId);

            if (!$isLocked) {
                return true;
            }

            $isLastTimeChecking = $attempt === ($maxAttempts - 1);

            if (!$isLastTimeChecking) {
                sleep(1);
            }
        }

        return false;
    }

    /**
     * @return int
     */
    public function clearStaleLocks(): int
    {
        $fiveMinutesAgo = Carbon::now()->subMinutes(5);

        return UserAvailabilityLock::where('locked_at', '<', $fiveMinutesAgo)
                                   ->delete();
    }


    /**
     * @param int $daysOld
     * @return int
     */
    public function deleteOldLocks(int $daysOld = 7): int
    {
        $cutoffDate = Carbon::now()->subDays($daysOld);

        return UserAvailabilityLock::where('created_at', '<', $cutoffDate)
                                   ->delete();
    }

    /**
     * Check if a user has any pending availability updates.
     *
     * @param int $userId
     * @return bool
     */
    private function isLocked(int $userId): bool
    {
        return UserAvailabilityLock::where('user_id', $userId)
                                   ->exists();
    }
}

