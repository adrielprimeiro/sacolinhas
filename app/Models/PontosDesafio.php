<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PontosDesafio extends Model
{
    use HasFactory;

    protected $table = 'pontos_desafios';

    protected $fillable = [
        'user_id',
        'mes_ano',
        'pontos',
        'desafio_nome',
    ];

    public $timestamps = false; // Apenas created_at via DB default

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
