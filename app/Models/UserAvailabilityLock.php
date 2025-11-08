<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserAvailabilityLock extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'task_id',
        'is_processing',
        'locked_at',
        'completed_at',
    ];

    protected $casts = [
        'is_processing' => 'boolean',
        'locked_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    /**
     * Get the user that owns the lock.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the task associated with this lock.
     */
    public function task()
    {
        return $this->belongsTo(Task::class);
    }
}
