<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ProjectController;
use App\Http\Controllers\DonateController;
use App\Http\Controllers\AsaasWebhookController;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

// Rotas da API para Projetos
Route::apiResource('projects', ProjectController::class);

// Rotas específicas para projetos com gamificação
Route::get('projects/{project}/info', [ProjectController::class, 'getProjectInfo']);
Route::get('projects/{project}/ranking', [ProjectController::class, 'getDailyRanking']);
Route::get('projects/{project}/fundraising-stats', [ProjectController::class, 'getFundraisingStats']);
Route::post('projects/{project}/troll-message', [ProjectController::class, 'generateTrollMessage']);

// Rotas da API para Doações
Route::apiResource('donates', DonateController::class);

// Rotas específicas para doações de um projeto
Route::get('projects/{project}/donates', [DonateController::class, 'byProject']);

// Rotas para integração com Asaas
Route::post('donates/pix', [DonateController::class, 'createPixPayment']);
Route::post('donates/boleto', [DonateController::class, 'createBoletoPayment']);
Route::get('donates/{donate}/status', [DonateController::class, 'checkPaymentStatus']);

// Webhooks do Asaas
Route::post('webhooks/asaas', [AsaasWebhookController::class, 'handleWebhook']);
Route::post('webhooks/asaas/test', [AsaasWebhookController::class, 'testWebhook']);
