<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Hash;

class Cliente extends Model
{
    use HasFactory;

    protected $table = 'users';

    protected $fillable = [
        // Dados básicos
        'name',
        'nome_cliente', 
        'email',
        'password',
        'role',
        'apelido',
        
        // Documentos
        'cpf',
        'rg',
        
        // Dados pessoais
        'data_nascimento',
        'sexo',
        
        // Endereço
        'endereco',
        'numero_endereco',
        'complemento',
        'bairro',
        'cidade',
        'estado',
        'cep',
        'pais',
        
        // Contatos tradicionais
        'telefone_principal',
        'telefone_2',
        
        // Redes sociais e contatos digitais
        'instagram',
        'whatsapp',
        'tiktok',
        
        // Dados do cliente
        'codigo_cliente',
        'data_cadastro',
        'observacao_cliente',
        'tipo_cliente',
        'bloqueado',
        'ultima_compra',
        'ultima_visita',
        'total_pedidos',
    ];

    protected $hidden = [
        'password',
        'remember_token', // Manter hidden para segurança
    ];

    protected $casts = [
        // Booleanos
        'bloqueado' => 'boolean',
        'is_admin' => 'boolean',
        
        // Datas
        'data_nascimento' => 'date',
        'data_cadastro' => 'datetime',
        'ultima_compra' => 'datetime',
        'ultima_visita' => 'datetime',
        'email_verified_at' => 'datetime',
        
        // Inteiros
        'codigo_cliente' => 'integer',
        'total_pedidos' => 'integer',
        
        // Hash automático da senha
        'password' => 'hashed',
    ];

    // Valores padrão
    protected $attributes = [
        'role' => 'client',
        'bloqueado' => false,
        'total_pedidos' => 0,
        'pais' => 'Brasil',
    ];

    // ===== SCOPES =====
    
    public function scopeAtivos($query)
    {
        return $query->where('bloqueado', false);
    }

    public function scopeBloqueados($query)
    {
        return $query->where('bloqueado', true);
    }

    public function scopeClientes($query)
    {
        return $query->where('role', 'client');
    }

    public function scopePorCidade($query, $cidade)
    {
        return $query->where('cidade', 'like', "%{$cidade}%");
    }

    public function scopePorEstado($query, $estado)
    {
        return $query->where('estado', $estado);
    }

    public function scopeComPedidos($query)
    {
        return $query->where('total_pedidos', '>', 0);
    }

    public function scopeSemPedidos($query)
    {
        return $query->where('total_pedidos', 0);
    }

    public function scopeComInstagram($query)
    {
        return $query->whereNotNull('instagram');
    }

    public function scopeComWhatsApp($query)
    {
        return $query->whereNotNull('whatsapp');
    }

    public function scopeComTikTok($query)
    {
        return $query->whereNotNull('tiktok');
    }

    public function scopeBuscar($query, $termo)
    {
        return $query->where(function($q) use ($termo) {
            $q->where('name', 'like', "%{$termo}%")
              ->orWhere('nome_cliente', 'like', "%{$termo}%")
              ->orWhere('email', 'like', "%{$termo}%")
              ->orWhere('cpf', 'like', "%{$termo}%")
              ->orWhere('codigo_cliente', 'like', "%{$termo}%")
              ->orWhere('telefone_principal', 'like', "%{$termo}%")
              ->orWhere('whatsapp', 'like', "%{$termo}%");
        });
    }

    // ===== MUTATORS (Formatação na Entrada) =====
    
    public function setCpfAttribute($value)
    {
        $this->attributes['cpf'] = $value ? preg_replace('/\D/', '', $value) : null;
    }

    public function setCepAttribute($value)
    {
        $this->attributes['cep'] = $value ? preg_replace('/\D/', '', $value) : null;
    }

    public function setTelefonePrincipalAttribute($value)
    {
        $this->attributes['telefone_principal'] = $value ? preg_replace('/\D/', '', $value) : null;
    }

    public function setTelefone2Attribute($value)
    {
        $this->attributes['telefone_2'] = $value ? preg_replace('/\D/', '', $value) : null;
    }

    public function setWhatsappAttribute($value)
    {
        $this->attributes['whatsapp'] = $value ? preg_replace('/\D/', '', $value) : null;
    }

    public function setEstadoAttribute($value)
    {
        $this->attributes['estado'] = $value ? strtoupper($value) : null;
    }

    public function setInstagramAttribute($value)
    {
        // Remove @ se o usuário digitou
        $this->attributes['instagram'] = $value ? str_replace('@', '', $value) : null;
    }

    public function setTiktokAttribute($value)
    {
        // Remove @ se o usuário digitou
        $this->attributes['tiktok'] = $value ? str_replace('@', '', $value) : null;
    }

    // ===== ACCESSORS (Formatação na Saída) =====
    
    public function getNomeCompletoAttribute()
    {
        return $this->nome_cliente ?: $this->name;
    }

