<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Avaliacao extends Model
{
    use HasFactory;

    protected $table = 'avaliacoes';

    protected $fillable = [
        'user_id',
        'tipo_compra',
        'tipo_cliente',
        'frete',
        'pagamento_escolhido',
        'total_venda',
        'total_payout',
        'status',
        'data_avaliacao',
        'observacoes'
    ];

    protected $casts = [
        'data_avaliacao' => 'datetime',
        'frete' => 'decimal:2',
        'total_venda' => 'decimal:2',
        'total_payout' => 'decimal:2',
    ];

    /**
     * Relacionamento com o fornecedor (cliente).
     */
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Relacionamento com as peças da avaliação.
     */
    public function items()
    {
        return $this->hasMany(AvaliacaoItem::class, 'avaliacao_id');
    }

    // Accessors
    public function getFormattedTotalVendaAttribute()
    {
        return 'R$ ' . number_format((float) $this->total_venda, 2, ',', '.');
    }

    public function getFormattedTotalPayoutAttribute()
    {
        return 'R$ ' . number_format((float) $this->total_payout, 2, ',', '.');
    }

    public function getFormattedFreteAttribute()
    {
        return 'R$ ' . number_format((float) $this->frete, 2, ',', '.');
    }

    public function getFormattedDataAvaliacaoAttribute()
    {
        return $this->data_avaliacao ? $this->data_avaliacao->format('d/m/Y H:i') : '';
    }

    public function getStatusLabelAttribute()
    {
        switch ($this->status) {
            case 'finalizada':
                return 'Finalizada';
            case 'cancelada':
                return 'Cancelada';
            case 'rascunho':
            default:
                return 'Rascunho';
        }
    }
}
