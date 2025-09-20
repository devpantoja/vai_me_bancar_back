<?php

namespace App\Http\Controllers;

use App\Services\AsaasService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class AsaasWebhookController extends Controller
{
    private AsaasService $asaasService;

    public function __construct(AsaasService $asaasService)
    {
        $this->asaasService = $asaasService;
    }

    /**
     * Receber webhooks do Asaas
     */
    public function handleWebhook(Request $request)
    {
        try {
            // Validar token do webhook
            $webhookToken = $request->header('asaas-access-token');
            if (!$this->asaasService->validateWebhook($webhookToken)) {
                Log::warning('Webhook do Asaas com token inválido', [
                    'token' => $webhookToken,
                    'ip' => $request->ip()
                ]);
                return response()->json(['error' => 'Token inválido'], 401);
            }

            $eventData = $request->all();
            
            Log::info('Webhook recebido do Asaas', [
                'event_data' => $eventData,
                'headers' => $request->headers->all()
            ]);

            // Processar o evento
            $this->asaasService->processWebhookEvent($eventData);

            return response()->json(['status' => 'success'], 200);

        } catch (\Exception $e) {
            Log::error('Erro ao processar webhook do Asaas', [
                'error' => $e->getMessage(),
                'request_data' => $request->all()
            ]);

            return response()->json(['error' => 'Erro interno'], 500);
        }
    }

    /**
     * Testar webhook (apenas para desenvolvimento)
     */
    public function testWebhook(Request $request)
    {
        if (config('app.env') !== 'local') {
            return response()->json(['error' => 'Não disponível em produção'], 403);
        }

        $testData = [
            'event' => 'PAYMENT_CONFIRMED',
            'payment' => [
                'id' => 'pay_test_123',
                'status' => 'CONFIRMED',
                'value' => 100.00,
                'customer' => 'cus_test_123'
            ]
        ];

        $this->asaasService->processWebhookEvent($testData);

        return response()->json(['message' => 'Webhook de teste processado']);
    }
}
