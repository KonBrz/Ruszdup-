<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;


class Task extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'title',
        'priority',
        'deadline',
        'trip_id',
    ];

        public function trip(): BelongsTo
        {
            return $this->belongsTo(Trip::class, 'trip_id');
        }

        public function user(): BelongsTo
        {
            return $this->belongsTo(User::class, 'user_id');
        }

        public function flagged()
        {
            return $this->hasMany(Flagged::class);
        }

        public function taskUsers(){
            return $this->belongsToMany(User::class, 'task_user', 'task_id', 'user_id')
                ->withPivot(['completed', 'ignored']);
        }
    }
