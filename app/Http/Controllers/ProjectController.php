<?php

namespace App\Http\Controllers;

use App\Models\Project;
use Illuminate\Http\Request;

/**
 * @OA\Info(
 *     title="Vai Me Bancar API",
 *     version="1.0.0",
 *     description="API para sistema de vaquinhas com integração ao Asaas",
 *     @OA\Contact(
 *         email="contato@vaimebancar.com"
 *     )
 * )
 * 
 * @OA\Server(
 *     url="https://vaimebancar.codegus.com",
 *     description="Servidor de produção"
 * )
 * 
 * @OA\Tag(
 *     name="Projetos",
 *     description="Endpoints para gerenciamento de projetos/vaquinhas"
 * )
 * 
 * @OA\Tag(
 *     name="Doações",
 *     description="Endpoints para gerenciamento de doações"
 * )
 * 
 * @OA\Tag(
 *     name="Pagamentos",
 *     description="Endpoints para integração com Asaas (PIX, Boleto)"
 * )
 * 
 * @OA\Tag(
 *     name="Webhooks",
 *     description="Endpoints para webhooks do Asaas"
 * )
 * 
 * @OA\Schema(
 *     schema="Project",
 *     type="object",
 *     @OA\Property(property="id", type="integer", example=1),
 *     @OA\Property(property="name", type="string", example="Vaquinha do João"),
 *     @OA\Property(property="description", type="string", example="João precisa de dinheiro para comprar um novo celular"),
 *     @OA\Property(property="budget", type="number", format="float", example=1500.00),
 *     @OA\Property(property="current_amount", type="number", format="float", example=500.00),
 *     @OA\Property(property="start_date", type="string", format="date-time", example="2025-01-20T00:00:00.000000Z"),
 *     @OA\Property(property="end_date", type="string", format="date-time", example="2025-02-20T00:00:00.000000Z"),
 *     @OA\Property(property="owner_name", type="string", example="João Silva"),
 *     @OA\Property(property="cellphone", type="string", example="11999999999"),
 *     @OA\Property(property="category", type="string", example="Shark Tank"),
 *     @OA\Property(property="status", type="string", example="active"),
 *     @OA\Property(property="created_at", type="string", format="date-time"),
 *     @OA\Property(property="updated_at", type="string", format="date-time")
 * )
 * 
 * @OA\Schema(
 *     schema="Donate",
 *     type="object",
 *     @OA\Property(property="id", type="integer", example=1),
 *     @OA\Property(property="amount", type="number", format="float", example=100.00),
 *     @OA\Property(property="status", type="string", example="paid"),
 *     @OA\Property(property="project_id", type="integer", example=1),
 *     @OA\Property(property="donor_name", type="string", example="Maria Santos"),
 *     @OA\Property(property="cellphone", type="string", example="11888888888"),
 *     @OA\Property(property="asaas_cliente_id", type="string", example="cus_123456"),
 *     @OA\Property(property="asaas_cobranca_id", type="string", example="pay_789012"),
 *     @OA\Property(property="created_at", type="string", format="date-time"),
 *     @OA\Property(property="updated_at", type="string", format="date-time")
 * )
 * 
 * @OA\Schema(
 *     schema="Error",
 *     type="object",
 *     @OA\Property(property="message", type="string", example="Erro de validação"),
 *     @OA\Property(property="errors", type="object")
 * )
 */
class ProjectController extends Controller
{
    /**
     * @OA\Get(
     *     path="/api/projects",
     *     summary="Listar todos os projetos",
     *     tags={"Projetos"},
     *     @OA\Response(
     *         response=200,
     *         description="Lista de projetos retornada com sucesso",
     *         @OA\JsonContent(
     *             type="array",
     *             @OA\Items(ref="#/components/schemas/Project")
     *         )
     *     )
     * )
     */
    public function index()
    {
        $projects = Project::with('donates')->get();
        return response()->json($projects);
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
     *     path="/api/projects",
     *     summary="Criar um novo projeto",
     *     tags={"Projetos"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"name", "description", "budget", "start_date", "end_date", "owner_name", "cellphone"},
     *             @OA\Property(property="name", type="string", example="Vaquinha do João"),
     *             @OA\Property(property="description", type="string", example="João precisa de dinheiro para comprar um novo celular"),
     *             @OA\Property(property="budget", type="number", format="float", example=1500.00),
     *             @OA\Property(property="start_date", type="string", format="date", example="2025-01-20"),
     *             @OA\Property(property="end_date", type="string", format="date", example="2025-02-20"),
     *             @OA\Property(property="owner_name", type="string", example="João Silva"),
     *             @OA\Property(property="cellphone", type="string", example="11999999999")
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Projeto criado com sucesso",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Projeto criado com sucesso!"),
     *             @OA\Property(property="project", ref="#/components/schemas/Project")
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
            'name' => 'required|string|max:255',
            'description' => 'required|string',
            'budget' => 'required|numeric|min:0',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after:start_date',
            'owner_name' => 'required|string|max:255',
            'cellphone' => 'required|string|max:20',
        ]);

        $project = Project::create($request->all());
        
        return response()->json([
            'message' => 'Projeto criado com sucesso!',
            'project' => $project
        ], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(Project $project)
    {
        $project->load('donates');
        return response()->json($project);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Project $project)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Project $project)
    {
        $request->validate([
            'name' => 'sometimes|string|max:255',
            'description' => 'sometimes|string',
            'budget' => 'sometimes|numeric|min:0',
            'start_date' => 'sometimes|date',
            'end_date' => 'sometimes|date|after:start_date',
            'owner_name' => 'sometimes|string|max:255',
            'cellphone' => 'sometimes|string|max:20',
        ]);

        $project->update($request->all());
        
        return response()->json([
            'message' => 'Projeto atualizado com sucesso!',
            'project' => $project
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Project $project)
    {
        $project->delete();
        
        return response()->json([
            'message' => 'Projeto excluído com sucesso!'
        ]);
    }

    /**
     * Obter informações completas do projeto com gamificação
     */
    public function getProjectInfo(Project $project)
    {
        $project->load('donates');
        $project->updateCurrentAmount();
        
        $info = [
            'project' => $project,
            'progress_percentage' => $project->getProgressPercentage(),
            'time_remaining' => $project->getTimeRemaining(),
            'is_goal_reached' => $project->isGoalReached(),
            'daily_ranking' => $project->getDailyRanking(),
            'top_donor_today' => $project->getDailyTopDonor(),
            'lowest_donor_today' => $project->getDailyLowestDonor(),
        ];
        
        return response()->json($info);
    }

    /**
     * Obter ranking de zoeira do dia
     */
    public function getDailyRanking(Project $project)
    {
        $ranking = $project->getDailyRanking();
        
        $response = [
            'project' => $project,
            'ranking' => $ranking,
            'top_donor' => $project->getDailyTopDonor(),
            'lowest_donor' => $project->getDailyLowestDonor(),
            'total_donors_today' => $ranking->count(),
            'total_amount_today' => $ranking->sum('amount')
        ];
        
        return response()->json($response);
    }

    /**
     * Gerar mensagem de zoeira para uma doação
     */
    public function generateTrollMessage(Request $request, Project $project)
    {
        $request->validate([
            'donate_amount' => 'required|numeric|min:0.01',
            'donor_name' => 'required|string|max:255'
        ]);

        $message = $project->generateTrollMessage(
            $request->donate_amount,
            $request->donor_name
        );
        
        return response()->json([
            'message' => $message,
            'donate_amount' => $request->donate_amount,
            'donor_name' => $request->donor_name,
            'project_name' => $project->name
        ]);
    }
}
