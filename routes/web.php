<?php

use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\LandingController;
use App\Http\Controllers\PromptController;
use Illuminate\Support\Facades\Route;

// Landing & builder
Route::get('/',        [LandingController::class, 'index']);
Route::get('/builder', [DashboardController::class, 'index']);

// Auth
Route::middleware('guest')->group(function () {
    Route::get('/login',     [AuthController::class, 'showLogin']);
    Route::post('/login',    [AuthController::class, 'login']);
    Route::get('/register',  [AuthController::class, 'showRegister']);
    Route::post('/register', [AuthController::class, 'register']);
});

Route::post('/logout', [AuthController::class, 'logout'])->middleware('auth');

// Saved prompts (auth required)
Route::middleware('auth')->group(function () {
    Route::get('/my-prompts',            [PromptController::class, 'index']);
    Route::post('/prompts',              [PromptController::class, 'store']);
    Route::patch('/prompts/{prompt}',    [PromptController::class, 'update']);
    Route::delete('/prompts/{prompt}',   [PromptController::class, 'destroy']);
});
