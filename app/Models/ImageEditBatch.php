<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ImageEditBatch extends Model
{
    protected $table = 'image_edit_batches';

    protected $fillable = [
        'provider',
        'mode',
        'batch_name',
        'provider_input_file_name',
        'provider_output_file_name',
        'local_output_file_path',
        'status',
        'manifest_path',
        'model',
        'total_items',
        'processed_items',
        'started_at',
        'finished_at',
        'last_polled_at',
        'lock_version',
        'error_message',
        'created_by',
    ];

    protected $casts = [
        'total_items' => 'integer',
        'processed_items' => 'integer',
        'created_by' => 'integer',
        'lock_version' => 'integer',
        'started_at' => 'datetime',
        'finished_at' => 'datetime',
        'last_polled_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function items(): HasMany
    {
        return $this->hasMany(ImageEditBatchItem::class, 'batch_id');
    }

    public function isTerminal(): bool
    {
        return in_array($this->status, [
            'succeeded',
            'partially_succeeded',
            'failed',
            'cancelled',
        ], true);
    }

    public function isOpen(): bool
    {
        return in_array($this->status, [
            'pending',
            'submitted',
            'running',
            'result_processing',
        ], true);
    }
}