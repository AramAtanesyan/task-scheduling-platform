<?php

namespace App\Jobs;

use App\Models\Task;
use App\Repositories\UserAvailabilityRepository;
use App\Services\AvailabilityLockService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class UpdateUserAvailabilityJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $task;

    /**
     * The number of times the job may be attempted.
     *
     * @var int
     */
    public $tries = 3;

    /**
     * The number of seconds to wait before retrying the job.
     *
     * @var int
     */
    public $backoff = 3;

    /**
     * Create a new job instance.
     *
     * @param Task $task
     * @return void
     */
    public function __construct(Task $task)
    {
        $this->task = $task;
    }

    /**
     * Execute the job.
     *
     * @param AvailabilityLockService $lockService
     * @param UserAvailabilityRepository $availabilityRepository
     * @return void
     */
    public function handle(AvailabilityLockService $lockService, UserAvailabilityRepository $availabilityRepository)
    {
        try {
            // Delete any existing availability records for this task
            // This handles both updates and reassignments
            $availabilityRepository->deleteByTask($this->task->id);

            // Create new availability record for the current user
            $availabilityRepository->create([
                'user_id' => $this->task->user_id,
                'task_id' => $this->task->id,
                'start_date' => $this->task->start_date,
                'end_date' => $this->task->end_date,
            ]);

            // Release the lock after successful update
            $lockService->releaseLock($this->task->user_id, $this->task->id);

            Log::info("User availability updated successfully for Task ID: {$this->task->id}, User ID: {$this->task->user_id}");
        } catch (\Exception $e) {
            Log::error("Failed to update user availability for Task ID: {$this->task->id}, User ID: {$this->task->user_id}. Error: {$e->getMessage()}");
            
            // Release the lock even on failure to prevent deadlock
            $lockService->releaseLock($this->task->user_id, $this->task->id);
            
            throw $e;
        }
    }

    /**
     * Handle a job failure.
     *
     * @param \Throwable $exception
     * @return void
     */
    public function failed(\Throwable $exception)
    {
        Log::error("UpdateUserAvailabilityJob failed permanently for Task ID: {$this->task->id}. Error: {$exception->getMessage()}");
        
        // Release the lock on permanent failure
        $lockService = app(AvailabilityLockService::class);
        $lockService->releaseLock($this->task->user_id, $this->task->id);
    }
}
