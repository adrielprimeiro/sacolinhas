<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ItemMedia extends Model
{
    protected $table = 'item_media';
	protected $fillable = [
		'item_id',
		'group_id',
		'media_type',
		'url',
		'thumbnail_url',
		'position',
		'is_cover',
		'alt_text',
		'metadata',
	];

    protected $casts = [
        'metadata' => 'array',
        'is_cover' => 'boolean',
    ];

    public function item()
    {
        return $this->belongsTo(Item::class, 'item_id');
    }
	
	public function group()
	{
		return $this->belongsTo(ImageGroup::class, 'group_id');
	}

}