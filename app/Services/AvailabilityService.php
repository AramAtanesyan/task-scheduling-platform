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
            ];
        }

        $task = $overlappingAvailability->task;
        $startFormatted = Carbon::parse($overlappingAvailability->start_date)->format('M d, Y');
        $endFormatted = Carbon::parse($overlappingAvailability->end_date)->format('M d, Y');

        return [
            'available' => false,
            'message' => "During the period from {$startFormatted} to {$endFormatted}, the user is already working on the \"{$task->title}\" task. Please choose another available time slot.",
        ];
    }

    /**
     * @param int      $userId
     * @param string   $startDate
     * @param string   $endDate
     * @param int|null $excludeTaskId
     * @return UserAvailability|null
     */
    private function getOverlappingTask(int $userId, string $startDate, string $endDate, ?int $excludeTaskId = null): ?UserAvailability
    {
        return $this->availabilityRepository->findOverlapping($userId, $startDate, $endDate, $excludeTaskId);
    }
}

