<?php

namespace App\Services;

use App\Models\UserAvailability;
use App\Repositories\UserAvailabilityRepository;
use Carbon\Carbon;

class AvailabilityService
{
    protected $availabilityRepository;

    public function __construct(UserAvailabilityRepository $availabilityRepository)
    {
        $this->availabilityRepository = $availabilityRepository;
    }

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
        return $this->availabilityRepository->hasOverlapping($userId, $startDate, $endDate, $excludeTaskId);
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
        return $this->availabilityRepository->findOverlapping($userId, $startDate, $endDate, $excludeTaskId);
    }

    /**
     * Validate user availability and return detailed information.
     *
     * @param int $userId
     * @param string $startDate
     * @param string $endDate
     * @param int|null $excludeTaskId
     * @return array
     */
    public function validateAvailability(int $userId, string $startDate, string $endDate, ?int $excludeTaskId = null): array
    {
        $overlappingAvailability = $this->getOverlappingTask($userId, $startDate, $endDate, $excludeTaskId);

        if (!$overlappingAvailability) {
            return [
                'available' => true,
                'message' => 'User is available during this period.',
            ];
        }

        $task = $overlappingAvailability->task;
        $startFormatted = Carbon::parse($overlappingAvailability->start_date)->format('M d, Y');
        $endFormatted = Carbon::parse($overlappingAvailability->end_date)->format('M d, Y');

        return [
            'available' => false,
            'message' => "User is unavailable during this period. They have an overlapping task: \"{$task->title}\" ({$startFormatted} - {$endFormatted})",
            'overlapping_task' => [
                'id' => $task->id,
                'title' => $task->title,
                'start_date' => $overlappingAvailability->start_date,
                'end_date' => $overlappingAvailability->end_date,
            ],
        ];
    }

    /**
     * Get all availability records for a user.
     *
     * @param int $userId
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getUserAvailability(int $userId)
    {
        return $this->availabilityRepository->getByUser($userId);
    }
}

