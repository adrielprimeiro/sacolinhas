<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LiveCodeRequest extends Model
{
    protected $table = 'live_code_requests';

    protected $fillable = [
        'live_id',
        'live_message_id',
        'username',
        'user_id',
        'item_id',
        'codigo',
        'message_text',
        'status'
    ];

    public function live()
    {
        return $this->belongsTo(Live::class);
    }

    public function liveMessage()
    {
        return $this->belongsTo(LiveMessage::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function item()
    {
        return $this->belongsTo(Item::class);
    }
}
