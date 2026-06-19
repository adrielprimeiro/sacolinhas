<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class LiveMessage extends Model
{
    use HasFactory;

    protected $table = 'live_messages';

    protected $fillable = [
        'live_id',
        'plataforma',
        'username',
        'message',
        'captured_at'
    ];

    protected $casts = [
        'captured_at' => 'datetime',
    ];

    public function live()
    {
        return $this->belongsTo(Live::class);
    }

    public function codeRequests()
    {
        return $this->hasMany(LiveCodeRequest::class);
    }
}
