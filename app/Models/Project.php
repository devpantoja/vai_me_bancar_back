<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Project extends Model
{
    protected $fillable = [
        'name',
        'description',
        'budget',
        'start_date',
        'end_date',
        'owner_name',
        'cellphone',
        'category',
        'status',
        'current_amount'
    ];

    protected $casts = [
        'budget' => 'decimal:2',
        'current_amount' => 'decimal:2',
        'start_date' => 'datetime',
        'end_date' => 'datetime',
    ];

    /**
     * Boot do modelo para definir categoria automaticamente
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($project) {
            $project->category = $project->calculateCategory($project->budget);
        });

        static::updating(function ($project) {
            if ($project->isDirty('budget')) {
                $project->category = $project->calculateCategory($project->budget);
            }
        });
    }

    /**
     * Calcula a categoria baseada no valor do orçamento
     * 
     * @param float $budget
     * @return string
     */
    public function calculateCategory($budget)
    {
        // Calcula as faixas baseadas no valor total
        $totalBudget = $budget;
        $range1 = $totalBudget * 0.3; // 30% do valor total
        $range2 = $totalBudget * 0.7; // 70% do valor total

        // Para valores pequenos, usar faixas fixas baseadas no exemplo dado
        if ($totalBudget <= 1000) {
            if ($budget <= 300) {
                return 'Mão de Vaca';
            } elseif ($budget <= 700) {
                return 'Mão de Vaca Médio';
            } else {
                return 'Shark Tank';
            }
        }

        // Para valores maiores, usar percentuais
        if ($budget <= $range1) {
            return 'Mão de Vaca';
        } elseif ($budget <= $range2) {
            return 'Mão de Vaca Médio';
        } else {
            return 'Shark Tank';
        }
    }

    /**
     * Relacionamento com doações
     */
    public function donates()
    {
        return $this->hasMany(Donate::class);
    }

    /**
     * Calcula o progresso da meta
     */
    public function getProgressPercentage()
    {
        if ($this->budget <= 0) return 0;
        return min(100, ($this->current_amount / $this->budget) * 100);
    }

    /**
     * Verifica se a meta foi atingida
     */
    public function isGoalReached()
    {
        return $this->current_amount >= $this->budget;
    }

    /**
     * Calcula tempo restante em formato legível
     */
    public function getTimeRemaining()
    {
        $now = now();
        $end = $this->end_date;
        
        if ($end <= $now) {
            return 'Expirado';
        }
        
        $diff = $end->diff($now);
        
        if ($diff->days > 0) {
            return $diff->days . ' dias restantes';
        } elseif ($diff->h > 0) {
            return $diff->h . ' horas restantes';
        } else {
            return $diff->i . ' minutos restantes';
        }
    }

    /**
     * Gera mensagem de zoeira baseada no valor da doação
     */
    public function generateTrollMessage($donateAmount, $donorName)
    {
        $percentage = ($donateAmount / $this->budget) * 100;
        
        if ($percentage < 1) {
            return "{$donorName} deu R$ {$donateAmount}, parabéns, agora você só precisa de mais R$ " . 
                   number_format($this->budget - $donateAmount, 2, ',', '.') . 
                   " pra ser relevante 😂";
        } elseif ($percentage < 5) {
            return "{$donorName} botou R$ {$donateAmount}, está querendo ser o herói da vaquinha 🤡";
        } elseif ($percentage < 10) {
            return "{$donorName} com R$ {$donateAmount} tá começando a ficar sério! 🔥";
        } elseif ($percentage < 25) {
            return "{$donorName} mandou R$ {$donateAmount}, agora sim tá ficando interessante! 💪";
        } elseif ($percentage < 50) {
            return "{$donorName} com R$ {$donateAmount} tá quase virando sócio! 🚀";
        } else {
            return "{$donorName} deu R$ {$donateAmount}, esse aí é o verdadeiro MVP! 👑";
        }
    }

    /**
     * Retorna o ranking de doadores do dia
     */
    public function getDailyRanking()
    {
        $today = now()->startOfDay();
        $tomorrow = now()->addDay()->startOfDay();
        
        $donates = $this->donates()
            ->whereBetween('created_at', [$today, $tomorrow])
            ->where('status', 'paid')
            ->orderBy('amount', 'desc')
            ->get();
            
        return $donates;
    }

    /**
     * Retorna o maior doador do dia
     */
    public function getDailyTopDonor()
    {
        $ranking = $this->getDailyRanking();
        return $ranking->first();
    }

    /**
     * Retorna o menor doador do dia
     */
    public function getDailyLowestDonor()
    {
        $ranking = $this->getDailyRanking();
        return $ranking->last();
    }

    /**
     * Atualiza o valor atual baseado nas doações
     */
    public function updateCurrentAmount()
    {
        $totalPaid = $this->donates()
            ->where('status', 'paid')
            ->sum('amount');
            
        $this->current_amount = $totalPaid;
        $this->save();
    }

    /**
     * Calcula o total arrecadado para ajudar o projeto
     */
    public function getHelpAmount()
    {
        return $this->donates()
            ->where('status', 'paid')
            ->where('donation_type', 'help')
            ->sum('amount');
    }

    /**
     * Calcula o total arrecadado para parar o projeto
     */
    public function getStopAmount()
    {
        return $this->donates()
            ->where('status', 'paid')
            ->where('donation_type', 'stop')
            ->sum('amount');
    }

    /**
     * Calcula a porcentagem de doações para ajudar
     */
    public function getHelpPercentage()
    {
        $helpAmount = $this->getHelpAmount();
        $stopAmount = $this->getStopAmount();
        $totalAmount = $helpAmount + $stopAmount;
        
        if ($totalAmount <= 0) return 0;
        
        return round(($helpAmount / $totalAmount) * 100, 2);
    }

    /**
     * Calcula a porcentagem de doações para parar
     */
    public function getStopPercentage()
    {
        $helpAmount = $this->getHelpAmount();
        $stopAmount = $this->getStopAmount();
        $totalAmount = $helpAmount + $stopAmount;
        
        if ($totalAmount <= 0) return 0;
        
        return round(($stopAmount / $totalAmount) * 100, 2);
    }

    /**
     * Gera mensagem engraçada quando stop > help
     */
    public function generateStopWinMessage()
    {
        $helpAmount = $this->getHelpAmount();
        $stopAmount = $this->getStopAmount();
        
        if ($stopAmount <= $helpAmount) {
            return null; // Não gera mensagem se stop não for maior que help
        }
        
        $messages = [
            "🚨 ALERTA! O projeto '{$this->name}' está sendo SABOTADO! 😈",
            "💀 Os haters estão ganhando! R$ " . number_format($stopAmount, 2, ',', '.') . " para PARAR vs R$ " . number_format($helpAmount, 2, ',', '.') . " para AJUDAR!",
            "🔥 Guerra de vaquinhas! Os trolls estão na frente com " . $this->getStopPercentage() . "% das doações!",
            "😱 O projeto está sendo BOICOTADO! Mais gente quer ver falhar do que dar certo!",
            "🎭 Plot twist! A vaquinha virou uma guerra entre anjos e demônios! 😂",
            "⚔️ Batalha épica! R$ " . number_format($stopAmount - $helpAmount, 2, ',', '.') . " a mais para DESTRUIR o projeto!",
            "🎪 Circo dos horrores! Os haters estão dominando a vaquinha!",
            "🚫 STOP ganhando! O projeto está sendo cancelado pelos próprios doadores! 😅"
        ];
        
        return $messages[array_rand($messages)];
    }

    /**
     * Retorna estatísticas completas de arrecadação
     */
    public function getFundraisingStats()
    {
        $helpAmount = $this->getHelpAmount();
        $stopAmount = $this->getStopAmount();
        $totalAmount = $helpAmount + $stopAmount;
        
        return [
            'help_amount' => $helpAmount,
            'stop_amount' => $stopAmount,
            'total_amount' => $totalAmount,
            'help_percentage' => $this->getHelpPercentage(),
            'stop_percentage' => $this->getStopPercentage(),
            'stop_wins' => $stopAmount > $helpAmount,
            'troll_message' => $this->generateStopWinMessage(),
            'help_count' => $this->donates()->where('status', 'paid')->where('donation_type', 'help')->count(),
            'stop_count' => $this->donates()->where('status', 'paid')->where('donation_type', 'stop')->count(),
        ];
    }
}
