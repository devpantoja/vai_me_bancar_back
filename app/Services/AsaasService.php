<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use App\Models\Donate;

class AsaasService
{
    private string $apiKey;
    private string $baseUrl;
    private string $environment;

    public function __construct()
    {
        $this->apiKey = config('services.asaas.api_key');
        $this->baseUrl = config('services.asaas.base_url');
        $this->environment = config('services.asaas.environment');
    }

    /**
     * Criar um cliente no Asaas
     */
    public function createCustomer(array $customerData): array
    {
        $response = Http::withHeaders([
            'access_token' => $this->apiKey,
            'Content-Type' => 'application/json',
        ])->post($this->baseUrl . '/customers', $customerData);

        if ($response->successful()) {
            return $response->json();
        }

        Log::error('Erro ao criar cliente no Asaas', [
            'status' => $response->status(),
            'response' => $response->body()
        ]);

        throw new \Exception('Erro ao criar cliente no Asaas: ' . $response->body());
    }

    /**
     * Criar uma cobrança no Asaas
     */
    public function createPayment(array $paymentData): array
    {
        $response = Http::withHeaders([
            'access_token' => $this->apiKey,
            'Content-Type' => 'application/json',
        ])->post($this->baseUrl . '/payments', $paymentData);

        if ($response->successful()) {
            return $response->json();
        }

        Log::error('Erro ao criar cobrança no Asaas', [
            'status' => $response->status(),
            'response' => $response->body()
        ]);

        throw new \Exception('Erro ao criar cobrança no Asaas: ' . $response->body());
    }

    /**
     * Buscar uma cobrança por ID
     */
    public function getPayment(string $paymentId): array
    {
        $response = Http::withHeaders([
            'access_token' => $this->apiKey,
        ])->get($this->baseUrl . '/payments/' . $paymentId);

        if ($response->successful()) {
            return $response->json();
        }

        Log::error('Erro ao buscar cobrança no Asaas', [
            'payment_id' => $paymentId,
            'status' => $response->status(),
            'response' => $response->body()
        ]);

        throw new \Exception('Erro ao buscar cobrança no Asaas: ' . $response->body());
    }

    /**
     * Criar cobrança PIX
     */
    public function createPixPayment(array $paymentData): array
    {
        $pixData = array_merge($paymentData, [
            'billingType' => 'PIX',
        ]);

        return $this->createPayment($pixData);
    }

    /**
     * Criar cobrança via boleto
     */
    public function createBoletoPayment(array $paymentData): array
    {
        $boletoData = array_merge($paymentData, [
            'billingType' => 'BOLETO',
        ]);

        return $this->createPayment($boletoData);
    }

    /**
     * Criar cobrança via cartão de crédito
     */
    public function createCreditCardPayment(array $paymentData): array
    {
        $cardData = array_merge($paymentData, [
            'billingType' => 'CREDIT_CARD',
        ]);

        return $this->createPayment($cardData);
    }

    /**
     * Cancelar uma cobrança
     */
    public function cancelPayment(string $paymentId): array
    {
        $response = Http::withHeaders([
            'access_token' => $this->apiKey,
        ])->delete($this->baseUrl . '/payments/' . $paymentId);

        if ($response->successful()) {
            return $response->json();
        }

        Log::error('Erro ao cancelar cobrança no Asaas', [
            'payment_id' => $paymentId,
            'status' => $response->status(),
            'response' => $response->body()
        ]);

        throw new \Exception('Erro ao cancelar cobrança no Asaas: ' . $response->body());
    }

    /**
     * Estornar uma cobrança
     */
    public function refundPayment(string $paymentId, ?float $value = null): array
    {
        $refundData = [];
        if ($value !== null) {
            $refundData['value'] = $value;
        }

        $response = Http::withHeaders([
            'access_token' => $this->apiKey,
            'Content-Type' => 'application/json',
        ])->post($this->baseUrl . '/payments/' . $paymentId . '/refund', $refundData);

        if ($response->successful()) {
            return $response->json();
        }

        Log::error('Erro ao estornar cobrança no Asaas', [
            'payment_id' => $paymentId,
            'status' => $response->status(),
            'response' => $response->body()
        ]);

        throw new \Exception('Erro ao estornar cobrança no Asaas: ' . $response->body());
    }

    /**
     * Verificar se o webhook é válido
     */
    public function validateWebhook(string $webhookToken): bool
    {
        return $webhookToken === config('services.asaas.webhook_token');
    }

    /**
     * Processar evento de webhook
     */
    public function processWebhookEvent(array $eventData): void
    {
        $eventType = $eventData['event'] ?? null;
        $paymentData = $eventData['payment'] ?? null;

        if (!$eventType || !$paymentData) {
            Log::warning('Webhook do Asaas com dados incompletos', $eventData);
            return;
        }

        Log::info('Processando evento do Asaas', [
            'event_type' => $eventType,
            'payment_id' => $paymentData['id'] ?? null,
            'status' => $paymentData['status'] ?? null
        ]);

        // Aqui você pode implementar a lógica específica para cada tipo de evento
        switch ($eventType) {
            case 'PAYMENT_CONFIRMED':
                $this->handlePaymentConfirmed($paymentData);
                break;
            case 'PAYMENT_RECEIVED':
                $this->handlePaymentReceived($paymentData);
                break;
            case 'PAYMENT_OVERDUE':
                $this->handlePaymentOverdue($paymentData);
                break;
            case 'PAYMENT_DELETED':
                $this->handlePaymentDeleted($paymentData);
                break;
            default:
                Log::info('Evento do Asaas não tratado', ['event_type' => $eventType]);
        }
    }

    private function handlePaymentConfirmed(array $paymentData): void
    {
        $this->updateDonateStatus($paymentData, 'paid');
        Log::info('Pagamento confirmado no Asaas', $paymentData);
    }

    private function handlePaymentReceived(array $paymentData): void
    {
        $this->updateDonateStatus($paymentData, 'paid');
        Log::info('Pagamento recebido no Asaas', $paymentData);
    }

    private function handlePaymentOverdue(array $paymentData): void
    {
        $this->updateDonateStatus($paymentData, 'pending');
        Log::info('Pagamento vencido no Asaas', $paymentData);
    }

    private function handlePaymentDeleted(array $paymentData): void
    {
        $this->updateDonateStatus($paymentData, 'cancelled');
        Log::info('Pagamento deletado no Asaas', $paymentData);
    }

    /**
     * Atualizar status da doação baseado no evento do Asaas
     */
    private function updateDonateStatus(array $paymentData, string $newStatus): void
    {
        $paymentId = $paymentData['id'] ?? null;
        if (!$paymentId) {
            return;
        }

        $donate = Donate::where('asaas_cobranca_id', $paymentId)->first();
        if (!$donate) {
            Log::warning('Doação não encontrada para cobrança do Asaas', [
                'payment_id' => $paymentId
            ]);
            return;
        }

        $oldStatus = $donate->status;
        $donate->update(['status' => $newStatus]);

        // Atualizar valor atual do projeto se foi pago
        if ($newStatus === 'paid' && $oldStatus !== 'paid') {
            $donate->project->updateCurrentAmount();
        }

        Log::info('Status da doação atualizado', [
            'donate_id' => $donate->id,
            'old_status' => $oldStatus,
            'new_status' => $newStatus,
            'payment_id' => $paymentId
        ]);
    }
}
