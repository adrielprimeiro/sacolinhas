<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EvolutionMessage extends Model
{
    use HasFactory;

    protected $table = 'evolution_messages';

    protected $fillable = [
        'user_id',
        'admin_id',
        'instance_name',
        'remote_jid',
        'push_name',
        'direction',
        'status',
        'message_id',
        'from',
        'to',
        'body',
        'media_type',
        'media_url',
        'failed_reason',
        'raw_payload',
    ];

    protected $casts = [
        'raw_payload' => 'array',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function admin()
    {
        return $this->belongsTo(User::class, 'admin_id');
    }
}
