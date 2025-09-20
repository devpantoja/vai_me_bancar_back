# Integração com Asaas - Configuração

## ✅ Integração Implementada

A integração completa com o Asaas foi implementada com sucesso! Aqui está o que foi criado:

### 🔧 Arquivos Criados/Modificados

1. **`config/services.php`** - Configuração das credenciais do Asaas
2. **`app/Services/AsaasService.php`** - Serviço para comunicação com a API do Asaas
3. **`app/Http/Controllers/AsaasWebhookController.php`** - Controller para webhooks
4. **`app/Http/Controllers/DonateController.php`** - Métodos de pagamento adicionados
5. **`routes/api.php`** - Novas rotas para pagamentos e webhooks

### 🚀 Funcionalidades Implementadas

#### Criação de Cobranças
- ✅ **PIX**: Criação de cobranças PIX com QR Code
- ✅ **Boleto**: Criação de cobranças via boleto bancário
- ✅ **Cartão de Crédito**: Estrutura preparada (pode ser expandida)

#### Webhooks
- ✅ **Recebimento de notificações** do Asaas
- ✅ **Atualização automática** do status das doações
- ✅ **Validação de segurança** com token

#### Gestão de Pagamentos
- ✅ **Verificação de status** de cobranças
- ✅ **Mapeamento de status** do Asaas para o sistema
- ✅ **Atualização automática** do progresso dos projetos

### 📋 Configuração Necessária

#### 1. Variáveis de Ambiente (.env)

Adicione as seguintes variáveis ao seu arquivo `.env`:

```env
# Configurações do Asaas
ASAAS_API_KEY=your_asaas_api_key_here
ASAAS_ENVIRONMENT=sandbox
ASAAS_BASE_URL=https://sandbox.asaas.com/api/v3
ASAAS_WEBHOOK_TOKEN=your_webhook_token_here
```

#### 2. Configuração no Asaas

1. **Acesse sua conta Asaas** (sandbox ou produção)
2. **Obtenha sua API Key** nas configurações
3. **Configure o Webhook**:
   - URL: `https://seudominio.com/api/webhooks/asaas`
   - Eventos: `PAYMENT_CONFIRMED`, `PAYMENT_RECEIVED`, `PAYMENT_OVERDUE`, `PAYMENT_DELETED`
   - Token: Use o mesmo valor de `ASAAS_WEBHOOK_TOKEN`

### 🔗 Endpoints Disponíveis

#### Criação de Pagamentos

**PIX Payment**
```http
POST /api/donates/pix
Content-Type: application/json

{
  "amount": 100.00,
  "project_id": 1,
  "donor_name": "João Silva",
  "donor_email": "joao@email.com",
  "donor_cpf": "12345678901",
  "donor_phone": "11999999999",
  "description": "Doação para o projeto"
}
```

**Boleto Payment**
```http
POST /api/donates/boleto
Content-Type: application/json

{
  "amount": 100.00,
  "project_id": 1,
  "donor_name": "João Silva",
  "donor_email": "joao@email.com",
  "donor_cpf": "12345678901",
  "donor_phone": "11999999999",
  "donor_address": "Rua das Flores, 123",
  "donor_city": "São Paulo",
  "donor_state": "SP",
  "donor_zipcode": "01234567",
  "description": "Doação para o projeto"
}
```

#### Verificação de Status
```http
GET /api/donates/{donate_id}/status
```

#### Webhook (configurado automaticamente)
```http
POST /api/webhooks/asaas
```

### 🧪 Testando a Integração

#### 1. Teste de Webhook (desenvolvimento)
```http
POST /api/webhooks/asaas/test
```

#### 2. Criar uma cobrança PIX
```bash
curl -X POST http://localhost:8000/api/donates/pix \
  -H "Content-Type: application/json" \
  -d '{
    "amount": 50.00,
    "project_id": 1,
    "donor_name": "Teste",
    "donor_email": "teste@email.com",
    "donor_cpf": "12345678901",
    "donor_phone": "11999999999"
  }'
```

### 📊 Fluxo de Pagamento

1. **Cliente faz doação** → Sistema cria cliente e cobrança no Asaas
2. **Asaas retorna** → QR Code PIX ou URL do boleto
3. **Cliente paga** → Asaas processa o pagamento
4. **Webhook recebido** → Sistema atualiza status automaticamente
5. **Projeto atualizado** → Progresso e meta atualizados

### 🔒 Segurança

- ✅ **Validação de webhook** com token
- ✅ **Logs detalhados** para auditoria
- ✅ **Tratamento de erros** robusto
- ✅ **Validação de dados** de entrada

### 📈 Próximos Passos (Opcionais)

1. **Implementar cartão de crédito** com tokenização
2. **Adicionar parcelamento** para valores maiores
3. **Implementar split de pagamento** para taxas
4. **Adicionar relatórios** de pagamentos
5. **Implementar estornos** automáticos

### 🆘 Suporte

Se encontrar problemas:

1. **Verifique os logs** em `storage/logs/laravel.log`
2. **Confirme as credenciais** do Asaas
3. **Teste o webhook** com o endpoint de teste
4. **Verifique a documentação** oficial do Asaas

---

**🎉 A integração está completa e pronta para uso!**
