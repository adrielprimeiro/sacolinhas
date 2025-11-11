<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Http\Request;
use App\Http\Controllers\UserController;
use App\Http\Controllers\ItemController;
use App\Http\Controllers\SacolinhaController;
use App\Http\Controllers\LiveController;
use App\Http\Controllers\ClienteController; // ← NOVA IMPORTAÇÃO

// ===== ROTAS DE AUTENTICAÇÃO =====

Route::get('/login', function () {
    return view('auth.login');
})->name('login');

Route::post('/login', function (Request $request) {
    $credentials = $request->validate([
        'email' => 'required|email',
        'password' => 'required',
    ]);

    if (Auth::attempt($credentials)) {
        $request->session()->regenerate();
        return redirect()->intended('/dashboard');
    }

    return back()->withErrors([
        'email' => 'As credenciais fornecidas não coincidem com nossos registros.',
    ])->onlyInput('email');
});

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

Route::post('/logout', function (Request $request) {
    Auth::logout();
    $request->session()->invalidate();
    $request->session()->regenerateToken();
    return redirect('/');
})->name('logout');

Route::get('/password/reset', function () {
    return view('auth.forgot-password');
})->name('password.request');

// ===== ROTAS PÚBLICAS =====

Route::get('/', function () {
    return view('welcome');
});

// ===== ROTAS PROTEGIDAS =====

Route::middleware('auth')->group(function () {
    
    // Dashboard
    Route::get('/dashboard', function () {
        return view('dashboard');
    })->name('dashboard');
    
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
    
    // Admin Items
    Route::prefix('admin')->group(function () {
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
        
        // Admin - Toggle bloqueio de clientes
        Route::patch('clientes/{cliente}/toggle-block', [ClienteController::class, 'toggleBlock'])
             ->name('admin.clientes.toggle_block');
             
        // Admin - Relatórios de clientes
        Route::get('clientes/relatorios/dashboard', [ClienteController::class, 'relatorios'])
             ->name('admin.clientes.relatorios');
    });

    // ===== SACOLINHAS (BAGS) =====
    // Página principal das sacolinhas
    Route::get('/sacolinhas', [SacolinhaController::class, 'index'])->name('sacolinhas.index');
    Route::get('/bags', [SacolinhaController::class, 'index'])->name('bags.index'); // Alias para compatibilidade
    
    // Operações das sacolinhas
    Route::post('/sacolinhas', [SacolinhaController::class, 'store'])->name('sacolinhas.store');

    // ===== LIVES =====
    // API para Lives (AJAX)
    Route::get('/lives', [LiveController::class, 'index'])->name('lives.api.index');
    Route::post('/lives', [LiveController::class, 'store'])->name('lives.api.store');
    Route::delete('/lives/{id}', [LiveController::class, 'destroy'])->name('lives.api.destroy');

    // ===== API ROUTES =====
    Route::prefix('api')->group(function () {
        // Busca de usuários e itens
        Route::get('/users/search', [UserController::class, 'search'])->name('api.users.search');
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
});

Route::get('/api/lives/all', [App\Http\Controllers\LiveController::class, 'getAllLives'])->name('api.lives.all');

Route::get('admin/sacolinhas', function () {
    return view('admin.sacolinhas.index');
})->name('admin.sacolinhas.index');


Route::get('/users/search', [App\Http\Controllers\LiveController::class, 'search']);
Route::post('/users/quick-create', [App\Http\Controllers\LiveController::class, 'quickCreate']);

Route::post('admin/clientes/check-email', [ClienteController::class, 'checkEmail'])
    ->name('admin.clientes.check-email');