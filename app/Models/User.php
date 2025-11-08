<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, Notifiable;

    const USER_ROLE_ADMIN = 'admin';
    const USER_ROLE_USER  = 'user';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
    ];

    /**
     * The attributes that are visible for serialization.
     *
     * @var array<int, string>
     */
    protected $visible = [
        'id',
        'name',
        'email',
        'role',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
    ];

    /**
     * Get the tasks assigned to the user.
     */
    public function tasks()
    {
        return $this->hasMany(Task::class);
    }

    /**
     * Get the user availabilities.
     */
    public function availabilities()
    {
        return $this->hasMany(UserAvailability::class);
    }

    /**
     * Check if user is an admin.
     *
     * @return bool
     */
    public function isAdmin()
    {
        return $this->role === 'admin';
    }

    /**
     * Check if user is a regular user.
     *
     * @return bool
     */
    public function isUser()
    {
        return $this->role === 'user';
    }
}
