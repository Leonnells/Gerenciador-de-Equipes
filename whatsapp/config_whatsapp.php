<?php
/**
 * GERENCIADOR DE EQUIPES — Configuração WhatsApp
 * Arquivo: whatsapp/config_whatsapp.php
 *
 * ⚠️  NUNCA versione este arquivo com credenciais reais.
 *     Adicione ao .gitignore se usar controle de versão.
 *
 * Como obter as credenciais:
 *  1. Acesse developers.facebook.com
 *  2. Crie um app do tipo "Business"
 *  3. Adicione o produto "WhatsApp"
 *  4. Em "Primeiros passos" você encontra Phone Number ID e Token
 *  5. O Verify Token é criado por você (qualquer string segura)
 *  6. Configure o webhook apontando para sua URL pública + /webhook.php
 */

// ── Credenciais da API WhatsApp Cloud (Meta) ─────────────────
define('WA_TOKEN',           'SEU_ACCESS_TOKEN_AQUI');       // Token temporário ou permanente da Meta
define('WA_PHONE_NUMBER_ID', 'SEU_PHONE_NUMBER_ID_AQUI');    // ID do número de telefone cadastrado
define('WA_API_VERSION',     'v19.0');                        // Versão da Graph API
define('WA_VERIFY_TOKEN',    'meu_token_secreto_webhook');   // Token de verificação do webhook (você define)

// ── URL base da Graph API ─────────────────────────────────────
define('WA_API_URL', 'https://graph.facebook.com/' . WA_API_VERSION . '/');

// ── Nome do template aprovado na Meta ────────────────────────
// O template precisa estar aprovado antes de ser usado.
// Crie em: business.facebook.com > WhatsApp > Modelos de mensagem
define('WA_TEMPLATE_NOME',   'confirmacao_escala');   // Nome exato do template aprovado
define('WA_TEMPLATE_IDIOMA', 'pt_BR');                // Idioma do template

// ── URL pública do sistema (para montar o link de fallback) ──
// Usado no corpo da mensagem como referência adicional.
// Não é necessário para fluxo por palavra-chave, mas útil.
define('APP_URL', 'https://seusite.com.br');

// ── Palavras-chave reconhecidas pelo webhook ──────────────────
// O sistema normaliza a resposta (remove acentos, maiúsculas)
// antes de comparar. Adicione variações conforme necessário.
define('WA_PALAVRAS_CONFIRMAR', ['confirmar', 'sim', 'confirmo', 'presente', 'ok', 'vou']);
define('WA_PALAVRAS_RECUSAR',   ['nao posso', 'não posso', 'nao', 'não', 'faltarei', 'ausente', 'impossivel', 'impossível']);

// ── Banco de dados (pode reaproveitar as constantes do api/index.php) ──
define('WA_DB_HOST',    'localhost');
define('WA_DB_NAME',    'gerenciador_equipes');
define('WA_DB_USER',    'root');
define('WA_DB_PASS',    '');
define('WA_DB_CHARSET', 'utf8mb4');

// ── Conexão PDO compartilhada ─────────────────────────────────
function wa_db(): PDO {
    static $pdo = null;
    if ($pdo === null) {
        $pdo = new PDO(
            'mysql:host='.WA_DB_HOST.';dbname='.WA_DB_NAME.';charset='.WA_DB_CHARSET,
            WA_DB_USER, WA_DB_PASS,
            [PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
             PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]
        );
    }
    return $pdo;
}

// ── Log simples para debug ────────────────────────────────────
function wa_log(string $msg, array $ctx = []): void {
    $linha = date('Y-m-d H:i:s') . ' | ' . $msg;
    if ($ctx) $linha .= ' | ' . json_encode($ctx, JSON_UNESCAPED_UNICODE);
    error_log($linha);
}
