<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    protected $table = 'users';

    protected $primaryKey = 'user_id';

    protected $fillable = [
        'username',
        'password',
        'first_name',
        'last_name',
        'role',
        'is_active',
        'failed_login_attempts',
        'lockout_level',
        'locked_until',
        'last_failed_login_at',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'password' => 'hashed',
            'is_active' => 'boolean',
            'failed_login_attempts' => 'integer',
            'lockout_level' => 'integer',
            'locked_until' => 'datetime',
            'last_failed_login_at' => 'datetime',
        ];
    }

    public function sales()
    {
        return $this->hasMany(
            Sale::class,
            'user_id',
            'user_id'
        );
    }

    public function purchases()
    {
        return $this->hasMany(
            Purchase::class,
            'user_id',
            'user_id'
        );
    }

    public function activityLogs()
    {
        return $this->hasMany(
            ActivityLog::class,
            'user_id',
            'user_id'
        );
    }
}