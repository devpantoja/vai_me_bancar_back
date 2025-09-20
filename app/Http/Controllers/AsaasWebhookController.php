<?php

namespace App\Http\Controllers;

use App\Services\AsaasService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * @OA\Tag(name="Webhooks")
 */
class AsaasWebhookController extends Controller
{
    private AsaasService $asaasService;

    public function __construct(AsaasService $asaasService)
    {
        $this->asaasService = $asaasService;
    }

    /**
     * @OA\Post(
     *     path="/api/webhooks/asaas",
     *     summary="Receber webhooks do Asaas",
     *     tags={"Webhooks"},
     *     @OA\Parameter(
     *         name="asaas-access-token",
     *         in="header",
     *         required=true,
     *         @OA\Schema(type="string"),
     *         description="Token de autenticação do webhook"
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="event", type="string", example="PAYMENT_CONFIRMED"),
     *             @OA\Property(property="payment", type="object")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Webhook processado com sucesso",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="string", example="success")
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Token inválido",
     *         @OA\JsonContent(
     *             @OA\Property(property="error", type="string", example="Token inválido")
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Erro interno do servidor",
     *         @OA\JsonContent(
     *             @OA\Property(property="error", type="string", example="Erro interno")
     *         )
     *     )
     * )
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
