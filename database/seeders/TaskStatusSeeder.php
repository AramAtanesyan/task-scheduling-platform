<?php

namespace Database\Seeders;

use App\Models\TaskStatus;
use Illuminate\Database\Seeder;

class TaskStatusSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $statuses = [
            ['name' => 'To Do', 'color' => '#3b82f6'],
            ['name' => 'In Progress', 'color' => '#f59e0b'],
            ['name' => 'Completed', 'color' => '#10b981'],
            ['name' => 'Cancelled', 'color' => '#ef4444'],
        ];

        foreach ($statuses as $status) {
            TaskStatus::create($status);
        }
    }
}
