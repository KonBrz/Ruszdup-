<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TripInvitation extends Model
{
    protected $fillable = ['trip_id', 'token'];

    public function trip()
    {
        return $this->belongsTo(Trip::class);
    }
}
