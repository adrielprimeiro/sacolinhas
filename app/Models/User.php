<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Database\Eloquent\Relations\HasOne;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'phone',
		'whatsapp', 
        'cpf',
        'nome_cliente',     // TikTok
        'apelido',          // Apelido
        'remember_token',   // Instagram
        'bloqueado',
        'needs_completion'
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];


    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
    ];

    /**
     * Obtém a última movimentação de ContaCorrente para o usuário, representando seu saldo atual.
     */
    public function latestContaCorrente()
    {
        return $this->hasOne(ContaCorrente::class, 'user_id')
                    ->latest('data_movimentacao') // Ordena pela data da movimentação (mais recente primeiro)
                    ->latest('id');               // Para desempate, pega o ID mais alto
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }
	
	public function limite()
	{
		return $this->hasOne(ClienteLimite::class, 'user_id');
	}

	public function whatsappMessages()
	{
		return $this->hasMany(WhatsappMessage::class);
	}


	public function grupos()
	{
		return $this->belongsToMany(Grupo::class, 'grupo_membros');
	}

	public function grupoAtual()
	{
		return $this->belongsToMany(Grupo::class, 'grupo_membros')->latest('pivot_created_at')->first();
	}	

	/**
	 * Perfil financeiro desta conta (para lançamentos como cliente, fornecedor etc.).
	 */
	public function perfilFinanceiro(): HasOne
	{
		return $this->hasOne(Pessoa::class, 'user_id');
	}
}
