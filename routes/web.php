<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Http\Request;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\ItemController;
use App\Http\Controllers\SacolinhaController;
use App\Http\Controllers\Admin\AdminSacolinhaController;
use App\Http\Controllers\LiveController;
use App\Http\Controllers\ClienteController;
use App\Http\Controllers\PedidoController;
use App\Http\Controllers\ContaCorrenteController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\InventarioController;
use App\Http\Controllers\ImportItemsController;
use App\Http\Controllers\WhatsappController;
use App\Http\Controllers\TwilioOutController;
use App\Http\Controllers\LiveWhatsAppController;
use App\Http\Controllers\Admin\AdminPedidoController;
use App\Http\Controllers\Admin\ChatController;
use App\Http\Controllers\PortalClienteController;
use App\Http\Controllers\SacolinhaVencidaController;
use App\Http\Controllers\Admin\ClubeMensalidadesController;
use App\Http\Controllers\ClassificacaoFinanceiraController;
use App\Http\Controllers\LojaController;
use App\Http\Controllers\OrphanPhotoController;
use App\Http\Controllers\ImageGroupController;
use App\Http\Controllers\ItemMediaController;
//use App\Http\Controllers\GeminiBatchImageEditController;
//use App\Http\Controllers\ImagemBatchController;
use App\Http\Controllers\PontuacoesController;
use App\Http\Controllers\Admin\GruposController;  

// Webhook MercadoPago
Route::post('/mercadopago/webhook', [\App\Http\Controllers\MercadoPagoController::class, 'webhook'])
    ->withoutMiddleware([\Illuminate\Foundation\Http\Middleware\VerifyCsrfToken::class]);

// Webhook Melhor Envio (URL /api/webhooks/melhorenvio configurada pelo usuário)
Route::post('/api/webhooks/melhorenvio', [\App\Http\Controllers\Api\MelhorEnvioWebhookController::class, 'handle'])
    ->withoutMiddleware([\Illuminate\Foundation\Http\Middleware\VerifyCsrfToken::class]);

// Webhook Banco Inter Pix
Route::post('/api/webhooks/inter', [\App\Http\Controllers\Api\InterWebhookController::class, 'handle'])
    ->withoutMiddleware([\Illuminate\Foundation\Http\Middleware\VerifyCsrfToken::class]);


// Rota que vai disparar o envio das imagens
//Route::get('/teste-enviar-gemini', [App\Http\Controllers\ImagemBatchController::class, 'enviarParaEdicao']);


//Editar Imagem Batch
//Route::post('/image-edits/batch', [GeminiBatchImageEditController::class, 'submit']);
//Route::get('/image-edits/batch/{batchId}', [GeminiBatchImageEditController::class, 'show']);
//Route::get('/image-edits/batch/{batchId}/status', [GeminiBatchImageEditController::class, 'status']);



// ===== ROTAS DE AUTENTICAÇÃO =====
Route::get('/login', [LoginController::class, 'showLoginForm'])->name('login');
Route::post('/login', [LoginController::class, 'login']);
Route::post('/logout', [LoginController::class, 'logout'])->name('logout');

Route::get('/register', function () {
    return view('auth.register');
})->name('register');

Route::post('/register', function (Request $request) {
    $request->validate([
    ]);

    $user = \App\Models\User::create([
        'name' => $request->name,
        'email' => $request->email,
        'password' => Hash::make($request->password),
    ]);

    Auth::login($user);
    return redirect('/dashboard');
});

Route::get('/password/reset', function () {
    return view('auth.forgot-password');
})->name('password.request');

// ===== ROTAS PÚBLICAS =====

Route::get('/', function () {
    return view('welcome');
});

// Rota pública de pagamento direto (sem login via Token)
Route::get('/checkout/pagamento/{token}', [App\Http\Controllers\Portal\CheckoutController::class, 'pagamentoToken'])->name('portal.checkout.pagamento');

// =====================================================================
// ===== MÓDULO FINANCEIRO (Regime de Competência + Caixa) =============
// =====================================================================
use App\Http\Controllers\Financeiro\FinanceiroDashboardController;

// Route para o dashboard financeiro acessível pelo link do menu principal
Route::get('admin/financeiro', [FinanceiroDashboardController::class, 'index'])->name('admin.financeiro.index')->middleware(['auth', 'check.admin']);
use App\Http\Controllers\Financeiro\LancamentoController;
use App\Http\Controllers\Financeiro\ContaBancariaController;
use App\Http\Controllers\Financeiro\OrcamentoController;
use App\Http\Controllers\Financeiro\ConciliacaoController;
use App\Http\Controllers\Financeiro\PessoaController;
use App\Http\Controllers\Financeiro\MovimentacaoController;

