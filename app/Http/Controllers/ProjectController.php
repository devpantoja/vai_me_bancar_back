<?php

namespace App\Http\Controllers;

use App\Models\Project;
use Illuminate\Http\Request;

class ProjectController extends Controller
{
    /**
     * Display a listing of the resource.
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
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'required|string',
            'budget' => 'required|numeric|min:0',
            'goal_amount' => 'required|numeric|min:0',
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
