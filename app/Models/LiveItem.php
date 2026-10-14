<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class LiveItem extends Model
{
    use HasFactory;

    protected $table = 'live_items';

    protected $guarded = ['id'];

    protected $casts = [
        'is_reviewed' => 'boolean',
        'youtube_published_at' => 'datetime',
        'tiktok_published_at' => 'datetime',
        'instagram_published_at' => 'datetime',
        'video_cut_started_at' => 'datetime',
        'video_cut_finished_at' => 'datetime',
    ];

    public function live()
    {
        return $this->belongsTo(Live::class, 'live_id');
    }

    public function item()
    {
        return $this->belongsTo(Item::class, 'item_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
