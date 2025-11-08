<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Task extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'title',
        'description',
        'start_date',
        'end_date',
        'status_id',
        'user_id',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
    ];

    /**
     * Get the user that owns the task.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the status of the task.
     */
    public function status()
    {
        return $this->belongsTo(TaskStatus::class);
    }

    /**
     * Get the availability record for this task.
     */
    public function availability()
    {
        return $this->hasOne(UserAvailability::class);
    }
}
