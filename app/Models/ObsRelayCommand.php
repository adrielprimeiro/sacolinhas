<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ObsRelayCommand extends Model
{
    protected $fillable = [
        'command_type',
        'payload',
        'status',
        'agent_id',
        'result',
        'executed_at',
    ];

    protected $casts = [
        'payload'     => 'array',
        'executed_at' => 'datetime',
    ];

    /** Scopes */
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }
}
