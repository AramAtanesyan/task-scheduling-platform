<?php

namespace App\Jobs;

use App\Models\Task;
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

    /**
     * @var int
     */
    private $taskId;

    /**
     * @var int
     */
    private $lockId;

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
     * @param int $taskId
     * @param int $lockId
     * @return void
     */
    public function __construct(int $taskId, int $lockId)
    {
        $this->taskId = $taskId;
        $this->lockId = $lockId;
    }

    /**
     * Execute the job.
     *
     * @param AvailabilityLockService $lockService
     * @return void
     */
    public function handle(AvailabilityLockService $lockService)
    {
        $task = Task::findOrFail($this->taskId);

        try {
            $task->availability()->delete();

            $task->availability()->create([
                'user_id' => $task->user_id,
                'start_date' => $task->start_date,
                'end_date' => $task->end_date,
            ]);

        } catch (\Exception $e) {
            Log::error("Failed to update user availability for Task ID: {$task->id}, User ID: {$task->user_id}. Error: {$e->getMessage()}");
            throw $e;
        } finally {
            $lockService->releaseLockById($this->lockId);
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
        Log::error("UpdateUserAvailabilityJob failed permanently for Task ID: {$this->taskId}. Error: {$exception->getMessage()}");

        $lockService = app(AvailabilityLockService::class);
        $lockService->releaseLockById($this->lockId);
    }
}
