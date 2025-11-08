<?php

namespace App\Console\Commands;

use App\Services\AvailabilityLockService;
use Illuminate\Console\Command;

class CleanupStaleLocksCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'locks:cleanup';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Clean up stale availability locks that are older than 5 minutes';

    protected $lockService;

    /**
     * Create a new command instance.
     *
     * @param AvailabilityLockService $lockService
     * @return void
     */
    public function __construct(AvailabilityLockService $lockService)
    {
        parent::__construct();
        $this->lockService = $lockService;
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $this->info('Cleaning up stale availability locks...');

        $clearedCount = $this->lockService->clearStaleLocks();

        if ($clearedCount > 0) {
            $this->info("Cleared {$clearedCount} stale lock(s).");
        } else {
            $this->info('No stale locks found.');
        }

        // Also delete any old locks (older than 7 days) - edge cases
        $deletedCount = $this->lockService->deleteOldLocks(7);

        if ($deletedCount > 0) {
            $this->info("Deleted {$deletedCount} old lock record(s).");
        }

        return 0;
    }
}
