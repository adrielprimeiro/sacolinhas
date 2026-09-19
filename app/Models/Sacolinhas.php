<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
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

        static::creating(function ($sacolinha) {
            // 1. Se pertence a uma live, a sacolinha pertence estritamente ao brechó daquela live
            if (!empty($sacolinha->live_id)) {
                $liveBrechoId = DB::table('lives')->where('id', $sacolinha->live_id)->value('brecho_id');
                if (!empty($liveBrechoId)) {
                    $sacolinha->brecho_id = $liveBrechoId;
                    return;
                }
            }

            // 2. Se não for de live, tenta herdar do item
            if (!empty($sacolinha->item_id)) {
                $itemBrechoId = DB::table('items')->where('id', $sacolinha->item_id)->value('brecho_id');
                if (!empty($itemBrechoId)) {
                    $sacolinha->brecho_id = $itemBrechoId;
                    return;
                }
            }

            // 3. Usuário logado
            if (empty($sacolinha->brecho_id) && auth()->check() && !empty(auth()->user()->brecho_id)) {
                $sacolinha->brecho_id = auth()->user()->brecho_id;
            }
            if (empty($sacolinha->brecho_id)) {
                $sacolinha->brecho_id = 1;
            }
        });

        static::created(function ($sacolinha) {
            // Se pertencer a um brechó parceiro, garante o vínculo do cliente em brecho_clientes
            if (!empty($sacolinha->brecho_id) && $sacolinha->brecho_id > 1 && !empty($sacolinha->user_id)) {
                DB::table('brecho_clientes')->insertOrIgnore([
                    'brecho_id'  => $sacolinha->brecho_id,
                    'user_id'    => $sacolinha->user_id,
                    'origem'     => 'sacolinha',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
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