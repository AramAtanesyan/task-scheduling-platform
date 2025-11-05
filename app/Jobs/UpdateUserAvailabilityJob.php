<?php

namespace App\Jobs;

use App\Models\Task;
use App\Models\UserAvailability;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class UpdateUserAvailabilityJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $task;

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
     * @return void
     */
    public function handle()
    {
        // Delete existing availability record for this task
        UserAvailability::where('task_id', $this->task->id)->delete();

        // Create new availability record
        UserAvailability::create([
            'user_id' => $this->task->user_id,
            'task_id' => $this->task->id,
            'start_date' => $this->task->start_date,
            'end_date' => $this->task->end_date,
        ]);
    }
}
