# Documentação Swagger - Vai Me Bancar API

## Acesso à Documentação

A documentação Swagger da API está disponível nos seguintes endereços:

### Interface Swagger UI
- **Produção**: https://vaimebancar.codegus.com/api/documentation
- **Desenvolvimento**: http://localhost:8000/api/documentation

### Documentação JSON
- **Produção**: https://vaimebancar.codegus.com/docs/api-docs.json
- **Desenvolvimento**: http://localhost:8000/docs/api-docs.json

## Endpoints Documentados

### Projetos
- `GET /api/projects` - Listar todos os projetos
- `POST /api/projects` - Criar novo projeto
- `GET /api/projects/{id}` - Buscar projeto específico
- `PUT /api/projects/{id}` - Atualizar projeto
- `DELETE /api/projects/{id}` - Excluir projeto
- `GET /api/projects/{id}/info` - Informações detalhadas do projeto
- `GET /api/projects/{id}/ranking` - Ranking diário do projeto
- `POST /api/projects/{id}/troll-message` - Gerar mensagem de zoeira

### Doações
- `GET /api/donates` - Listar todas as doações
- `POST /api/donates` - Criar nova doação
- `GET /api/donates/{id}` - Buscar doação específica
- `PUT /api/donates/{id}` - Atualizar doação
- `DELETE /api/donates/{id}` - Excluir doação
- `GET /api/projects/{id}/donates` - Listar doações de um projeto

### Pagamentos (Integração Asaas)
- `POST /api/donates/pix` - Criar cobrança PIX
- `POST /api/donates/boleto` - Criar cobrança via boleto
- `GET /api/donates/{id}/status` - Verificar status de pagamento

### Webhooks
- `POST /api/webhooks/asaas` - Receber webhooks do Asaas
- `POST /api/webhooks/asaas/test` - Testar webhook do Asaas

## Comandos Úteis

### Regenerar Documentação
```bash
php artisan l5-swagger:generate
```

### Limpar Cache
```bash
php artisan config:clear
php artisan route:clear
php artisan cache:clear
```

### Verificar Rotas
```bash
php artisan route:list --path=api
```

## Configuração

A documentação Swagger está configurada para usar o servidor de produção:
- **URL Base**: https://vaimebancar.codegus.com
- **Descrição**: Servidor de produção

## Schemas Definidos

- **Project**: Modelo de projeto/vaquinha
- **Donate**: Modelo de doação
- **Error**: Modelo de erro padrão

## Tags Organizadas

- **Projetos**: Endpoints para gerenciamento de projetos/vaquinhas
- **Doações**: Endpoints para gerenciamento de doações
- **Pagamentos**: Endpoints para integração com Asaas (PIX, Boleto)
- **Webhooks**: Endpoints para webhooks do Asaas

## Exemplos de Uso

### Criar Projeto
```json
POST /api/projects
{
  "name": "Vaquinha do João",
  "description": "João precisa de dinheiro para comprar um novo celular",
  "budget": 1500.00,
  "start_date": "2025-01-20",
  "end_date": "2025-02-20",
  "owner_name": "João Silva",
  "cellphone": "11999999999"
}
```

### Criar Cobrança PIX
```json
POST /api/donates/pix
{
  "amount": 100.00,
  "project_id": 1,
  "donor_name": "Maria Santos",
  "donor_email": "maria@email.com",
  "donor_cpf": "11144477735",
  "donor_phone": "11888888888",
  "description": "Doação para o projeto"
}
```

### Criar Cobrança Boleto
```json
POST /api/donates/boleto
{
  "amount": 250.00,
  "project_id": 1,
  "donor_name": "Pedro Costa",
  "donor_email": "pedro@email.com",
  "donor_cpf": "11144477735",
  "donor_phone": "11777777777",
  "donor_address": "Rua das Flores, 123",
  "donor_city": "São Paulo",
  "donor_state": "SP",
  "donor_zipcode": "01234567",
  "description": "Doação para o projeto"
}
```

## Notas Importantes

1. **Autenticação**: A API não requer autenticação para os endpoints públicos
2. **Validação**: Todos os endpoints possuem validação de dados
3. **Integração Asaas**: Os pagamentos são processados através da API do Asaas
4. **Webhooks**: Os webhooks do Asaas são processados automaticamente
5. **Gamificação**: O sistema inclui funcionalidades de gamificação e mensagens de zoeira

## Suporte

Para dúvidas ou problemas, entre em contato:
- **Email**: contato@vaimebancar.com
- **Documentação**: https://vaimebancar.codegus.com/api/documentation