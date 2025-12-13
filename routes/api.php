<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\OrderController;

Route::post('/login', [ProfileController::class, 'login']);
Route::post('/register', [ProfileController::class, 'register']);

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [ProfileController::class, 'logout']);
    Route::get('/profile', [ProfileController::class, 'index']);
});

Route::middleware(['auth:sanctum', 'append.user.id'])->group(function () {
    Route::get('/orders', [OrderController::class, 'getAll']);
    Route::post('/orders', [OrderController::class, 'create']);
    Route::post('/orders/{id}/cancel', [OrderController::class, 'cancel']);
});

