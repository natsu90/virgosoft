<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ProfileController;

Route::post('/login', [ProfileController::class, 'login']);
Route::post('/register', [ProfileController::class, 'register']);

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [ProfileController::class, 'logout']);
    Route::get('/profile', [ProfileController::class, 'index']);
});
