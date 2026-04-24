<?php
    // routes/api.php

use App\Http\Controllers\ImportItemsController;
use Illuminate\Support\Facades\Route;

Route::post('/import-items', [ImportItemsController::class, 'import']);
Route::post('/webhooks/mercadopago', [\App\Http\Controllers\MercadoPagoController::class, 'webhook'])->name('api.mercadopago.webhook');