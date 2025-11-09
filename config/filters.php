<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Task Due Date Filters Configuration
    |--------------------------------------------------------------------------
    |
    | This configuration defines all available due date filters for tasks.
    | Each filter contains:
    | - label: Display name for the frontend
    | - value: The value sent from/to frontend
    | - description: Optional description for documentation
    | - query_builder: Closure that applies the filter logic to the query
    |
    */
    
    'due_date' => [
        [
            'label' => 'Overdue',
            'value' => 'overdue',
            'description' => 'Tasks with end date before current time',
            'query_builder' => function ($query, $now) {
                return $query->where('end_date', '<', $now);
            },
        ],
        [
            'label' => 'Due Today',
            'value' => 'today',
            'description' => 'Tasks with end date today',
            'query_builder' => function ($query, $now) {
                return $query->whereDate('end_date', $now);
            },
        ],
        [
            'label' => 'This Week',
            'value' => 'this_week',
            'description' => 'Tasks with end date in the current week',
            'query_builder' => function ($query, $now) {
                return $query->whereBetween('end_date', [
                    $now->copy()->startOfWeek(),
                    $now->copy()->endOfWeek()
                ]);
            },
        ],
        [
            'label' => 'This Month',
            'value' => 'this_month',
            'description' => 'Tasks with end date in the current month',
            'query_builder' => function ($query, $now) {
                return $query->whereBetween('end_date', [
                    $now->copy()->startOfMonth(),
                    $now->copy()->endOfMonth()
                ]);
            },
        ],
        [
            'label' => 'Next 7 Days',
            'value' => 'next_7_days',
            'description' => 'Tasks with end date in the next 7 days',
            'query_builder' => function ($query, $now) {
                return $query->whereBetween('end_date', [
                    $now,
                    $now->copy()->addDays(7)
                ]);
            },
        ],
        [
            'label' => 'Next 30 Days',
            'value' => 'next_30_days',
            'description' => 'Tasks with end date in the next 30 days',
            'query_builder' => function ($query, $now) {
                return $query->whereBetween('end_date', [
                    $now,
                    $now->copy()->addDays(30)
                ]);
            },
        ],
    ],

];

