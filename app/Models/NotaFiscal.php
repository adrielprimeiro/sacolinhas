<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Traits\BelongsToBrecho;

class NotaFiscal extends Model
{
    use HasFactory, BelongsToBrecho;

    protected $table = 'notas_fiscais';

    protected $fillable = [
        'brecho_id',
        'pedido_id',
        'tipo',
        'modelo',
        'serie',
        'numero',
        'chave_acesso',
        'status',
        'cStat',
        'xMotivo',
        'protocolo',
        'xml_enviado',
        'xml_autorizado',
        'xml_cancelamento',
        'danfe_pdf_path',
        'valor_total',
        'valor_produtos',
        'valor_frete',
        'valor_desconto',
        'data_emissao',
        'data_autorizacao',
        'data_cancelamento',
        'justificativa_cancelamento',
    ];

    protected $casts = [
        'data_emissao' => 'datetime',
        'data_autorizacao' => 'datetime',
        'data_cancelamento' => 'datetime',
        'valor_total' => 'decimal:2',
        'valor_produtos' => 'decimal:2',
        'valor_frete' => 'decimal:2',
        'valor_desconto' => 'decimal:2',
        'numero' => 'integer',
        'modelo' => 'integer',
    ];

    public function pedido()
    {
        return $this->belongsTo(Pedido::class, 'pedido_id');
    }

    public function brecho()
    {
        return $this->belongsTo(Brecho::class, 'brecho_id');
    }

    public function isAutorizada(): bool
    {
        return $this->status === 'autorizada';
    }

    public function isCancelada(): bool
    {
        return $this->status === 'cancelada';
    }

    public function getChaveFormatadaAttribute(): string
    {
        if (empty($this->chave_acesso) || strlen($this->chave_acesso) !== 44) {
            return $this->chave_acesso ?? '';
        }

        return trim(chunk_split($this->chave_acesso, 4, ' '));
    }
}
