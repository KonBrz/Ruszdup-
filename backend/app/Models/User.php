<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class User extends Authenticatable implements FilamentUser
{
    use HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'password',
        'is_admin',
        'is_banned',
    ];
    protected $hidden = [
        'password',
        'remember_token',
    ];

    public function trips(): HasMany
    {
        return $this->hasMany(Trip::class, 'user_id');
    }
    public function flagged()
    {
        return $this->hasMany(Flagged::class);
    }

    public function tasks(): HasMany
    {
        return $this->hasMany(Task::class, 'user_id');
    }
    public function tripUsers()
    {
        return $this->belongsToMany(Trip::class, 'trip_user', 'user_id', 'trip_id');
    }
    public function taskUsers()
    {
        return $this->belongsToMany(User::class, 'task_user', 'task_id', 'user_id')
            ->withPivot(['completed', 'ignored']);
    }

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_admin' => 'boolean',
        ];
    }
    public function  canAccessPanel(Panel $panel): bool
    {
        return $this->is_admin;
    }

}
