<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Brecho extends Model
{
    use HasFactory;

    protected $table = 'brechos';

    protected $fillable = [
        'nome',
        'slug',
        'documento',
        'telefone',
        'whatsapp',
        'chave_pix',
        'tipo_chave_pix',
        'ativo',
        'configuracoes',
        'razao_social',
        'inscricao_estadual',
        'regime_tributario',
        'logradouro',
        'numero_endereco',
        'complemento',
        'bairro',
        'codigo_municipio',
        'municipio',
        'uf',
        'cep',
        'nfe_serie',
        'nfe_ultimo_numero',
        'nfe_ambiente',
        'certificado_path',
        'certificado_senha',
        'csc',
        'csc_id',
        'prefixo_codigo',
        'taxa_cadastro_peca',
        'comissao_porcentagem',
        'fornecedor_id',
        'fornecedor_nome',
    ];

    protected $casts = [
        'ativo' => 'boolean',
        'configuracoes' => 'array',
        'taxa_cadastro_peca' => 'decimal:2',
        'comissao_porcentagem' => 'decimal:2',
    ];

    /**
     * Operadores e administradores vinculados a este brechó
     */
    public function users(): HasMany
    {
        return $this->hasMany(User::class, 'brecho_id')->where('role', '!=', 'client');
    }

    /**
     * Retorna a lista de operadores/administradores para exibição no painel
     */
    public function operadores()
    {
        if ($this->id == 1) {
            return User::where(function ($q) {
                $q->whereIn('role', ['admin_master', 'admin', 'operador'])
                  ->orWhere(function ($sub) {
                      $sub->where('brecho_id', 1)->where('role', '!=', 'client');
                  });
            })->where('role', '!=', 'client')->orderBy('name')->get();
        }

        return User::where('brecho_id', $this->id)
            ->where('role', '!=', 'client')
            ->orderBy('name')
            ->get();
    }

    /**
     * Itens / Peças do estoque pertencentes a este brechó
     */
    public function items(): HasMany
    {
        return $this->hasMany(Item::class, 'brecho_id');
    }

    /**
     * Transmissões e lives realizadas por este brechó
     */
    public function lives(): HasMany
    {
        return $this->hasMany(Live::class, 'brecho_id');
    }

    /**
     * Sacolinhas geradas para este brechó
     */
    public function sacolinhas(): HasMany
    {
        return $this->hasMany(Sacolinhas::class, 'brecho_id');
    }

    /**
     * Pedidos finalizados deste brechó
     */
    public function pedidos(): HasMany
    {
        return $this->hasMany(Pedido::class, 'brecho_id');
    }

    /**
     * Clientes vinculados a este brechó
     */
    public function clientes(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'brecho_clientes', 'brecho_id', 'user_id')
            ->withPivot('origem')
            ->withTimestamps();
    }
}