Route::middleware(['auth', 'check.admin'])->prefix('admin/financeiro')->name('financeiro.')->group(function () {
    // Dashboard
    Route::get('/', [FinanceiroDashboardController::class, 'index'])->name('dashboard');

    // Contas Bancárias
    Route::prefix('contas')->name('contas.')->group(function () {
        Route::get('/',                          [ContaBancariaController::class, 'index'])->name('index');
        Route::post('/',                         [ContaBancariaController::class, 'store'])->name('store');
        Route::put('/{contaBancaria}',           [ContaBancariaController::class, 'update'])->name('update');
        Route::delete('/{contaBancaria}',        [ContaBancariaController::class, 'destroy'])->name('destroy');
        Route::get('/{contaBancaria}/extrato',   [ContaBancariaController::class, 'extrato'])->name('extrato');
    });

    // Lançamentos
    Route::prefix('lancamentos')->name('lancamentos.')->group(function () {
        Route::get('/',                          [LancamentoController::class, 'index'])->name('index');
        Route::post('/',                         [LancamentoController::class, 'store'])->name('store');
        Route::get('/{lancamento}',              [LancamentoController::class, 'show'])->name('show');
        Route::put('/{lancamento}',              [LancamentoController::class, 'update'])->name('update');
        Route::delete('/{lancamento}',           [LancamentoController::class, 'destroy'])->name('destroy');
        Route::post('/{lancamento}/baixar',      [LancamentoController::class, 'baixar'])->name('baixar');
        Route::post('/{lancamento}/cancelar',    [LancamentoController::class, 'cancelar'])->name('cancelar');
    });

    // AJAX Selects
    Route::get('/search/pessoas',        [LancamentoController::class, 'searchPessoas'])->name('search.pessoas');
    Route::get('/search/classificacoes', [LancamentoController::class, 'searchClassificacoes'])->name('search.classificacoes');

    Route::get('/movimentacoes', [MovimentacaoController::class, 'index'])->name('movimentacoes.index');
    Route::post('/movimentacoes/transferir', [MovimentacaoController::class, 'transferir'])->name('movimentacoes.transferir');
    Route::put('/movimentacoes/{movimentacao}', [MovimentacaoController::class, 'update'])->name('movimentacoes.update');
    Route::delete('/movimentacoes/{movimentacao}', [MovimentacaoController::class, 'destroy'])->name('movimentacoes.destroy');

    Route::prefix('orcamento')->name('orcamento.')->group(function () {
        Route::get('/',    [OrcamentoController::class, 'index'])->name('index');
        Route::post('/',   [OrcamentoController::class, 'upsert'])->name('upsert');
        Route::post('/replicar', [OrcamentoController::class, 'replicar'])->name('replicar');
    });

    // Conciliação
    Route::prefix('conciliacao')->name('conciliacao.')->group(function () {
        Route::get('/',                [ConciliacaoController::class, 'index'])->name('index');
        Route::post('/vincular',       [ConciliacaoController::class, 'vincular'])->name('vincular');
        Route::post('/vincular-multiplos', [ConciliacaoController::class, 'vincularMultiplos'])->name('vincular-multiplos');
        Route::post('/sincronizar-mp', [ConciliacaoController::class, 'sincronizarMp'])->name('sincronizar-mp');
        Route::post('/sincronizar-inter', [ConciliacaoController::class, 'sincronizarInter'])->name('sincronizar-inter');
        Route::post('/importar',       [ConciliacaoController::class, 'importarOfx'])->name('importar');
        Route::post('/criar-rapido',   [ConciliacaoController::class, 'criarRapido'])->name('criar-rapido');
        Route::get('/buscar-pessoas',  [ConciliacaoController::class, 'buscarPessoas'])->name('buscar-pessoas');
        Route::get('/buscar-lancamentos', [ConciliacaoController::class, 'buscarLancamentos'])->name('buscar-lancamentos');
        Route::get('/get-sugestao-pessoa/{transacao}', [ConciliacaoController::class, 'getSugestaoPessoa'])->name('get-sugestao-pessoa');
        Route::post('/{transacao}/ignorar', [ConciliacaoController::class, 'ignorar'])->name('ignorar');
    });

    // Pessoas (Contatos)
    Route::prefix('pessoas')->name('pessoas.')->group(function () {
        Route::get('/',              [PessoaController::class, 'index'])->name('index');
        Route::post('/',             [PessoaController::class, 'store'])->name('store');
        Route::get('/{pessoa}',      [PessoaController::class, 'show'])->name('show');
        Route::put('/{pessoa}',      [PessoaController::class, 'update'])->name('update');
        Route::delete('/{pessoa}',   [PessoaController::class, 'destroy'])->name('destroy');
    });
});

// Rota para o Módulo de Conta Corrente (Antigo Financeiro)
Route::middleware(['auth', 'check.admin'])->prefix('admin/financeiro')->group(function () {
    Route::post('conta-corrente/gerar-recarga', [\App\Http\Controllers\ContaCorrenteController::class, 'gerarRecarga'])
        ->name('admin.conta_corrente.gerar_recarga');
});

Route::middleware(['auth', 'check.admin'])->prefix('admin/financeiro')->resource('conta-corrente', \App\Http\Controllers\ContaCorrenteController::class)->names([
    'index'   => 'admin.conta_corrente.index',
    'create'  => 'admin.conta_corrente.create',
    'store'   => 'admin.conta_corrente.store',
    'show'    => 'admin.conta_corrente.show',
    'edit'    => 'admin.conta_corrente.edit',
    'update'  => 'admin.conta_corrente.update',
    'destroy' => 'admin.conta_corrente.destroy',
])->parameters([
    'conta-corrente' => 'financeiro'
]);

