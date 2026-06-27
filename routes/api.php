<?php

use App\Http\Controllers\Api\TagController;
use Illuminate\Support\Facades\Route;

Route::get('/tags',         [TagController::class, 'index']);
Route::get('/tags/resolve', [TagController::class, 'resolve']);
