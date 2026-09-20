<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SeverinoDynamicTool extends Model
{
    protected $table = 'severino_dynamic_tools';

    protected $fillable = [
        'nome',
        'descricao',
        'modulo_area',
        'parametros',
        'sql_template',
        'created_by',
        'ativo',
        'exemplos_uso'
    ];

    protected $casts = [
        'parametros' => 'array',
        'ativo' => 'boolean'
    ];
}
