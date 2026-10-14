<?php

namespace App\Models;

class YouTubeComment extends SocialCutComment
{
    protected static function booted()
    {
        static::addGlobalScope('platform_youtube', function ($builder) {
            $builder->where('platform', 'youtube');
        });

        static::creating(function ($model) {
            if (empty($model->platform)) {
                $model->platform = 'youtube';
            }
        });
    }
}
