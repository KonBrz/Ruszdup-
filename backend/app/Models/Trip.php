<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;


class Trip extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'destination',
        'description',
        'start_date',
        'end_date',
        'user_id',
        'assigned_to',
    ];

    public function tasks(): HasMany
    {
        return $this->hasMany(Task::class);
    }

    // Relacja do użytkownika, który utworzył trip
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    // Relacja do przypisanego użytkownika
    public function assignedUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }
}
