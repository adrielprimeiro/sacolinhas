<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Traits\BelongsToBrecho;

class Sacolinhas extends Model
{
    use HasFactory, BelongsToBrecho;

    protected $table = 'sacolinhas';

    protected $fillable = [
        'brecho_id',
        'user_id',
        'item_id', 
        'live_id',
        'quantity',
        'price',
        'add_at',
        'tray',
        'status',
        'obs'
    ];

    protected $casts = [
        'add_at' => 'datetime',
        'price' => 'decimal:2',
        'quantity' => 'integer'
    ];

    protected static function booted()
    {
        static::addGlobalScope('active', function ($builder) {
            $builder->where('status', '!=', 'pedido');
        });
    }

    // Relacionamentos
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function item()
    {
        return $this->belongsTo(Item::class);
    }

    public function live()
    {
        return $this->belongsTo(Live::class);
    }
}