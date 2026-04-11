<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WhatsappMessage extends Model
{
    use HasFactory;

    protected $table = 'whatsapp_messages';

    protected $fillable = [
        'user_id',
        'live_id',
        'pedido_id',
        'direction',
        'status',
        'message_sid',
        'account_sid',
        'from',
        'to',
        'body',
        'num_media',
        'media_url',
        'media_content_type',
        'message_type',
        'button_text',
        'button_payload',
        'profile_name',
        'wa_id',
        'raw_payload',
        'created_at',
        'updated_at',
    ];

    protected $casts = [
        'raw_payload' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    // Relacionamentos (opcional, se precisar)
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}