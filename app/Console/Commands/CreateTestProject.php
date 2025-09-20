<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Project;
use App\Models\Donate;
use Carbon\Carbon;

class CreateTestProject extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'project:create-test';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Cria um projeto de teste com doações para help e stop';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('🚀 Criando projeto de teste...');

        // Criar o projeto
        $project = Project::create([
            'name' => 'Projeto de Teste - Guerra de Vaquinhas',
            'description' => 'Projeto criado para testar o sistema de guerra entre doações help vs stop. Meta: R$ 40.000',
            'budget' => 40000.00,
            'start_date' => Carbon::now(),
            'end_date' => Carbon::now()->addDays(30),
            'owner_name' => 'João Teste',
            'cellphone' => '11999999999',
            'status' => 'active',
            'current_amount' => 0.00
        ]);

        $this->info("✅ Projeto criado com ID: {$project->id}");
        $this->info("📊 Categoria: {$project->category}");

        // Criar 10 doações para HELP (valores variados)
        $helpAmounts = [1000, 1500, 2000, 2500, 3000, 3500, 4000, 4500, 5000, 5500];
        $helpTotal = 0;

        $this->info('💚 Criando doações para HELP...');
        foreach ($helpAmounts as $index => $amount) {
            $donate = Donate::create([
                'amount' => $amount,
                'status' => 'paid',
                'project_id' => $project->id,
                'donor_name' => "Ajudador " . ($index + 1),
                'cellphone' => '1199999999' . $index,
                'donation_type' => 'help',
                'donation_message' => "Vamos ajudar o projeto! 💪"
            ]);
            
            $helpTotal += $amount;
            $this->line("  - {$donate->donor_name}: R$ " . number_format($amount, 2, ',', '.'));
        }

        $this->info("💰 Total HELP: R$ " . number_format($helpTotal, 2, ',', '.'));

        // Criar doações para STOP (totalizando R$ 50.000)
        $stopAmounts = [10000, 15000, 20000, 5000]; // Total: 50.000
        $stopTotal = 0;

        $this->info('💀 Criando doações para STOP...');
        foreach ($stopAmounts as $index => $amount) {
            $donate = Donate::create([
                'amount' => $amount,
                'status' => 'paid',
                'project_id' => $project->id,
                'donor_name' => "Hater " . ($index + 1),
                'cellphone' => '1188888888' . $index,
                'donation_type' => 'stop',
                'donation_message' => "Vamos parar esse projeto! 😈"
            ]);
            
            $stopTotal += $amount;
            $this->line("  - {$donate->donor_name}: R$ " . number_format($amount, 2, ',', '.'));
        }

        $this->info("💰 Total STOP: R$ " . number_format($stopTotal, 2, ',', '.'));

        // Atualizar valor atual do projeto
        $project->updateCurrentAmount();

        // Obter estatísticas
        $stats = $project->getFundraisingStats();

        $this->newLine();
        $this->info('📊 ESTATÍSTICAS FINAIS:');
        $this->table(
            ['Métrica', 'Valor'],
            [
                ['Total Arrecadado', 'R$ ' . number_format($stats['total_amount'], 2, ',', '.')],
                ['Para AJUDAR', 'R$ ' . number_format($stats['help_amount'], 2, ',', '.')],
                ['Para PARAR', 'R$ ' . number_format($stats['stop_amount'], 2, ',', '.')],
                ['% AJUDAR', $stats['help_percentage'] . '%'],
                ['% PARAR', $stats['stop_percentage'] . '%'],
                ['STOP ganha?', $stats['stop_wins'] ? 'SIM 😈' : 'NÃO 💚'],
                ['Doações HELP', $stats['help_count']],
                ['Doações STOP', $stats['stop_count']]
            ]
        );

        if ($stats['troll_message']) {
            $this->newLine();
            $this->error('🎭 MENSAGEM DE ZOEIRA:');
            $this->line($stats['troll_message']);
        }

        $this->newLine();
        $this->info('🎉 Projeto de teste criado com sucesso!');
        $this->info("🔗 Acesse: GET /api/projects/{$project->id}/fundraising-stats");
        
        return Command::SUCCESS;
    }
}
