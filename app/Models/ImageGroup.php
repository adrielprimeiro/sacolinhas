<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ImageGroup extends Model
{
	protected $fillable = [
		'status',
		'confidence_score',
		'grouping_method',
		'processed_at',
		'metadata',
	];

    public function medias()
    {
        return $this->hasMany(ItemMedia::class, 'group_id');
    }
	
	protected $casts = [
		'metadata' => 'array',
		'processed_at' => 'datetime',
	];	
}
