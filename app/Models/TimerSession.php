<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TimerSession extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'user_id',
        'planned_duration',
        'actual_duration',
        'completed',
        'started_at',
        'ended_at',
    ];

    protected $casts = [
        'completed'  => 'boolean',
        'started_at' => 'datetime',
        'ended_at'   => 'datetime',
        'created_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
