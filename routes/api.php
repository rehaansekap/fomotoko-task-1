<?php

use App\Http\Controllers\OrderController;
use Illuminate\Support\Facades\Route;

// Rute untuk membuat pesanan baru
Route::post('/orders', [OrderController::class, 'store']);
