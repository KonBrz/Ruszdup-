<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;


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
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
    public function tasks(): HasMany
    {
        return $this->hasMany(Task::class, 'trip_id');
    }

    public function flagged()
    {
        return $this->hasMany(Flagged::class);
    }

    public function invitations()
    {
        return $this->hasMany(TripInvitation::class);
    }

    public function tripUsers()
    {
        return $this->belongsToMany(User::class, 'trip_user', 'trip_id', 'user_id');
    }


}
