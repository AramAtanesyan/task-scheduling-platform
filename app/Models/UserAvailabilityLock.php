<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserAvailabilityLock extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'locked_at',
    ];

    protected $casts = [
        'locked_at' => 'datetime',
    ];

    /**
     * Get the user that owns the lock.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
