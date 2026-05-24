<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Configuracao extends Model
{
    protected $table = 'configuracoes';

    protected $fillable = [
        'chave',
        'valor',
    ];

    /**
     * Get a configuration value by key.
     *
     * @param string $chave
     * @param mixed $default
     * @return mixed
     */
    public static function get($chave, $default = null)
    {
        $config = self::where('chave', $chave)->first();
        return $config ? $config->valor : $default;
    }

    /**
     * Set a configuration value by key.
     *
     * @param string $chave
     * @param mixed $valor
     * @return void
     */
    public static function set($chave, $valor)
    {
        self::updateOrCreate(
            ['chave' => $chave],
            ['valor' => $valor]
        );
    }
}