// ===== ROTAS PROTEGIDAS ORIGINAIS =====
Route::middleware('auth')->group(function () {
    
    // Todas as rotas administrativas originais agora exigem check.admin
    Route::middleware('check.admin')->group(function () {
        // Dashboard
        Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    Route::get('/home', function () {
        return redirect('/dashboard');
    })->name('home');

    // ===== CLIENTES =====
    Route::resource('clientes', ClienteController::class);
    
    // Ação específica para bloquear/desbloquear cliente
    Route::patch('clientes/{cliente}/toggle-block', [ClienteController::class, 'toggleBlock'])
         ->name('clientes.toggle_block');
    
    // Rota para exportar clientes (opcional)
    Route::get('clientes/export/{format?}', [ClienteController::class, 'export'])
         ->name('clientes.export');

    // ===== ITEMS =====
    Route::resource('items', ItemController::class);
	Route::get('/inventario', [ItemController::class, 'inventario'])->name('inventario');



    // Admin Items
    Route::prefix('admin')->group(function () {

        // ===== ADMIN - UPDATE STATUS (DEVE VIR ANTES DO RESOURCE!) =====
        Route::get("items/update-status", [ItemController::class, "updateStatusPage"])
             ->name("admin.items.update-status");
        Route::post("items/update-status", [ItemController::class, "updateStatusApi"])
             ->name("admin.items.update-status.api");

        // Rotas customizadas de Pedido
        Route::get('pedido/{pedido}/pdf', [AdminPedidoController::class, 'pdf'])->name('admin.pedido.pdf');
        // Rota customizada para etiqueta de sacolinha (ID=0) - inserir aqui**
        Route::get('items/etiqueta', [ItemController::class, 'etiqueta'])
            ->name('admin.items.etiqueta');		
 		Route::post('/items/{item}/media/reorder', [ItemMediaController::class, 'reorder'])
			->name('items.media.reorder');

        Route::resource('items', ItemController::class)->names([
            'index' => 'admin.items.index',
            'create' => 'admin.items.create',
            'store' => 'admin.items.store',
            'show' => 'admin.items.show',
            'edit' => 'admin.items.edit',
            'update' => 'admin.items.update',
            'destroy' => 'admin.items.destroy',
        ]);


        // ===== ADMIN CLIENTES =====
        Route::resource('clientes', ClienteController::class)->names([
            'index' => 'admin.clientes.index',
            'create' => 'admin.clientes.create',
            'store' => 'admin.clientes.store',
            'show' => 'admin.clientes.show',
            'edit' => 'admin.clientes.edit',
            'update' => 'admin.clientes.update',
            'destroy' => 'admin.clientes.destroy',
        ]);
		
		//ROTAS DE financeiro
/*
		Route::resource('financeiro', ContaCorrenteController::class)->names([
			'index' => 'admin.financeiro.index',
			'create' => 'admin.financeiro.create',
			'store' => 'admin.financeiro.store',
			'show' => 'admin.financeiro.show',
			'edit' => 'admin.financeiro.edit',
			'update' => 'admin.financeiro.update',
			'destroy' => 'admin.financeiro.destroy',
		]);	
*/
		
		//classificacao financeira
		Route::resource('classificacao_financeira', ClassificacaoFinanceiraController::class)->names([
			'index' => 'classificacao_financeira.index',
			'create' => 'classificacao_financeira.create',
			'store' => 'classificacao_financeira.store',
			'show' => 'classificacao_financeira.show',
			'edit' => 'classificacao_financeira.edit',
			'update' => 'classificacao_financeira.update',
			'destroy' => 'classificacao_financeira.destroy',
		]);	
		
        // Busca de item para modal (sem filtro de status) — deve vir ANTES do resource para não conflitar com {pedido}
        Route::get('pedido/buscar-item', [AdminPedidoController::class, 'buscarItem'])->name('admin.pedido.buscarItem');
        
        // Melhor Envio (Saldo) - deve vir ANTES do resource para não conflitar com {pedido}
        Route::get('pedido/melhorenvio-saldo', [AdminPedidoController::class, 'saldoMelhorEnvio'])->name('admin.pedido.saldoMelhorEnvio');

        // Melhor Envio OAuth2
        Route::get('melhor-envio/auth', [\App\Http\Controllers\Admin\MelhorEnvioAuthController::class, 'redirect'])->name('admin.melhor-envio.auth');
        Route::get('melhor-envio/callback', [\App\Http\Controllers\Admin\MelhorEnvioAuthController::class, 'callback'])->name('admin.melhor-envio.callback');
        Route::post('melhor-envio/disconnect', [\App\Http\Controllers\Admin\MelhorEnvioAuthController::class, 'disconnect'])->name('admin.melhor-envio.disconnect');

        Route::resource('pedido', AdminPedidoController::class)->names([
			'index' => 'admin.pedido.index',
			'create' => 'admin.pedido.create',
			'store' => 'admin.pedido.store',
			'show' => 'admin.pedido.show',
			'edit' => 'admin.pedido.edit',
			'update' => 'admin.pedido.update',
			'destroy' => 'admin.pedido.destroy',
		]);

        // Melhor Envio
        Route::post('pedido/{pedido}/frete-opcoes', [AdminPedidoController::class, 'freteOpcoes'])->name('admin.pedido.freteOpcoes');
        Route::post('pedido/{pedido}/gerar-etiqueta', [AdminPedidoController::class, 'gerarEtiqueta'])->name('admin.pedido.gerarEtiqueta');
        Route::post('pedido/{pedido}/sincronizar-melhorenvio', [AdminPedidoController::class, 'sincronizarMelhorEnvio'])->name('admin.pedido.sincronizarMelhorEnvio');

        // Itens do pedido (adicionar / remover)
        Route::post('pedido/{pedido}/adicionar-item', [AdminPedidoController::class, 'adicionarItem'])->name('admin.pedido.adicionarItem');
        Route::delete('pedido/{pedido}/remover-item/{itemId}', [AdminPedidoController::class, 'removerItem'])->name('admin.pedido.removerItem');
        Route::post('pedido/{pedido}/devolucao', [AdminPedidoController::class, 'devolucao'])->name('admin.pedido.devolucao');

        // ===== ADMIN CATEGORIAS =====
        Route::resource('categorias', \App\Http\Controllers\CategoriaController::class)->names([
            'index' => 'admin.categorias.index',
            'create' => 'admin.categorias.create',
            'store' => 'admin.categorias.store',
            'edit' => 'admin.categorias.edit',
            'update' => 'admin.categorias.update',
            'destroy' => 'admin.categorias.destroy',
        ]);
		
        // Admin - Toggle bloqueio de clientes
        Route::patch('clientes/{cliente}/toggle-block', [ClienteController::class, 'toggleBlock'])
             ->name('admin.clientes.toggle_block');
             
        // Admin - Relatórios de clientes
        Route::get('clientes/relatorios/dashboard', [ClienteController::class, 'relatorios'])
             ->name('admin.clientes.relatorios');
			 
			 
			// Rota para atribuição de chat
		Route::post('/chat/api/assign', [ChatController::class, 'assignConversation'])->name('admin.chat.api.assign');
		Route::get('/chat/api/admins', [ChatController::class, 'getAdmins'])->name('admin.chat.api.admins');
		Route::get('/chat/api/admins', [ChatController::class, 'getAdmins'])
		->name('admin.chat.api.admins');
		Route::post('chat/api/assign', [ChatController::class, 'assignConversation'])
		->name('admin.chat.api.assign');	 
    });

    // ===== ADMIN SACOLINHAS (NOVO) =====

    // ===== SACOLINHAS (BAGS) =====
    // Página principal das sacolinhas
    Route::get('/sacolinhas', [SacolinhaController::class, 'index'])->name('sacolinhas.index');
    Route::get('/bags', [SacolinhaController::class, 'index'])->name('bags.index'); // Alias para compatibilidade
    
    // Operações das sacolinhas
    Route::post('/sacolinhas', [SacolinhaController::class, 'store'])->name('sacolinhas.store');
	// Em routes/web.php
    Route::post('/sacolinhas/adicionar-item', [SacolinhaController::class, 'adicionarItemSacola'])->name('sacolinhas.adicionar_item');

    // ===== LIVES =====
    // API para Lives (AJAX)
    Route::get('/lives', [LiveController::class, 'index'])->name('lives.api.index');
    Route::post('/lives', [LiveController::class, 'store'])->name('lives.api.store');
    //Route::delete('/lives/{id}', [LiveController::class, 'destroy'])->name('lives.api.destroy');
	

    // ===== API ROUTES =====

    Route::prefix('api')->group(function () {
		// Busca de usuários e itens
		Route::get('/users/search', [ClienteController::class, 'search'])->name('api.users.search');
		Route::get('/items/search', [ItemController::class, 'search'])->name('api.items.search');
        // ===== API CLIENTES =====
        Route::get('/clientes/search', [ClienteController::class, 'search'])->name('api.clientes.search');
        Route::get('/clientes/buscar-por-cpf/{cpf}', [ClienteController::class, 'buscarPorCpf'])->name('api.clientes.buscar_cpf');
        Route::get('/clientes/estatisticas', [ClienteController::class, 'estatisticas'])->name('api.clientes.estatisticas');
        Route::get('/clientes/{cliente}/historico', [ClienteController::class, 'historico'])->name('api.clientes.historico');
        
        // API das Sacolinhas
        Route::get('/sacolinhas/live/{liveId?}', [SacolinhaController::class, 'getBagsByLive'])->name('api.sacolinhas.live');
        Route::delete('/sacolinhas/remove', [SacolinhaController::class, 'removeItems'])->name('api.sacolinhas.remove');
	});	
	


    // ===== ROTAS DE PEDIDOS =====
    Route::get('/pedidos', [PedidoController::class, 'index'])->name('pedidos.index');
    Route::post('/pedidos/criar-pedido', [PedidoController::class, 'criarPedido'])->name('pedidos.criar');
    Route::post('/pedidos/atualizar-status', [PedidoController::class, 'atualizarStatusPedido'])->name('pedidos.atualizar-status');

    // Rotas para itens de sacolinha/pedido (AJAX)
    Route::post('/pedidos/itens-sacolinha', [PedidoController::class, 'itensSacolinha']); // POST para carregar sacolinha
    Route::post('/pedidos/itens-pedido', [PedidoController::class, 'itensPedido']); // POST para carregar pedido

    // Rotas de Movimentação de Itens
    Route::post('/pedidos/mover-para-pedido', [PedidoController::class, 'moverParaPedido']);
    Route::post('/pedidos/devolver-para-sacolinha', [PedidoController::class, 'devolverParaSacolinha']);

    // Rota para registrar débito (se for parte do fluxo de pedido)
    Route::post('/pedidos/registrar-debito-conclusao', [ContaCorrenteController::class, 'registrarDebitoConclusao']);

    // ROTAS DE IMPRESSÃO (estas já estavam aqui, apenas confirmando)
    Route::post('/pedidos/imprimir-sacolinha', [PedidoController::class, 'imprimirSacolinha'])->name('pedidos.imprimir.sacolinha');
    Route::post('/pedidos/imprimir-pedido', [PedidoController::class, 'imprimirPedido'])->name('pedidos.imprimir.pedido');

        // Rotas de busca Pedido cliente
        Route::get('/pedidos/buscar-clientes', [PedidoController::class, 'buscarClientes']); 
    });

    // ===== FLUXO DE CHECKOUT (FECHAR SACOLINHA) =====
    Route::post('/checkout/iniciar', [App\Http\Controllers\Portal\CheckoutController::class, 'iniciar'])->name('portal.checkout.iniciar');
    Route::get('/checkout/{pedido}', [App\Http\Controllers\Portal\CheckoutController::class, 'show'])->name('portal.checkout.show');
    Route::post('/checkout/{pedido}/cancelar', [App\Http\Controllers\Portal\CheckoutController::class, 'cancelar'])->name('portal.checkout.cancelar');
    Route::post('/checkout/{pedido}/confirmar', [App\Http\Controllers\Portal\CheckoutController::class, 'finalizarRevision'])->name('portal.checkout.confirmar');

});

