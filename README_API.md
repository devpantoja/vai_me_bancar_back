# 🚀 Vai Me Bancar API - Sistema Gamificado

API completa para sistema de crowdfunding gamificado com categorização automática, zoeira e ranking de doadores.

## 🎯 Funcionalidades

### 📊 Sistema de Categorização Automática
- **Mão de Vaca** (até R$ 300 ou 30% do valor)
- **Mão de Vaca Médio** (R$ 301-700 ou 31-70% do valor)  
- **Shark Tank** (acima de R$ 700 ou 70% do valor)

### 🎮 Gamificação
- Meta e progresso em tempo real
- Contagem regressiva automática
- Mensagens de zoeira baseadas no valor da doação
- Ranking diário de doadores
- Coroa virtual para maior doador 👑
- Título "Mão de Alface" para menor doador 🥬
- **Sistema de Guerra de Vaquinhas**: Doações para ajudar vs parar projetos
- **Mensagens engraçadas** quando doações para parar superam as de ajudar
- **Estatísticas detalhadas** com porcentagens de cada tipo de doação

## 🚀 Como Usar

### 1. Configuração
1. Importe o arquivo `insomnia_collection.json` no Insomnia
2. Configure a variável `base_url` para `http://localhost:8000`
3. Inicie o servidor Laravel: `php artisan serve`

### 2. Fluxo Básico
1. **Criar Projeto** → `POST /api/projects`
2. **Fazer Doações** → `POST /api/donates`
3. **Ver Progresso** → `GET /api/projects/{id}/info`
4. **Ver Ranking** → `GET /api/projects/{id}/ranking`

## 📋 Endpoints Principais

### Projetos
- `GET /api/projects` - Listar todos os projetos
- `POST /api/projects` - Criar novo projeto
- `GET /api/projects/{id}` - Visualizar projeto específico
- `PUT /api/projects/{id}` - Atualizar projeto
- `DELETE /api/projects/{id}` - Excluir projeto

### Gamificação
- `GET /api/projects/{id}/info` - Informações completas com progresso
- `GET /api/projects/{id}/ranking` - Ranking de zoeira do dia
- `GET /api/projects/{id}/fundraising-stats` - Estatísticas de arrecadação (help vs stop)
- `POST /api/projects/{id}/troll-message` - Gerar mensagem de zoeira

### Doações
- `GET /api/donates` - Listar todas as doações
- `POST /api/donates` - Criar doação (com zoeira automática)
- `GET /api/projects/{id}/donates` - Doações de um projeto
- `POST /api/projects/{id}/donates` - Criar doação para projeto específico

## 🎭 Exemplos de Mensagens de Zoeira

### Doação Pequena (R$ 1)
```
"Mão de Alface deu R$ 1, parabéns, agora você só precisa de mais R$ 1.999,00 pra ser relevante 😂"
```

### Doação Média (R$ 50)
```
"Maria Santos botou R$ 50, está querendo ser o herói da vaquinha 🤡"
```

### Doação Grande (R$ 500)
```
"Rei da Vaquinha com R$ 500 tá quase virando sócio! 🚀"
```

### Doação Gigante (R$ 1000+)
```
"João deu R$ 1000, esse aí é o verdadeiro MVP! 👑"
```

## 🎭 Exemplos de Mensagens de Guerra de Vaquinhas

### Quando Stop > Help
```
"🚨 ALERTA! O projeto 'Viagem dos Sonhos' está sendo SABOTADO! 😈"
"💀 Os haters estão ganhando! R$ 800,00 para PARAR vs R$ 500,00 para AJUDAR!"
"🔥 Guerra de vaquinhas! Os trolls estão na frente com 61.5% das doações!"
"😱 O projeto está sendo BOICOTADO! Mais gente quer ver falhar do que dar certo!"
"🎭 Plot twist! A vaquinha virou uma guerra entre anjos e demônios! 😂"
```

## 📊 Exemplo de Resposta Completa

```json
{
  "project": {
    "id": 1,
    "name": "Vaquinha do João",
    "category": "Shark Tank",
    "budget": 2000.00,
    "current_amount": 551.00,
    "progress_percentage": 27.55,
    "time_remaining": "30 dias restantes",
    "is_goal_reached": false
  },
  "fundraising_stats": {
    "help_amount": 300.00,
    "stop_amount": 251.00,
    "total_amount": 551.00,
    "help_percentage": 54.45,
    "stop_percentage": 45.55,
    "stop_wins": false,
    "troll_message": null,
    "help_count": 3,
    "stop_count": 2
  },
  "daily_ranking": [
    {
      "donor_name": "Rei da Vaquinha",
      "amount": 500.00
    },
    {
      "donor_name": "Maria Santos", 
      "amount": 50.00
    },
    {
      "donor_name": "Mão de Alface",
      "amount": 1.00
    }
  ],
  "top_donor": "Rei da Vaquinha 👑",
  "lowest_donor": "Mão de Alface 🥬"
}
```

## 📊 Exemplo de Estatísticas de Arrecadação

```json
{
  "project_id": 1,
  "project_name": "Vaquinha do João",
  "help_amount": 300.00,
  "stop_amount": 800.00,
  "total_amount": 1100.00,
  "help_percentage": 27.27,
  "stop_percentage": 72.73,
  "stop_wins": true,
  "troll_message": "🚨 ALERTA! O projeto 'Vaquinha do João' está sendo SABOTADO! 😈",
  "help_count": 3,
  "stop_count": 5
}
```

## 🧪 Testes Incluídos

A coleção inclui endpoints de teste para:
- ✅ Categorização automática
- ✅ Mensagens de zoeira
- ✅ Sistema de ranking
- ✅ Progresso de metas
- ✅ Diferentes tipos de doações
- ✅ Sistema de guerra de vaquinhas (help vs stop)
- ✅ Estatísticas de arrecadação com porcentagens
- ✅ Mensagens engraçadas quando stop > help

## 🎯 Próximos Passos

1. Teste todos os endpoints na ordem sugerida
2. Experimente diferentes valores para ver as categorizações
3. Faça várias doações para testar o ranking
4. Verifique as mensagens de zoeira automáticas

---

**Divirta-se testando o sistema! 🎉**
