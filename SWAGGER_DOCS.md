# 📚 Documentação da API - Swagger

## ✅ Swagger Implementado com Sucesso!

A documentação interativa da API foi implementada usando o **L5-Swagger** e está disponível em:

### 🔗 **Acesso à Documentação**

**URL da Documentação Swagger UI:**
```
http://localhost:8000/api/documentation
```

**URL do JSON da API:**
```
http://localhost:8000/docs/api-docs.json
```

### 📋 **Funcionalidades Documentadas**

#### 🏷️ **Tags Organizadas:**
- **Projetos** - Gerenciamento de projetos/vaquinhas
- **Doações** - Gerenciamento de doações
- **Pagamentos** - Integração com Asaas (PIX, Boleto)
- **Webhooks** - Endpoints para webhooks do Asaas

#### 📊 **Schemas Definidos:**
- **Project** - Estrutura completa de um projeto
- **Donate** - Estrutura de uma doação
- **Error** - Padrão de resposta de erro

#### 🔗 **Endpoints Documentados:**

**Projetos:**
- `GET /api/projects` - Listar todos os projetos
- `POST /api/projects` - Criar novo projeto
- `GET /api/projects/{id}` - Buscar projeto específico
- `PUT /api/projects/{id}` - Atualizar projeto
- `DELETE /api/projects/{id}` - Excluir projeto

**Pagamentos:**
- `POST /api/donates/pix` - Criar cobrança PIX
- `POST /api/donates/boleto` - Criar cobrança boleto
- `GET /api/donates/{id}/status` - Verificar status de pagamento

**Webhooks:**
- `POST /api/webhooks/asaas` - Receber webhooks do Asaas
- `POST /api/webhooks/asaas/test` - Testar webhook (desenvolvimento)

### 🛠️ **Como Usar**

1. **Acesse a documentação:**
   ```
   http://localhost:8000/api/documentation
   ```

2. **Teste os endpoints:**
   - Clique em qualquer endpoint
   - Clique em "Try it out"
   - Preencha os parâmetros
   - Clique em "Execute"

3. **Veja os exemplos:**
   - Cada endpoint tem exemplos de request/response
   - Schemas detalhados para cada modelo
   - Códigos de status HTTP explicados

### 🔧 **Comandos Úteis**

**Regenerar documentação:**
```bash
php artisan l5-swagger:generate
```

**Limpar cache:**
```bash
php artisan config:clear
php artisan l5-swagger:generate
```

### 📝 **Adicionando Novos Endpoints**

Para documentar novos endpoints:

1. **Adicione anotações no controller:**
```php
/**
 * @OA\Post(
 *     path="/api/novo-endpoint",
 *     summary="Descrição do endpoint",
 *     tags={"Tag"},
 *     @OA\RequestBody(...),
 *     @OA\Response(...)
 * )
 */
public function novoMetodo(Request $request) {
    // código do método
}
```

2. **Regenere a documentação:**
```bash
php artisan l5-swagger:generate
```

### 🎯 **Recursos do Swagger**

- ✅ **Interface interativa** para testar endpoints
- ✅ **Validação automática** de parâmetros
- ✅ **Exemplos de request/response**
- ✅ **Schemas detalhados** dos modelos
- ✅ **Códigos de erro** documentados
- ✅ **Autenticação** preparada para implementar
- ✅ **Exportação** para Postman/Insomnia

### 🚀 **Próximos Passos**

1. **Adicionar autenticação** JWT/Bearer Token
2. **Documentar mais endpoints** conforme necessário
3. **Adicionar exemplos** mais detalhados
4. **Implementar rate limiting** documentado
5. **Adicionar testes** automatizados baseados no Swagger

---

**🎉 A documentação está completa e pronta para uso!**

Acesse: `http://localhost:8000/api/documentation`