Route::post('/loja/adicionar-item', [LojaController::class, 'adicionarItemSacola'])->name('loja.adicionar_item');

Route::get('/api/lives/all', [App\Http\Controllers\LiveController::class, 'getAllLives'])->name('api.lives.all');

Route::get('admin/sacolinhas', [LiveController::class, 'showLiveBagsOverview'])->name('admin.sacolinhas.index')->middleware(['auth', 'check.admin']);


Route::get('/users/search', [App\Http\Controllers\LiveController::class, 'search']);
Route::post('/users/quick-create', [App\Http\Controllers\LiveController::class, 'quickCreate']);

Route::post('admin/clientes/check-email', [ClienteController::class, 'checkEmail'])
    ->name('admin.clientes.check-email')
    ->middleware(['auth', 'check.admin']);
	
Route::get('/sacolinhas/live/{liveId}', [SacolinhaController::class, 'getSacolinhasByLive']);
Route::put('/items/{itemId}/status', [SacolinhaController::class, 'updateItemStatus']);

// ROTA TEMPORÁRIA PARA TESTE - adicionar no final do web.php
Route::patch('/api/items/{itemId}/status', [SacolinhaController::class, 'updateItemStatus'])->name('api.items.update-status');

// ADICIONAR ESTA LINHA - Rota GET para consultar status do item
Route::get('/api/items/{itemId}/status', [SacolinhaController::class, 'getItemStatus'])->name('api.items.get-status');

