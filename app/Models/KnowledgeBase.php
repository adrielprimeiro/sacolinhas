<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class KnowledgeBase extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'category',
        'content',
        'embedding',
        'is_active',
    ];

    protected $casts = [
        'embedding' => 'array',
        'is_active' => 'boolean',
    ];
}
