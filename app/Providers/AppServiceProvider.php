<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Models\Pedido;
use App\Observers\PedidoObserver;
use Illuminate\Support\Facades\Gate;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
	   Gate::define('admin', function ($user) {
		   return in_array($user->role, ['admin', 'admin_master']) || $user->is_admin;
	   });

       Pedido::observe(PedidoObserver::class);
       \App\Models\Item::observe(\App\Observers\ItemObserver::class);
    }
	public const HOME = '/dashboard';
}
