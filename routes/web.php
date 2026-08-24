<?php

use App\Http\Controllers\ResolverController;
use Illuminate\Support\Facades\Route;

Route::get('/', [ResolverController::class, 'frontpage']);
Route::get('/lookup', [ResolverController::class, 'lookup']);