// ADICIONAR esta rota que usa o método correto
Route::get('/api/sacolinhas/by-live/{liveId}', [SacolinhaController::class, 'getSacolinhasByLive']);

//------------------------------------------------------------------------------------------------------------------------------------------------------
// ===== ROTAS DE BUSCA (SEM AUTH - públicas) =====
Route::prefix('api')->group(function () {
    Route::get('/users/search', [ClienteController::class, 'search'])->name('api.users.search');
    Route::get('/users/{id}', [\App\Http\Controllers\Api\UserSearchController::class, 'getUser']);
    Route::get('/items/search', [ItemController::class, 'search']);
});

// ===== ROTAS DE SACOLINHAS (COM AUTH) =====
Route::prefix('api')->middleware('auth')->group(function () {
	// Buscar limites do cliente
    Route::get('/sacolinhas/limites', [SacolinhaController::class, 'getLimites'])->name('sacolinhas.limites');		
	
    // Consultar sacolinha de um cliente específico
    Route::get('/sacolinhas/{userId}', [SacolinhaController::class, 'consultarSacolinhaCliente']);
    
    // Obter totais da sacolinha
    Route::get('/sacolinhas/{userId}/totais', [SacolinhaController::class, 'obterTotalSacola']);
    
    // Adicionar item à sacolinha
	Route::post('/sacolinhas/add', [SacolinhaController::class, 'adicionarItemSacola'])->name('sacolinhas.add');
    
    // Atualizar quantidade de item
    Route::put('/sacolinhas/{sacolinhaId}/update-quantity', [SacolinhaController::class, 'atualizarQuantidadeItem']);
    
    // Remover item da sacolinha
    Route::delete('/sacolinhas/{sacolinhaId}', [SacolinhaController::class, 'removerItemSacola']);

    // Simular Frete da Sacolinha
    Route::post('/frete/simular', [SacolinhaController::class, 'simularFrete'])->name('api.frete.simular');
	
});

// Rotas para exibir a página de consulta de sacolinhas
Route::middleware('auth')->group(function () {
    Route::get('/sacolinhas/consultar', [SacolinhaController::class, 'consultarView'])->name('sacolinhas.consultar');
});
//Rota para procurar a sacolinha pelo codigo do item
Route::get('/admin/sacolinhas/qrcode-scanner', [InventarioController::class, 'index'])
    ->name('admin.sacolinhas.qrcode.scanner')
    ->middleware(['auth', 'check.admin']);  



    // Rota para a importação (sem autenticação, menos seguro)
Route::post('/import-items', [ImportItemsController::class, 'import'])->withoutMiddleware(['web', 'csrf']);

