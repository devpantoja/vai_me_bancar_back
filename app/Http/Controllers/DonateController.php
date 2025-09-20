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
     * @OA\Get(
     *     path="/api/donates",
     *     summary="Listar todas as doações",
     *     tags={"Doações"},
     *     @OA\Response(
     *         response=200,
     *         description="Lista de doações retornada com sucesso",
     *         @OA\JsonContent(
     *             type="array",
     *             @OA\Items(ref="#/components/schemas/Donate")
     *         )
     *     )
     * )
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
     * @OA\Post(
     *     path="/api/donates",
     *     summary="Criar uma nova doação (ajudar ou parar projeto)",
     *     tags={"Doações"},
     *     description="Cria uma nova doação. Use 'help' para ajudar o projeto ou 'stop' para sabotar (subtrair valor)! 😈",
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(ref="#/components/schemas/CreateDonateData")
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Doação criada com sucesso",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Doação para AJUDAR o projeto criada com sucesso! 💚"),
     *             @OA\Property(property="donate", ref="#/components/schemas/Donate"),
     *             @OA\Property(property="troll_message", type="string", example="😈 João acabou de DOAR PARA PARAR o projeto 'Viagem dos Sonhos'! R$ 100 para fazer o projeto falhar! Que maldade! 😂"),
     *             @OA\Property(property="project_progress", type="number", format="float", example=75.5),
     *             @OA\Property(property="is_goal_reached", type="boolean", example=false),
     *             @OA\Property(property="donation_type", type="string", enum={"help", "stop"}, example="help")
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Erro de validação",
     *         @OA\JsonContent(ref="#/components/schemas/Error")
     *     )
     * )
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
            'donation_type' => 'required|string|in:help,stop',
            'donation_message' => 'nullable|string|max:500',
        ]);

        $donate = Donate::create($request->all());
        $donate->load('project');
        
        // Lógica zueira: se é doação para parar o projeto
        if ($donate->donation_type === 'stop') {
            // Subtrai o valor do projeto (zueira total!)
            $donate->project->decrement('current_amount', $donate->amount);
            
            // Se o valor ficar negativo, zera
            if ($donate->project->current_amount < 0) {
                $donate->project->update(['current_amount' => 0]);
            }
        } else {
            // Doação normal para ajudar
            $donate->project->updateCurrentAmount();
        }
        
        // Gera mensagem de zoeira específica baseada no tipo
        $trollMessage = null;
        if ($donate->status === 'paid') {
            if ($donate->donation_type === 'stop') {
                $trollMessage = $this->generateStopTrollMessage($donate);
            } else {
                $trollMessage = $donate->project->generateTrollMessage(
                    $donate->amount,
                    $donate->donor_name
                );
            }
        }
        
        return response()->json([
            'message' => $donate->donation_type === 'stop' 
                ? 'Doação para PARAR o projeto criada com sucesso! 😈' 
                : 'Doação para AJUDAR o projeto criada com sucesso! 💚',
            'donate' => $donate,
            'troll_message' => $trollMessage,
            'project_progress' => $donate->project->getProgressPercentage(),
            'is_goal_reached' => $donate->project->isGoalReached(),
            'donation_type' => $donate->donation_type
        ], 201);
    }

    /**
     * @OA\Get(
     *     path="/api/donates/{donate}",
     *     summary="Buscar doação específica",
     *     tags={"Doações"},
     *     @OA\Parameter(
     *         name="donate",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer"),
     *         description="ID da doação"
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Doação encontrada com sucesso",
     *         @OA\JsonContent(ref="#/components/schemas/Donate")
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Doação não encontrada",
     *         @OA\JsonContent(ref="#/components/schemas/Error")
     *     )
     * )
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
     * @OA\Put(
     *     path="/api/donates/{donate}",
     *     summary="Atualizar doação",
     *     tags={"Doações"},
     *     @OA\Parameter(
     *         name="donate",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer"),
     *         description="ID da doação"
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="amount", type="number", format="float", example=150.00),
     *             @OA\Property(property="status", type="string", example="paid"),
     *             @OA\Property(property="project_id", type="integer", example=1),
     *             @OA\Property(property="donor_name", type="string", example="Maria Santos"),
     *             @OA\Property(property="cellphone", type="string", example="11888888888"),
     *             @OA\Property(property="asaas_cliente_id", type="string", example="cus_123456"),
     *             @OA\Property(property="asaas_cobranca_id", type="string", example="pay_789012")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Doação atualizada com sucesso",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Doação atualizada com sucesso!"),
     *             @OA\Property(property="donate", ref="#/components/schemas/Donate")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Doação não encontrada",
     *         @OA\JsonContent(ref="#/components/schemas/Error")
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Erro de validação",
     *         @OA\JsonContent(ref="#/components/schemas/Error")
     *     )
     * )
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
     * @OA\Delete(
     *     path="/api/donates/{donate}",
     *     summary="Excluir doação",
     *     tags={"Doações"},
     *     @OA\Parameter(
     *         name="donate",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer"),
     *         description="ID da doação"
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Doação excluída com sucesso",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Doação excluída com sucesso!")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Doação não encontrada",
     *         @OA\JsonContent(ref="#/components/schemas/Error")
     *     )
     * )
     */
    public function destroy(Donate $donate)
    {
        $donate->delete();
        
        return response()->json([
            'message' => 'Doação excluída com sucesso!'
        ]);
    }

    /**
     * @OA\Get(
     *     path="/api/projects/{project}/donates",
     *     summary="Listar doações de um projeto",
     *     tags={"Doações"},
     *     @OA\Parameter(
     *         name="project",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer"),
     *         description="ID do projeto"
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Lista de doações do projeto",
     *         @OA\JsonContent(
     *             @OA\Property(property="project", ref="#/components/schemas/Project"),
     *             @OA\Property(property="donates", type="array", @OA\Items(ref="#/components/schemas/Donate"))
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Projeto não encontrado",
     *         @OA\JsonContent(ref="#/components/schemas/Error")
     *     )
     * )
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
     *     summary="Criar cobrança PIX (ajudar ou parar projeto)",
     *     tags={"Pagamentos"},
     *     description="Cria uma cobrança PIX para doação. Use 'help' para ajudar o projeto ou 'stop' para sabotar! 😈",
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(ref="#/components/schemas/CreatePixPaymentData")
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Cobrança PIX criada com sucesso",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Cobrança PIX criada com sucesso!"),
     *             @OA\Property(property="donate", ref="#/components/schemas/Donate"),
     *             @OA\Property(property="payment", type="object"),
     *             @OA\Property(property="pix_code", type="string", example="QR Code PIX"),
     *             @OA\Property(property="pix_copy_paste", type="string", example="Código PIX para copiar"),
     *             @OA\Property(property="donation_type", type="string", enum={"help", "stop"}, example="help")
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
            'donation_type' => 'required|string|in:help,stop',
            'donation_message' => 'nullable|string|max:500',
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
                'donation_type' => $request->donation_type,
                'donation_message' => $request->donation_message,
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
     * @OA\Post(
     *     path="/api/donates/boleto",
     *     summary="Criar cobrança via boleto (ajudar ou parar projeto)",
     *     tags={"Pagamentos"},
     *     description="Cria uma cobrança via boleto para doação. Use 'help' para ajudar o projeto ou 'stop' para sabotar! 😈",
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(ref="#/components/schemas/CreateBoletoPaymentData")
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Cobrança boleto criada com sucesso",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Cobrança boleto criada com sucesso!"),
     *             @OA\Property(property="donate", ref="#/components/schemas/Donate"),
     *             @OA\Property(property="payment", type="object"),
     *             @OA\Property(property="boleto_url", type="string", example="URL do boleto"),
     *             @OA\Property(property="donation_type", type="string", enum={"help", "stop"}, example="help")
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
     *             @OA\Property(property="error", type="string", example="Erro ao criar cobrança boleto")
     *         )
     *     )
     * )
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
            'donation_type' => 'required|string|in:help,stop',
            'donation_message' => 'nullable|string|max:500',
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
                'donation_type' => $request->donation_type,
                'donation_message' => $request->donation_message,
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
     * @OA\Get(
     *     path="/api/donates/{donate}/status",
     *     summary="Verificar status de pagamento",
     *     tags={"Pagamentos"},
     *     @OA\Parameter(
     *         name="donate",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer"),
     *         description="ID da doação"
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Status verificado com sucesso",
     *         @OA\JsonContent(
     *             @OA\Property(property="donate", ref="#/components/schemas/Donate"),
     *             @OA\Property(property="payment", type="object", description="Dados do pagamento no Asaas")
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Doação não possui cobrança no Asaas",
     *         @OA\JsonContent(
     *             @OA\Property(property="error", type="string", example="Doação não possui cobrança no Asaas")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Doação não encontrada",
     *         @OA\JsonContent(ref="#/components/schemas/Error")
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Erro interno do servidor",
     *         @OA\JsonContent(
     *             @OA\Property(property="error", type="string", example="Erro ao verificar status")
     *         )
     *     )
     * )
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
                
                // Lógica zueira: atualizar valor do projeto baseado no tipo de doação
                if ($newStatus === 'paid') {
                    if ($donate->donation_type === 'stop') {
                        // Doação para parar: subtrai o valor
                        $donate->project->decrement('current_amount', $donate->amount);
                        if ($donate->project->current_amount < 0) {
                            $donate->project->update(['current_amount' => 0]);
                        }
                    } else {
                        // Doação normal: adiciona o valor
                        $donate->project->updateCurrentAmount();
                    }
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

    /**
     * Gera mensagem troll específica para doações de parar projeto
     */
    private function generateStopTrollMessage(Donate $donate): string
    {
        $messages = [
            "😈 {$donate->donor_name} acabou de DOAR PARA PARAR o projeto '{$donate->project->name}'! R$ {$donate->amount} para fazer o projeto falhar! Que maldade! 😂",
            "🚫 {$donate->donor_name} é um SABOTADOR! Doou R$ {$donate->amount} para PARAR o projeto '{$donate->project->name}'! A guerra das vaquinhas começou! ⚔️",
            "💀 {$donate->donor_name} é o VILÃO da história! R$ {$donate->amount} para destruir o projeto '{$donate->project->name}'! Que pessoa má! 😈",
            "🔥 {$donate->donor_name} acabou de lançar uma BOMBA! R$ {$donate->amount} para EXPLODIR o projeto '{$donate->project->name}'! Que zueira! 💣",
            "👹 {$donate->donor_name} é o DIABO em pessoa! Doou R$ {$donate->amount} para ACABAR com '{$donate->project->name}'! Que maldade! 😈",
            "⚡ {$donate->donor_name} lançou um RAIOS para PARAR o projeto! R$ {$donate->amount} para destruir '{$donate->project->name}'! Que energia negativa! ⚡",
            "🎭 {$donate->donor_name} é o ANTI-HERÓI da vaquinha! R$ {$donate->amount} para sabotar '{$donate->project->name}'! Que drama! 🎪",
            "💥 {$donate->donor_name} acabou de DETONAR! R$ {$donate->amount} para EXPLODIR o projeto '{$donate->project->name}'! Que explosão! 🧨"
        ];

        return $messages[array_rand($messages)];
    }
}