    public function getCpfFormatadoAttribute()
    {
        if (!$this->cpf) return null;
        return preg_replace('/(\d{3})(\d{3})(\d{3})(\d{2})/', '$1.$2.$3-$4', $this->cpf);
    }

    public function getCepFormatadoAttribute()
    {
        if (!$this->cep) return null;
        return preg_replace('/(\d{5})(\d{3})/', '$1-$2', $this->cep);
    }

    public function getTelefonePrincipalFormatadoAttribute()
    {
        if (!$this->telefone_principal) return null;
        $telefone = $this->telefone_principal;
        
        if (strlen($telefone) === 11) {
            return preg_replace('/(\d{2})(\d{5})(\d{4})/', '($1) $2-$3', $telefone);
        } elseif (strlen($telefone) === 10) {
            return preg_replace('/(\d{2})(\d{4})(\d{4})/', '($1) $2-$3', $telefone);
        }
        
        return $telefone;
    }

    public function getTelefone2FormatadoAttribute()
    {
        if (!$this->telefone_2) return null;
        $telefone = $this->telefone_2;
        
        if (strlen($telefone) === 11) {
            return preg_replace('/(\d{2})(\d{5})(\d{4})/', '($1) $2-$3', $telefone);
        } elseif (strlen($telefone) === 10) {
            return preg_replace('/(\d{2})(\d{4})(\d{4})/', '($1) $2-$3', $telefone);
        }
        
        return $telefone;
    }

    public function getWhatsappFormatadoAttribute()
    {
        if (!$this->whatsapp) return null;
        $whatsapp = $this->whatsapp;
        
        if (strlen($whatsapp) === 11) {
            return preg_replace('/(\d{2})(\d{5})(\d{4})/', '($1) $2-$3', $whatsapp);
        } elseif (strlen($whatsapp) === 10) {
            return preg_replace('/(\d{2})(\d{4})(\d{4})/', '($1) $2-$3', $whatsapp);
        }
        
        return $whatsapp;
    }

    public function getInstagramUrlAttribute()
    {
        if (!$this->instagram) return null;
        return 'https://instagram.com/' . $this->instagram;
    }

    public function getTiktokUrlAttribute()
    {
        if (!$this->tiktok) return null;
        return 'https://tiktok.com/@' . $this->tiktok;
    }

    public function getWhatsappUrlAttribute()
    {
        if (!$this->whatsapp) return null;
        return 'https://wa.me/55' . $this->whatsapp;
    }

    public function getIdadeAttribute()
    {
        if (!$this->data_nascimento) return null;
        return $this->data_nascimento->age;
    }

    public function getTempoComClienteAttribute()
    {
        if (!$this->data_cadastro) return null;
        return $this->data_cadastro->diffForHumans();
    }

    public function getStatusAttribute()
    {
        return $this->bloqueado ? 'Bloqueado' : 'Ativo';
    }

    public function getStatusClassAttribute()
    {
        return $this->bloqueado ? 'danger' : 'success';
    }

    // ===== MÉTODOS AUXILIARES =====
    
    public function isAtivo()
    {
        return !$this->bloqueado;
    }

    public function isBloqueado()
    {
        return $this->bloqueado;
    }

    public function bloquear()
    {
        return $this->update(['bloqueado' => true]);
    }

    public function desbloquear()
    {
        return $this->update(['bloqueado' => false]);
    }

    public function temInstagram()
    {
        return !empty($this->instagram);
    }

    public function temWhatsApp()
    {
        return !empty($this->whatsapp);
    }

    public function temTikTok()
    {
        return !empty($this->tiktok);
    }

    public function incrementarPedidos()
    {
        return $this->increment('total_pedidos');
    }

    public function atualizarUltimaCompra()
    {
        return $this->update(['ultima_compra' => now()]);
    }

    public function atualizarUltimaVisita()
    {
        return $this->update(['ultima_visita' => now()]);
    }

    // ===== MÉTODO ESTÁTICO =====
    
    public static function gerarProximoCodigo()
    {
        return static::max('codigo_cliente') + 1;
    }

    public static function buscarPorCodigo($codigo)
    {
        return static::where('codigo_cliente', $codigo)->first();
    }

    public static function buscarPorCpf($cpf)
    {
        $cpfLimpo = preg_replace('/\D/', '', $cpf);
        return static::where('cpf', $cpfLimpo)->first();
    }

    // ===== BOOT METHOD =====
    
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($cliente) {
            // Gerar código automaticamente se não informado
            if (!$cliente->codigo_cliente) {
                $cliente->codigo_cliente = static::gerarProximoCodigo();
            }
            
            // Definir data de cadastro
            if (!$cliente->data_cadastro) {
                $cliente->data_cadastro = now();
            }
            
            // Definir role como client
            $cliente->role = 'client';
        });

        static::updating(function ($cliente) {
            // Atualizar ultima_visita sempre que atualizar
            $cliente->ultima_visita = now();
        });
    }
}