Route::post('/twilio-in', [WhatsappController::class, 'in'])->withoutMiddleware([VerifyCsrfToken::class]);
Route::post('/twilio-out', [TwilioOutController::class, 'send'])->withoutMiddleware([VerifyCsrfToken::class]);
Route::post('/twilio-status', [ChatController::class, 'twilioStatus'])->withoutMiddleware([VerifyCsrfToken::class]);




Route::post('/admin/live/{liveId}/send-whatsapp', [LiveWhatsAppController::class, 'send'])
    ->withoutMiddleware([VerifyCsrfToken::class]);
//Encerrar live
Route::delete('lives/{id}', [LiveController::class, 'destroy'])->withoutMiddleware([VerifyCsrfToken::class]);	
//Msg para cliente individual de uma live
Route::post('/lives/{liveId}/sacolas/{userId}/whatsapp/first', [LiveController::class, 'sendFirstToClient']);	



// (Opcional) Agrupa rotas do admin para proteger com senha depois
/*Route::prefix('admin')->middleware(['auth'])->group(function () {
    
    // Página principal do chat
    Route::get('/chat', [ChatController::class, 'index'])->name('admin.chat.index');

    // API para o frontend
    Route::get('/chat/api/conversations', [ChatController::class, 'getConversations'])->name('admin.chat.api.conversations');
    Route::get('/chat/api/messages/{userId}', [ChatController::class, 'getMessages'])->name('admin.chat.api.messages');
    Route::post('/chat/api/send', [ChatController::class, 'sendMessage'])->name('admin.chat.api.send');
});
*/
// (Opcional) Agrupa rotas do admin para proteger com senha depois
Route::prefix('admin')->middleware(['auth', 'check.admin'])->group(function () {
    
    // Página principal do chat
    Route::get('/chat', [ChatController::class, 'index'])->name('admin.chat.index');

    // API para o frontend (sem CSRF para evitar 419)
    Route::get('/chat/api/conversations', [ChatController::class, 'getConversations'])->name('admin.chat.api.conversations');
    Route::get('/chat/api/messages/{userId}', [ChatController::class, 'getMessages'])->name('admin.chat.api.messages');
    Route::post('/chat/api/send', [ChatController::class, 'sendMessage'])->name('admin.chat.api.send');
    
    // NOVA ROTA: Download de mídia (anexos)
    Route::get('/chat/download/{id}', [ChatController::class, 'downloadMedia'])->name('admin.chat.download');
    
})->withoutMiddleware([VerifyCsrfToken::class]);


Route::prefix('admin')->middleware(['auth', 'check.admin'])->group(function () {
    // ✅ NOVAS ROTAS LIVE FINALIZE
    Route::post('/live/{liveId}/finalize', [LiveController::class, 'finalize'])->name('admin.live.finalize');
    Route::get('/live/{liveId}/status', [LiveController::class, 'status'])->name('admin.live.status');
    Route::post('/live/{liveId}/retry', [LiveController::class, 'retryFailed'])->name('admin.live.retry');
});


Route::prefix('admin/whatsapp-dashboard')->middleware(['auth', 'check.admin'])->group(function () {
    Route::get('/', [\App\Http\Controllers\Admin\ChatController::class, 'dashboard'])->name('admin.whatsapp.dashboard');
    Route::get('/api/stats', [\App\Http\Controllers\Admin\ChatController::class, 'getDashboardStats']);
});

Route::post('/admin/whatsapp-dashboard/api/conflicts/{id}/send-msg2', [ChatController::class, 'sendMsg2FromConflict'])
    ->middleware(['auth', 'check.admin'])
    ->withoutMiddleware([\App\Http\Middleware\VerifyCsrfToken::class]);
	
Route::post('/admin/chat/api/mark-read/{userId}', [ChatController::class, 'markMessagesAsRead'])->middleware(['auth', 'check.admin']);


