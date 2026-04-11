<?php
    // routes/api.php

use App\Http\Controllers\ImportItemsController;
use Illuminate\Support\Facades\Route;

Route::post('/import-items', [ImportItemsController::class, 'import']);