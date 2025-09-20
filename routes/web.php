<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ProjectController;
use App\Http\Controllers\DonateController;

Route::get('/', function () {
    return view('welcome');
});

// Rotas da API para Projetos
Route::prefix('api')->group(function () {
    Route::apiResource('projects', ProjectController::class);
    Route::apiResource('donates', DonateController::class);
});
