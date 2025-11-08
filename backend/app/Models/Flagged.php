<?php

namespace App\Models;


use App\Enums\Decision;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Flagged extends Model
{
    protected $table = 'flagged';
    use HasFactory;

    protected $fillable = [
        'user_id',
        'trip_id',
        'task_id',
        'reason',
        'is_closed',
        'decision',
    ];

    protected $casts = [
        'decision' => Decision::class,
    ];

    protected static function booted()
    {
        static::updated(function (Flagged $flag) {
            if ($flag->decision === Decision::Remove) {
                if ($flag->user_id) {
                    $flag->user()->update(['is_banned' => true]);
                }

                if ($flag->task_id) {
                    $flag->task()->delete();
                }

                if ($flag->trip_id) {
                    $flag->trip()->delete();
                }
            }
        });
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function trip()
    {
        return $this->belongsTo(Trip::class);
    }

    public function task()
    {
        return $this->belongsTo(Task::class);
    }
}
