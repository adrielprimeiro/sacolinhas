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
        'brecho_id',
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
        'tiktok',           // TikTok real
        'instagram',        // Instagram real
        'bloqueado',
        'needs_completion',
        'photo',
        'sacolinha_autorizada_por',
        'sacolinha_autorizada_obs',
        'permissions',
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
        'permissions' => 'array',
    ];

    /**
     * The "booted" method of the model.
     */
    protected static function booted()
    {
        static::created(function ($user) {
            if ($user->role === 'client') {
                \App\Models\Pessoa::firstOrCreate(
                    ['user_id' => $user->id],
                    [
                        'nome'      => $user->name,
                        'documento' => $user->cpf,
                        'tipo'      => 'cliente_circular',
                    ]
                );
            }
        });

        static::updated(function ($user) {
            if ($user->role === 'client') {
                \App\Models\Pessoa::updateOrCreate(
                    ['user_id' => $user->id],
                    [
                        'nome'      => $user->name,
                        'documento' => $user->cpf,
                        'tipo'      => 'cliente_circular',
                    ]
                );
            }
        });
    }

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
            'permissions' => 'array',
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

	public function sacolinhas()
	{
		return $this->hasMany(Sacolinhas::class, 'user_id');
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

	/**
	 * Brechó ao qual este operador/administrador pertence.
	 */
	public function brecho()
	{
		return $this->belongsTo(Brecho::class, 'brecho_id');
	}

	/**
	 * Brechós aos quais este cliente está vinculado.
	 */
	public function brechosVinculados()
	{
		return $this->belongsToMany(Brecho::class, 'brecho_clientes', 'user_id', 'brecho_id')
			->withPivot('origem')
			->withTimestamps();
	}

	/**
	 * Verifica se o usuário é um operador/admin de brechó parceiro.
	 */
	public function isBrechoParceiro(): bool
	{
		return $this->role === 'brecho_admin' 
			|| (!empty($this->brecho_id) && $this->brecho_id > 1 && in_array($this->role, ['brecho_admin', 'operador']));
	}

	/**
	 * Lista de módulos do sistema que podem receber permissões granulares (none, read, write).
	 */
	public static function availableModules(): array
	{
		return [
			'dashboard' => [
				'label' => 'Dashboard Geral',
				'icon' => 'fas fa-home',
				'description' => 'Indicadores gerais e visão inicial do sistema',
				'entry_url' => '/dashboard',
			],
			'lives' => [
				'label' => 'Lives & Transmissão',
				'icon' => 'fas fa-broadcast-tower',
				'description' => 'Gerenciar Lives, Chat da Live, OBS, Bipagem, Cortes de Vídeo e Severino Cliper',
				'entry_url' => '/bags',
			],
			'sacolinhas' => [
				'label' => 'Sacolinhas & Vencimentos',
				'icon' => 'fas fa-shopping-bag',
				'description' => 'Sacolas da Live, Sacolas por Cliente, Sacolas Vencidas e Scanner de Sacolinha',
				'entry_url' => '/admin/sacolinhas',
			],
			'pedidos' => [
				'label' => 'Pedidos, Fretes & NF-e',
				'icon' => 'fas fa-receipt',
				'description' => 'Gestão de Pedidos, Etiquetas Melhor Envio, Devoluções e Notas Fiscais',
				'entry_url' => '/admin/pedido',
			],
			'avaliacoes' => [
				'label' => 'Avaliação de Desapegos',
				'icon' => 'fas fa-hand-holding-usd',
				'description' => 'Entrada de mercadoria, lotes de avaliação e aprovação de peças',
				'entry_url' => '/admin/avaliacoes',
			],
			'produtos' => [
				'label' => 'Produtos, Categorias & Estoque',
				'icon' => 'fas fa-box',
				'description' => 'Lista de Produtos, Categorias, Marcas, Inventário Físico, Conferências e Fotos',
				'entry_url' => '/items',
			],
			'clientes' => [
				'label' => 'Clientes & Carteira',
				'icon' => 'fas fa-users',
				'description' => 'Cadastro de Clientes, Bloqueios e Carteira / Conta Corrente do Cliente',
				'entry_url' => '/clientes',
			],
			'atendimento' => [
				'label' => 'Chat ao Vivo & WhatsApp',
				'icon' => 'fas fa-comments',
				'description' => 'Atendimento no Chat ao Vivo e WhatsApp Dashboard',
				'entry_url' => '/admin/chat',
			],
			'financeiro' => [
				'label' => 'Financeiro & Contábil',
				'icon' => 'fas fa-dollar-sign',
				'description' => 'Dashboard Financeiro, Fluxo de Caixa, DRE, Orçamento, Lançamentos, Conciliação e Contas',
				'entry_url' => '/admin/financeiro',
			],
			'clube' => [
				'label' => 'Clube & Engajamento',
				'icon' => 'fas fa-crown',
				'description' => 'Painel do Clube, Mensalidades, Desafios e Grupos',
				'entry_url' => '/admin/clube',
			],
			'ods' => [
				'label' => 'Relatório ODS (Novos Clientes & Faturamento)',
				'icon' => 'fas fa-bullseye',
				'description' => 'Página exclusiva de Novos Clientes e Faturamento no intervalo de tempo',
				'entry_url' => '/admin/relatorios/ods',
			],
			'relatorios' => [
				'label' => 'Demais Relatórios & Inteligência Artificial',
				'icon' => 'fas fa-chart-pie',
				'description' => 'Relatório de Clientes, Acessos ao Portal, Severino AI e Base de Conhecimento (RAG)',
				'entry_url' => '/admin/clientes/relatorios/dashboard',
			],
		];
	}

	/**
	 * Verifica se o usuário possui matriz de permissões personalizada configurada.
	 */
	public function hasCustomPermissions(): bool
	{
		return is_array($this->permissions) && !empty($this->permissions);
	}

	/**
	 * Retorna o nível de permissão do usuário em um módulo: 'none', 'read' ou 'write'.
	 */
	public function getModulePermission(string $module): string
	{
		if ($this->role === 'admin_master') {
			return 'write';
		}

		if ($module === 'master_only') {
			return 'none';
		}

		if ($this->isBrechoParceiro()) {
			$restrictedForBrecho = ['financeiro', 'avaliacoes', 'clube', 'ods', 'relatorios'];
			if (in_array($module, $restrictedForBrecho, true)) {
				return 'none';
			}
		}

		$isAdmin = in_array($this->role, ['admin', 'admin_master', 'brecho_admin'], true) || !empty($this->is_admin);
		if (!$isAdmin) {
			return 'none';
		}

		if ($module === 'carteira_cliente') {
			$permClientes = $this->getModulePermission('clientes');
			$permFinanceiro = $this->getModulePermission('financeiro');
			if ($permClientes === 'write' || $permFinanceiro === 'write') {
				return 'write';
			}
			if ($permClientes === 'read' || $permFinanceiro === 'read') {
				return 'read';
			}
			return 'none';
		}

		// Se for admin/parceiro sem permissões customizadas salvas (legado), mantém acesso de escrita
		if (!$this->hasCustomPermissions()) {
			return 'write';
		}

		if ($module === 'ods' && !array_key_exists('ods', $this->permissions)) {
			$level = $this->permissions['relatorios'] ?? 'none';
			return in_array($level, ['none', 'read', 'write'], true) ? $level : 'none';
		}

		$level = $this->permissions[$module] ?? 'none';
		return in_array($level, ['none', 'read', 'write'], true) ? $level : 'none';
	}

	/**
	 * Verifica se o usuário pode pelo menos visualizar (read ou write) o módulo.
	 */
	public function canAccessModule(string $module): bool
	{
		return in_array($this->getModulePermission($module), ['read', 'write'], true);
	}

	/**
	 * Verifica se o usuário pode editar/criar/excluir (write) no módulo.
	 */
	public function canEditModule(string $module): bool
	{
		return $this->getModulePermission($module) === 'write';
	}

	/**
	 * Verifica se o usuário está restrito ao modo somente leitura no módulo.
	 */
	public function isReadOnlyInModule(string $module): bool
	{
		return $this->getModulePermission($module) === 'read';
	}

	/**
	 * Retorna a primeira URL administrativa que o usuário tem permissão para acessar.
	 */
	public function firstAccessibleAdminUrl(): string
	{
		foreach (self::availableModules() as $key => $info) {
			if ($this->canAccessModule($key)) {
				return $info['entry_url'];
			}
		}

		return '/dashboard';
	}

	/**
	 * Identifica a qual módulo pertence um determinado caminho de URL (path relativo sem barra inicial).
	 */
	public static function resolveModuleForPath(string $cleanPath): ?string
	{
		$cleanPath = trim($cleanPath, '/');

		// Rotas exclusivas do Master
		if (\Illuminate\Support\Str::is(['admin/equipe*', 'admin/brechos*', 'admin/users*'], $cleanPath)) {
			return 'master_only';
		}

		// 1. Dashboard
		if (in_array($cleanPath, ['dashboard', 'home'], true)) {
			return 'dashboard';
		}

		// 2. Relatório ODS (exclusivo)
		if (\Illuminate\Support\Str::is([
			'admin/relatorios/ods*',
		], $cleanPath)) {
			return 'ods';
		}

		// 3. Demais Relatórios & IA (deve vir antes de admin/clientes* e admin/severino/cliper*)
		if (\Illuminate\Support\Str::is([
			'admin/relatorios*',
			'admin/clientes/relatorios*',
			'admin/portal-acessos*',
			'severino*',
			'admin/knowledge-base*',
		], $cleanPath)) {
			return 'relatorios';
		}

		// 3. Carteira do Cliente (acessível por Clientes ou Financeiro)
		if (\Illuminate\Support\Str::is([
			'admin/financeiro/conta-corrente*',
			'pedidos/registrar-debito-conclusao*',
		], $cleanPath)) {
			return 'carteira_cliente';
		}

		// 4. Financeiro
		if (\Illuminate\Support\Str::is([
			'admin/financeiro*',
			'financeiro*',
			'classificacao_financeira*',
		], $cleanPath)) {
			return 'financeiro';
		}

		// 5. Lives & Transmissão
		if (\Illuminate\Support\Str::is([
			'bags*',
			'lives*',
			'live/movimentacao*',
			'admin/live-dashboard*',
			'admin/live-chat*',
			'admin/live-feed*',
			'admin/live-bipagem*',
			'admin/live-contador*',
			'admin/lives*',
			'admin/live/*',
			'admin/obs-relay*',
			'admin/severino/cliper*',
			'admin/youtube*',
			'admin/tiktok*',
			'admin/instagram*',
			'api/lives*',
			'api/live-contador*',
		], $cleanPath)) {
			return 'lives';
		}

		// 6. Sacolinhas & Vencimentos
		if (\Illuminate\Support\Str::is([
			'sacolinhas*',
			'admin/sacolinhas*',
			'admin/sacolinhas-admin*',
			'admin/vencimentos*',
			'api/sacolinhas*',
			'api/frete/simular*',
		], $cleanPath)) {
			return 'sacolinhas';
		}

		// 7. Pedidos, Fretes & NF-e
		if (\Illuminate\Support\Str::is([
			'pedidos*',
			'admin/pedido*',
			'admin/melhor-envio*',
			'admin/nfe*',
			'admin/fiscal*',
		], $cleanPath)) {
			return 'pedidos';
		}

		// 8. Avaliação de Desapegos
		if (\Illuminate\Support\Str::is([
			'admin/avaliacoes*',
		], $cleanPath)) {
			return 'avaliacoes';
		}

		// 9. Produtos, Categorias & Estoque
		if (\Illuminate\Support\Str::is([
			'items*',
			'admin/items*',
			'admin/categorias*',
			'admin/marcas*',
			'inventario*',
			'alocacao*',
			'upload-batch*',
			'orphan-photos*',
			'image-groups*',
			'api/items*',
		], $cleanPath)) {
			return 'produtos';
		}

		// 10. Clientes
		if (\Illuminate\Support\Str::is([
			'clientes*',
			'admin/clientes*',
			'users/quick-create*',
			'api/clientes*',
		], $cleanPath)) {
			return 'clientes';
		}

		// 11. Atendimento (Chat & WhatsApp)
		if (\Illuminate\Support\Str::is([
			'admin/chat*',
			'admin/whatsapp-dashboard*',
			'twilio-out*',
		], $cleanPath)) {
			return 'atendimento';
		}

		// 12. Clube & Engajamento
		if (\Illuminate\Support\Str::is([
			'admin/clube*',
			'admin/grupos*',
			'dashboard-pontuacoes*',
		], $cleanPath)) {
			return 'clube';
		}

		return null;
	}

	/**
	 * Identifica se a requisição atual é um endpoint compartilhado de busca/autocomplete (somente leitura).
	 */
	public static function isSharedLookupRequest(\Illuminate\Http\Request $request): bool
	{
		$cleanPath = trim($request->path(), '/');

		if ($request->isMethod('GET')) {
			return \Illuminate\Support\Str::is([
				'api/users/search*',
				'api/users/*',
				'users/search*',
				'api/clientes/search*',
				'api/clientes/buscar-por-cpf*',
				'api/items/search*',
				'admin/pedido/buscar-item*',
				'pedidos/buscar-clientes*',
				'admin/avaliacoes/search-fornecedor-cliente*',
				'admin/sacolinhas/buscar-item*',
				'admin/sacolinhas-admin/search-item*',
				'admin/financeiro/search/*',
				'admin/financeiro/conciliacao/buscar-*',
			], $cleanPath);
		}

		return false;
	}

	/**
	 * Determina se uma requisição representa apenas leitura/consulta (true) ou se exige permissão de edição/escrita (false).
	 */
	public static function isReadActionRequest(\Illuminate\Http\Request $request): bool
	{
		$cleanPath = trim($request->path(), '/');

		if (in_array($request->method(), ['GET', 'HEAD', 'OPTIONS'], true)) {
			// OAuth connect/disconnect e páginas de criação pura exigem permissão de escrita
			if (\Illuminate\Support\Str::is([
				'admin/youtube/connect*',
				'admin/youtube/disconnect*',
				'admin/tiktok/connect*',
				'admin/tiktok/disconnect*',
				'admin/instagram/connect*',
				'admin/instagram/disconnect*',
				'admin/melhor-envio/auth*',
				'*/create',
			], $cleanPath)) {
				return false;
			}

			return true;
		}

		// Algumas rotas POST são apenas consultas, carregamentos de listagem, impressões ou simulações
		if ($request->isMethod('POST')) {
			if (\Illuminate\Support\Str::is([
				'pedidos/itens-sacolinha*',
				'pedidos/itens-pedido*',
				'pedidos/imprimir-sacolinha*',
				'pedidos/imprimir-pedido*',
				'admin/pedido/*/frete-opcoes*',
				'api/frete/simular*',
				'admin/clientes/check-email*',
				'image-groups/buscar-codigo*',
				'severino/ask*',
			], $cleanPath)) {
				return true;
			}
		}

		return false;
	}
}
