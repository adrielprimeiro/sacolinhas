<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ImageEditBatchItem extends Model
{
    protected $table = 'image_edit_batch_items';

    protected $fillable = [
        'batch_id',
        'custom_id',
        'original_path',
        'edited_path',
        'prompt',
        'mime_type',
        'status',
        'provider_status',
        'provider_response',
        'error_message',
        'processed_at',
        'attempt_count',
        'content_hash',
    ];

    protected $casts = [
        'batch_id' => 'integer',
        'attempt_count' => 'integer',
        'processed_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function batch(): BelongsTo
    {
        return $this->belongsTo(ImageEditBatch::class, 'batch_id');
    }

    public function isProcessed(): bool
    {
        return $this->status === 'processed';
    }

    public function hasFailed(): bool
    {
        return $this->status === 'failed';
    }
}