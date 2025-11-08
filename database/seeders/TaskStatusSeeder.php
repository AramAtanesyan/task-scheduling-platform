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
            ['name' => 'To Do', 'color' => '#3b82f6', 'is_default' => true],
            ['name' => 'In Progress', 'color' => '#f59e0b', 'is_default' => false],
            ['name' => 'Completed', 'color' => '#10b981', 'is_default' => false],
            ['name' => 'Cancelled', 'color' => '#ef4444', 'is_default' => false],
        ];

        TaskStatus::insert($statuses);
    }
}