// ============================================
// PORTAL DO CLIENTE (NOVO)
// ============================================
Route::middleware(['auth', 'check.client'])->prefix('portal')->name('portal.')->group(function () {
    
    // Dashboard do cliente
    Route::get('/dashboard', [PortalClienteController::class, 'dashboard'])->name('dashboard');
    
    // Perfil do cliente
    Route::get('/perfil', [PortalClienteController::class, 'perfil'])->name('perfil');
	Route::put('/perfil', [PortalClienteController::class, 'perfilAtualizar'])->name('perfil.atualizar');
    
    // Histórico de pedidos
    Route::get('/pedidos', [PortalClienteController::class, 'pedidos'])->name('pedidos');
    
    // Sacolinha atual
    Route::get('/sacolinha', [PortalClienteController::class, 'sacolinha'])->name('sacolinha');
	
    //Saldo para dashboard
	Route::get('/movimentacao', [PortalClienteController::class, 'movimentacao'])->name('movimentacao');
	
    Route::delete('/sacolinha/{id}', [PortalClienteController::class, 'sacolinhaExcluir'])->name('sacolinha.excluir');

    // Desafios do Clube
    Route::get('/desafios', [PortalClienteController::class, 'desafios'])->name('desafios');

    // Mercado Pago Checkout Transparente
    Route::get('/mercadopago/{pedido}/checkout', [\App\Http\Controllers\MercadoPagoController::class, 'checkout'])->name('mercadopago.checkout');
    Route::post('/mercadopago/{pedido}/process', [\App\Http\Controllers\MercadoPagoController::class, 'processPayment'])->name('mercadopago.process')->withoutMiddleware([\Illuminate\Foundation\Http\Middleware\VerifyCsrfToken::class]);
    Route::get('/mercadopago/checkout-lancamento/{lancamento}', [\App\Http\Controllers\MercadoPagoController::class, 'checkoutLancamento'])->name('mercadopago.checkout_lancamento');
    Route::post('/mercadopago/checkout-lancamento/{lancamento}/process', [\App\Http\Controllers\MercadoPagoController::class, 'processPaymentLancamento'])->name('mercadopago.process_lancamento')->withoutMiddleware([\Illuminate\Foundation\Http\Middleware\VerifyCsrfToken::class]);

    // Banco Inter Pix Checkout
    Route::get('/inter/{pedido}/checkout', [\App\Http\Controllers\Portal\CheckoutController::class, 'checkoutInter'])->name('inter.checkout');
    Route::get('/inter/{pedido}/status', [\App\Http\Controllers\Portal\CheckoutController::class, 'checkInterStatus'])->name('inter.checkout.status');
    Route::get('/inter/checkout-lancamento/{lancamento}', [\App\Http\Controllers\Portal\CheckoutController::class, 'checkoutInterLancamento'])->name('inter.checkout_lancamento');
    Route::get('/inter/checkout-lancamento/{lancamento}/status', [\App\Http\Controllers\Portal\CheckoutController::class, 'checkInterStatusLancamento'])->name('inter.checkout_lancamento.status');

    // Checkout de Lançamento (Seleção)
    Route::get('/checkout-lancamento/{lancamento}', [\App\Http\Controllers\Portal\CheckoutController::class, 'showLancamento'])->name('checkout_lancamento.show');
    Route::post('/checkout-lancamento/{lancamento}/confirmar', [\App\Http\Controllers\Portal\CheckoutController::class, 'confirmarLancamento'])->name('checkout_lancamento.confirmar');

});


//Sacolinhas Vencidas
Route::get('/admin/vencimentos', [SacolinhaVencidaController::class, 'index'])
    ->name('admin.vencimentos')
    ->middleware(['auth', 'check.admin']);

Route::get('/admin/vencimentos/cliente/{user}', [\App\Http\Controllers\Admin\RelatorioVencimentosController::class, 'show'])
    ->name('admin.vencimentos.cliente')
    ->middleware(['auth', 'check.admin']);
	
Route::post('/admin/vencimentos/cliente/{user}/whatsapp', [SacolinhaVencidaController::class, 'sendWhatsappVencidos'])
    ->name('admin.vencimentos.whatsapp.send')
    ->middleware(['auth', 'check.admin']);
	

use App\Http\Controllers\Admin\ClubeDashboardController;
use App\Http\Controllers\Admin\DesafiosController;

//Controle Clube
Route::prefix('admin')->middleware(['auth', 'check.admin'])->group(function () {
    Route::prefix('clube')->name('admin.clube.')->group(function () {
        Route::get('/', [ClubeDashboardController::class, 'index'])->name('dashboard');
        Route::post('/desafio', [ClubeDashboardController::class, 'lancarDesafio'])->name('desafio.lancar');
        Route::post('/pagamento', [ClubeDashboardController::class, 'registrarPagamento'])->name('pagamento.registrar');
        Route::post('/mudar-grupo', [ClubeDashboardController::class, 'mudarGrupo'])->name('mudar-grupo');

        Route::get('mensalidades/registrar', [ClubeMensalidadesController::class, 'create'])
            ->name('mensalidades.create');

        Route::post('mensalidades/registrar', [ClubeMensalidadesController::class, 'store'])
            ->name('mensalidades.store');

        // CRUD de Desafios
        Route::resource('desafios', DesafiosController::class)->names([
            'index'   => 'desafios.index',
            'create'  => 'desafios.create',
            'store'   => 'desafios.store',
            'edit'    => 'desafios.edit',
            'update'  => 'desafios.update',
            'destroy' => 'desafios.destroy',
        ]);
    });
});



//Game
Route::get('/dashboard-pontuacoes', [PontuacoesController::class, 'dashboard'])->middleware('auth')->name('portal.ranking');







//Devolução pedido
Route::post('/admin/pedido/{pedido}/devolucao', [\App\Http\Controllers\Admin\AdminPedidoController::class, 'devolucao'])
    ->name('admin.pedido.devolucao')
    ->middleware(['auth', 'check.admin']);

//Adicionar item ao pedido
Route::post('/admin/pedido/{pedido}/adicionar-item', [\App\Http\Controllers\Admin\AdminPedidoController::class, 'adicionarItem'])
    ->name('admin.pedido.adicionarItem')
    ->middleware(['auth', 'check.admin']);

//Remover item do pedido
Route::delete('/admin/pedido/{pedido}/remover-item/{itemId}', [\App\Http\Controllers\Admin\AdminPedidoController::class, 'removerItem'])
    ->name('admin.pedido.removerItem')
    ->middleware(['auth', 'check.admin']);
	
// rota para deletar uma mídia específica
Route::delete('/items/{item}/medias/{medias}', [App\Http\Controllers\ItemController::class, 'destroyMedia'])
    ->name('items.medias.destroy')
    ->middleware(['auth', 'check.admin']);	
Route::post('/items/{item}/media', [ItemController::class, 'uploadMedia'])
    ->name('items.media.upload')
    ->middleware(['auth', 'check.admin']);
	
