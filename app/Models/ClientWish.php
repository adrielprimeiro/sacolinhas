<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ClientWish extends Model
{
    use HasFactory;

    protected $table = 'client_wishes';

    protected $fillable = [
        'user_id',
        'raw_prompt',
        'category',
        'size',
        'max_price',
        'parsed_attributes',
        'status',
    ];

    protected $casts = [
        'parsed_attributes' => 'array',
        'max_price' => 'decimal:2',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
