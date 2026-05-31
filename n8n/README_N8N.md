# WhatsApp via N8N — Guia de Configuração
## Gerenciador de Equipes · Igreja Verbo da Vida

---

## Estrutura dos arquivos

```
n8n/
├── workflow_enviar_confirmacao.json   ← Importar no N8N (Workflow 1)
├── workflow_receber_resposta.json     ← Importar no N8N (Workflow 2)
└── README_N8N.md                     ← Este arquivo
```

O `whatsapp_schema.sql` continua necessário — importe-o no banco antes de ativar os workflows.

---

## Passo 1 — Credenciais no N8N Cloud

Antes de importar os workflows, crie as credenciais em:
**N8N Cloud → Settings → Credentials → Add Credential**

### Credencial 1: MySQL
- Tipo: `MySQL`
- Name: `gerenciador_equipes`
- Host: seu host MySQL
- Database: `gerenciador_equipes`
- User / Password: seus dados

### Credencial 2: WhatsApp Bearer Token
- Tipo: `Header Auth`
- Name: `WhatsApp Bearer Token`
- Name do header: `Authorization`
- Value: `Bearer SEU_ACCESS_TOKEN_AQUI`

> O Access Token está em: developers.facebook.com → App → WhatsApp → Primeiros passos

---

## Passo 2 — Importar os workflows

1. No N8N Cloud, clique em **+ New Workflow → Import from file**
2. Importe `workflow_enviar_confirmacao.json`
3. Repita para `workflow_receber_resposta.json`
4. Em cada workflow, abra cada node MySQL e HTTP e **vincule as credenciais criadas no Passo 1**
5. Ative os dois workflows (toggle no canto superior direito)

---

## Passo 3 — URLs dos webhooks do N8N

Após ativar, o N8N gera URLs públicas para cada webhook. Copie-as:

**Workflow 1 — Enviar confirmação:**
```
https://SEU-SUBDOMINIO.app.n8n.cloud/webhook/enviar-confirmacao
```
Use esta URL no `index.html` (constante `N8N_ENVIAR_URL`).

**Workflow 2 — Receber resposta:**
```
https://SEU-SUBDOMINIO.app.n8n.cloud/webhook/whatsapp-webhook
```
Use esta URL no painel da Meta como URL do webhook.

---

## Passo 4 — Configurar webhook na Meta

1. Acesse developers.facebook.com → seu App → WhatsApp → Configuração
2. Em "Webhook", clique em **Editar**
3. URL do callback: `https://SEU-SUBDOMINIO.app.n8n.cloud/webhook/whatsapp-webhook`
4. Token de verificação: qualquer string segura (ex: `igreja_verbo_2025`)
   - Este valor precisa bater com o que você configurou nas variáveis de ambiente do N8N (opcional — o workflow atual aceita qualquer token)
5. Clique em **Verificar e salvar**
6. Assine os campos: `messages` e `message_deliveries`

---

## Passo 5 — Criar o template na Meta

Acesse: business.facebook.com → WhatsApp → Modelos de mensagem → Criar modelo

- **Nome:** `confirmacao_escala`
- **Idioma:** Português (BR)
- **Categoria:** Utility
- **Corpo da mensagem:**

```
Olá, {{1}}! Você está escalado(a) para o ministério *{{2}}* no dia *{{3}}* às *{{4}}* como *{{5}}*.

Responda *CONFIRMAR* para confirmar sua presença ou *NÃO POSSO* se não puder comparecer.
```

- Parâmetros de exemplo:
  - {{1}} → Ana Souza
  - {{2}} → Louvor
  - {{3}} → 06/06/2025
  - {{4}} → 09:00
  - {{5}} → Violão

Aguarde aprovação da Meta (geralmente 24-48h).

---

## Passo 6 — Atualizar o index.html

No `index.html`, localize a constante:
```javascript
const N8N_ENVIAR_URL = 'https://SEU-SUBDOMINIO.app.n8n.cloud/webhook/enviar-confirmacao';
```
Substitua pela URL real do Workflow 1.

---

## Como o fluxo funciona

```
[Usuário clica "Enviar WhatsApp" na escala]
        ↓
[index.html faz POST para N8N — Workflow 1]
        ↓
[N8N busca escala + membro no MySQL]
[N8N gera token, registra em escala_confirmacoes]
[N8N envia template via API Meta]
[N8N registra resultado em whatsapp_envios]
        ↓
[Membro recebe mensagem no WhatsApp]
[Membro responde CONFIRMAR ou NÃO POSSO]
        ↓
[Meta envia resposta para N8N — Workflow 2]
[N8N normaliza texto, detecta palavra-chave]
[N8N atualiza escalas + escala_confirmacoes no MySQL]
[N8N responde ao membro com confirmação]
        ↓
[Status atualizado aparece na tabela de escalas]
```

---

## Testando com ngrok (desenvolvimento local)

Se o banco estiver local (XAMPP/Laragon):

1. Rode `ngrok http 80` no terminal
2. Configure a credencial MySQL no N8N apontando para o IP da sua máquina
3. A URL do webhook do N8N Cloud já é pública — não precisa do ngrok para receber eventos da Meta

> Atenção: o N8N Cloud já tem URL pública. O ngrok só seria necessário se você usasse N8N self-hosted.
