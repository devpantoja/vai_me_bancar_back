<?php

namespace App\Http\Controllers;

use App\Models\Donate;
use App\Models\Project;
use App\Services\AsaasService;
use Illuminate\Http\Request;

/**
 * @OA\Tag(name="Doações")
 * @OA\Tag(name="Pagamentos")
 */
class DonateController extends Controller
{
    private AsaasService $asaasService;

    public function __construct(AsaasService $asaasService)
    {
        $this->asaasService = $asaasService;
    }

    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $donates = Donate::with('project')->get();
        return response()->json($donates);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'amount' => 'required|numeric|min:0.01',
            'status' => 'required|string|in:pending,paid,cancelled',
            'project_id' => 'required|exists:projects,id',
            'donor_name' => 'required|string|max:255',
            'cellphone' => 'required|string|max:20',
            'asaas_cliente_id' => 'nullable|string|max:255',
            'asaas_cobranca_id' => 'nullable|string|max:255',
        ]);

        $donate = Donate::create($request->all());
        $donate->load('project');
        
        // Atualiza o valor atual do projeto
        $donate->project->updateCurrentAmount();
        
        // Gera mensagem de zoeira se a doação foi paga
        $trollMessage = null;
        if ($donate->status === 'paid') {
            $trollMessage = $donate->project->generateTrollMessage(
                $donate->amount,
                $donate->donor_name
            );
        }
        
        return response()->json([
            'message' => 'Doação criada com sucesso!',
            'donate' => $donate,
            'troll_message' => $trollMessage,
            'project_progress' => $donate->project->getProgressPercentage(),
            'is_goal_reached' => $donate->project->isGoalReached()
        ], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(Donate $donate)
    {
        $donate->load('project');
        return response()->json($donate);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Donate $donate)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Donate $donate)
    {
        $request->validate([
            'amount' => 'sometimes|numeric|min:0.01',
            'status' => 'sometimes|string|in:pending,paid,cancelled',
            'project_id' => 'sometimes|exists:projects,id',
            'donor_name' => 'sometimes|string|max:255',
            'cellphone' => 'sometimes|string|max:20',
            'asaas_cliente_id' => 'nullable|string|max:255',
            'asaas_cobranca_id' => 'nullable|string|max:255',
        ]);

        $donate->update($request->all());
        $donate->load('project');
        
        return response()->json([
            'message' => 'Doação atualizada com sucesso!',
            'donate' => $donate
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Donate $donate)
    {
        $donate->delete();
        
        return response()->json([
            'message' => 'Doação excluída com sucesso!'
        ]);
    }

    /**
     * Listar doações por projeto
     */
    public function byProject(Project $project)
    {
        $donates = $project->donates;
        return response()->json([
            'project' => $project,
            'donates' => $donates
        ]);
    }

    /**
     * @OA\Post(
     *     path="/api/donates/pix",
     *     summary="Criar cobrança PIX",
     *     tags={"Pagamentos"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"amount", "project_id", "donor_name", "donor_email", "donor_cpf", "donor_phone"},
     *             @OA\Property(property="amount", type="number", format="float", example=100.00),
     *             @OA\Property(property="project_id", type="integer", example=1),
     *             @OA\Property(property="donor_name", type="string", example="João Silva"),
     *             @OA\Property(property="donor_email", type="string", format="email", example="joao@email.com"),
     *             @OA\Property(property="donor_cpf", type="string", example="11144477735"),
     *             @OA\Property(property="donor_phone", type="string", example="11999999999"),
     *             @OA\Property(property="description", type="string", example="Doação para o projeto")
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Cobrança PIX criada com sucesso",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Cobrança PIX criada com sucesso!"),
     *             @OA\Property(property="donate", ref="#/components/schemas/Donate"),
     *             @OA\Property(property="payment", type="object"),
     *             @OA\Property(property="pix_code", type="string", example="QR Code PIX"),
     *             @OA\Property(property="pix_copy_paste", type="string", example="Código PIX para copiar")
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Erro de validação",
     *         @OA\JsonContent(ref="#/components/schemas/Error")
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Erro interno do servidor",
     *         @OA\JsonContent(
     *             @OA\Property(property="error", type="string", example="Erro ao criar cobrança PIX")
     *         )
     *     )
     * )
     */
    public function createPixPayment(Request $request)
    {
        $request->validate([
            'amount' => 'required|numeric|min:0.01',
            'project_id' => 'required|exists:projects,id',
            'donor_name' => 'required|string|max:255',
            'donor_email' => 'required|email|max:255',
            'donor_cpf' => 'required|string|max:14',
            'donor_phone' => 'required|string|max:20',
            'description' => 'nullable|string|max:500',
        ]);

        try {
            $project = Project::findOrFail($request->project_id);

            // Criar cliente no Asaas
            $customerData = [
                'name' => $request->donor_name,
                'email' => $request->donor_email,
                'cpfCnpj' => $request->donor_cpf,
                'phone' => $request->donor_phone,
            ];

            $customer = $this->asaasService->createCustomer($customerData);

            // Criar cobrança PIX
            $paymentData = [
                'customer' => $customer['id'],
                'billingType' => 'PIX',
                'value' => $request->amount,
                'dueDate' => now()->addDays(3)->format('Y-m-d'),
                'description' => $request->description ?? "Doação para: {$project->title}",
                'externalReference' => "donate_{$project->id}_" . time(),
            ];

            $payment = $this->asaasService->createPixPayment($paymentData);

            // Criar registro da doação
            $donate = Donate::create([
                'amount' => $request->amount,
                'status' => 'pending',
                'project_id' => $request->project_id,
                'donor_name' => $request->donor_name,
                'cellphone' => $request->donor_phone,
                'asaas_cliente_id' => $customer['id'],
                'asaas_cobranca_id' => $payment['id'],
            ]);

            return response()->json([
                'message' => 'Cobrança PIX criada com sucesso!',
                'donate' => $donate,
                'payment' => $payment,
                'pix_code' => $payment['pixTransaction']['qrCode'] ?? null,
                'pix_copy_paste' => $payment['pixTransaction']['payload'] ?? null,
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Erro ao criar cobrança PIX: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Criar cobrança via boleto no Asaas
     */
    public function createBoletoPayment(Request $request)
    {
        $request->validate([
            'amount' => 'required|numeric|min:0.01',
            'project_id' => 'required|exists:projects,id',
            'donor_name' => 'required|string|max:255',
            'donor_email' => 'required|email|max:255',
            'donor_cpf' => 'required|string|max:14',
            'donor_phone' => 'required|string|max:20',
            'donor_address' => 'required|string|max:500',
            'donor_city' => 'required|string|max:100',
            'donor_state' => 'required|string|max:2',
            'donor_zipcode' => 'required|string|max:10',
            'description' => 'nullable|string|max:500',
        ]);

        try {
            $project = Project::findOrFail($request->project_id);

            // Criar cliente no Asaas
            $customerData = [
                'name' => $request->donor_name,
                'email' => $request->donor_email,
                'cpfCnpj' => $request->donor_cpf,
                'phone' => $request->donor_phone,
                'address' => $request->donor_address,
                'city' => $request->donor_city,
                'state' => $request->donor_state,
                'postalCode' => $request->donor_zipcode,
            ];

            $customer = $this->asaasService->createCustomer($customerData);

            // Criar cobrança boleto
            $paymentData = [
                'customer' => $customer['id'],
                'billingType' => 'BOLETO',
                'value' => $request->amount,
                'dueDate' => now()->addDays(3)->format('Y-m-d'),
                'description' => $request->description ?? "Doação para: {$project->title}",
                'externalReference' => "donate_{$project->id}_" . time(),
            ];

            $payment = $this->asaasService->createBoletoPayment($paymentData);

            // Criar registro da doação
            $donate = Donate::create([
                'amount' => $request->amount,
                'status' => 'pending',
                'project_id' => $request->project_id,
                'donor_name' => $request->donor_name,
                'cellphone' => $request->donor_phone,
                'asaas_cliente_id' => $customer['id'],
                'asaas_cobranca_id' => $payment['id'],
            ]);

            return response()->json([
                'message' => 'Cobrança boleto criada com sucesso!',
                'donate' => $donate,
                'payment' => $payment,
                'boleto_url' => $payment['bankSlipUrl'] ?? null,
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Erro ao criar cobrança boleto: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Verificar status de uma cobrança
     */
    public function checkPaymentStatus(Donate $donate)
    {
        try {
            if (!$donate->asaas_cobranca_id) {
                return response()->json([
                    'error' => 'Doação não possui cobrança no Asaas'
                ], 400);
            }

            $payment = $this->asaasService->getPayment($donate->asaas_cobranca_id);
            
            // Atualizar status da doação se necessário
            $newStatus = $this->mapAsaasStatusToDonateStatus($payment['status']);
            if ($newStatus !== $donate->status) {
                $donate->update(['status' => $newStatus]);
                
                // Atualizar valor atual do projeto se foi pago
                if ($newStatus === 'paid') {
                    $donate->project->updateCurrentAmount();
                }
            }

            return response()->json([
                'donate' => $donate->fresh(),
                'payment' => $payment,
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Erro ao verificar status: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Mapear status do Asaas para status da doação
     */
    private function mapAsaasStatusToDonateStatus(string $asaasStatus): string
    {
        return match ($asaasStatus) {
            'PENDING' => 'pending',
            'CONFIRMED', 'RECEIVED' => 'paid',
            'OVERDUE' => 'pending',
            'REFUNDED' => 'cancelled',
            'RECEIVED_IN_CASH' => 'paid',
            'CHARGEBACK_REQUESTED' => 'pending',
            'CHARGEBACK_DISPUTE' => 'pending',
            'AWAITING_CHARGEBACK_REVERSAL' => 'pending',
            'DUNNING_REQUESTED' => 'pending',
            'DUNNING_RECEIVED' => 'pending',
            'AWAITING_RISK_ANALYSIS' => 'pending',
            default => 'pending',
        };
    }
}
