<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class SocialCutComment extends Model
{
    use HasFactory;

    protected $table = 'social_cut_comments';

    protected $fillable = [
        'platform',
        'comment_id',
        'video_id',
        'live_item_id',
        'live_id',
        'author_name',
        'author_channel_id',
        'author_profile_image',
        'comment_text',
        'is_purchase_intent',
        'detected_keyword',
        'reply_sent',
        'reply_text',
        'reply_sent_at',
        'published_at',
    ];

    protected $casts = [
        'is_purchase_intent' => 'boolean',
        'reply_sent' => 'boolean',
        'reply_sent_at' => 'datetime',
        'published_at' => 'datetime',
    ];

    public function liveItem()
    {
        return $this->belongsTo(LiveItem::class, 'live_item_id');
    }

    public function live()
    {
        return $this->belongsTo(Live::class, 'live_id');
    }
}