//rotas de Loja
Route::get('/loja', [LojaController::class, 'index'])->name('loja.index');
Route::get('/loja/produtos/{item}', [LojaController::class, 'show'])->name('loja.show');

//Edição de imagem do intem
Route::post('/items/{item}/media/ai-edit', [ItemController::class, 'aiEditMedia'])
  ->name('items.media.aiEdit')
  ->middleware(['auth', 'check.admin']);
  
//Edição de várias imagens 
Route::get('upload-batch', function () {
    return view('upload-batch');
})->middleware(['auth', 'check.admin'])->name('upload.batch.form');

Route::post('upload-batch', [App\Http\Controllers\BatchUploadController::class, 'upload'])
    ->middleware(['auth', 'check.admin'])
    ->name('upload.batch');  
	
//imagens sem Item_id	
Route::middleware(['auth', 'check.admin'])->group(function () {
    Route::get('/orphan-photos', [App\Http\Controllers\OrphanPhotoController::class, 'index'])->name('orphan.photos');
    Route::delete('/orphan-photos/{id}', [App\Http\Controllers\OrphanPhotoController::class, 'destroy']);


    //Agrupando imagens
    Route::get('/image-groups', [ImageGroupController::class, 'index'])->name('image-groups.index');
    Route::post('/image-groups/{id}/add-media', [ImageGroupController::class, 'addMedia']);
    Route::post('/image-groups/{id}/remove-media', [ImageGroupController::class, 'removeMedia']);
    Route::post('/image-groups/merge', [ImageGroupController::class, 'merge']);

    Route::post('/image-groups/group-orphans', [ImageGroupController::class, 'groupOrphans'])
        ->name('image-groups.group-orphans');
    Route::post('/image-groups/{group}/edit', [App\Http\Controllers\ImageGroupController::class, 'editGroup'])
        ->name('image-groups.edit');

    Route::post('/image-groups/{group}/transfer', [ImageGroupController::class, 'transferToItem'])
        ->name('image-groups.transfer');	
    Route::post('/image-groups/orphans/delete', [ImageGroupController::class, 'deleteOrphans'])
        ->name('image-groups.orphans.delete');
    Route::post('/image-groups/transfer-orphans', [ImageGroupController::class, 'transferSelectedOrphans'])
        ->name('image-groups.transfer-orphans');
        
    Route::post('/image-groups/buscar-codigo', [App\Http\Controllers\ImageGroupController::class, 'buscarCodigo'])
        ->name('image-groups.buscar-codigo');

    Route::post('/image-groups/orphans/delete-selected', [ImageGroupController::class, 'deleteSelectedOrphans'])
        ->name('image-groups.orphans.delete-selected');
});
	
	
// ===== ADMIN SACOLINHAS (NOVO) =====
Route::middleware(['auth', 'check.admin'])->prefix('admin/sacolinhas-admin')->group(function () {
    Route::get('/', [AdminSacolinhaController::class, 'index'])->name('admin.sacolinha.gestao');
    Route::get('/ver/{user}', [AdminSacolinhaController::class, 'show'])->name('admin.sacolinha.show');
    Route::get('/ver/{user}/pdf', [AdminSacolinhaController::class, 'pdf'])->name('admin.sacolinha.pdf');
    Route::get('/search-item', [AdminSacolinhaController::class, 'searchItem'])->name('admin.sacolinha.searchItem');
    Route::post('/add-item', [AdminSacolinhaController::class, 'addItem'])->name('admin.sacolinha.addItem');
    Route::post('/fechar-sacolinha', [AdminSacolinhaController::class, 'fecharSacolinha'])->name('admin.sacolinha.fechar');
    Route::delete('/remove-item/{id}', [AdminSacolinhaController::class, 'removeItem'])->name('admin.sacolinha.removeItem');
    Route::post('/update-item-price', [AdminSacolinhaController::class, 'updateItemPrice'])->name('admin.sacolinha.updatePrice');
    Route::post('/ver/{user}/autorizar', [AdminSacolinhaController::class, 'autorizarFechamento'])->name('admin.sacolinha.autorizar');
    Route::post('/ver/{user}/revogar', [AdminSacolinhaController::class, 'revogarAutorizacao'])->name('admin.sacolinha.revogar');
});

//Item->Sacolinha
Route::prefix('admin/sacolinhas')->middleware(['auth', 'check.admin'])->group(function () {
    // Página principal do scanner
    Route::get('/qrcode-scanner', [ItemController::class, 'scannerSacolinha'])
        ->name('admin.sacolinhas.qrcode.scanner');

    // API para buscar o item pelo código
    Route::get('/buscar-item', [ItemController::class, 'buscarPorCodigo'])
        ->name('admin.sacolinhas.qrcode.buscar-item');

    // API para atualizar apenas o status
    Route::patch('/item/{item}/status', [ItemController::class, 'atualizarStatusRapido'])
        ->name('admin.sacolinhas.qrcode.atualizar-status');
});

Route::middleware(['auth', 'check.admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::resource('grupos', \App\Http\Controllers\Admin\GruposController::class)->middleware('can:admin');
    Route::post('grupos/{grupo}/membros', [\App\Http\Controllers\Admin\GruposController::class, 'addMembro'])->middleware('can:admin')->name('grupos.addMembro');
    Route::delete('grupos/{grupo}/membros/{user}', [\App\Http\Controllers\Admin\GruposController::class, 'removeMembro'])->middleware('can:admin')->name('grupos.removeMembro');
});
