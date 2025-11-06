<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Cliente extends Model
{
    use HasFactory;

    protected $table = 'users';

    protected $fillable = [
        'name', 'nome_cliente', 'email', 'password', 'cpf', 'telefone_principal',
        'endereco', 'numero_endereco', 'complemento', 'bairro', 'cidade', 
        'estado', 'cep', 'pais', 'data_nascimento', 'sexo', 'bloqueado',
        'codigo_cliente', 'data_cadastro', 'observacao_cliente', 'role'
    ];

    protected $casts = [
        'bloqueado' => 'boolean',
        'data_nascimento' => 'date',
        'data_cadastro' => 'datetime'
    ];

    public function scopeAtivos($query)
    {
        return $query->where('bloqueado', false);
    }

    public function scopeClientes($query)
    {
        return $query->where('role', 'client');
    }

    public function getNomeCompletoAttribute()
    {
        return $this->nome_cliente ?: $this->name;
    }
}
