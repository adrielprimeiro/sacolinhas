<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SocialChannelAccount extends Model
{
    use HasFactory;

    protected $table = 'social_channel_accounts';

    protected $fillable = [
        'provider',
        'channel_id',
        'channel_name',
        'channel_avatar',
        'access_token',
        'refresh_token',
        'token_expires_at',
        'settings',
        'is_active',
    ];

    protected $casts = [
        'token_expires_at' => 'datetime',
        'settings' => 'array',
        'is_active' => 'boolean',
    ];

    /**
     * Retorna a conta ativa de um provedor específico (ex: 'youtube')
     */
    public static function getActiveAccount(string $provider = 'youtube'): ?self
    {
        return static::where('provider', $provider)
            ->where('is_active', true)
            ->orderBy('id', 'desc')
            ->first();
    }
}
