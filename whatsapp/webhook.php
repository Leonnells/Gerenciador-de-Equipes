<?php
/**
 * GERENCIADOR DE EQUIPES — Webhook WhatsApp
 * Arquivo: whatsapp/webhook.php
 *
 * Este arquivo deve ser acessível publicamente via HTTPS.
 * Configure a URL no painel da Meta:
 *   developers.facebook.com > App > WhatsApp > Configuração
 *   URL do webhook: https://seusite.com.br/whatsapp/webhook.php
 *   Token de verificação: valor definido em WA_VERIFY_TOKEN
 *
 * Responsabilidades:
 *  1. GET  — verificação do webhook pela Meta (handshake inicial)
 *  2. POST — recebe eventos: mensagens recebidas + status de entrega
 *
 * Fluxo de confirmação por palavra-chave:
 *  Membro responde "CONFIRMAR" → status escala = confirmado
 *  Membro responde "NÃO POSSO" → status escala = ausente
 *  Resposta não reconhecida   → envia mensagem de ajuda de volta
 */

require_once __DIR__ . '/config_whatsapp.php';

// ════════════════════════════════════════════════════════════════
// GET — Verificação do webhook pela Meta
// ════════════════════════════════════════════════════════════════
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $mode      = $_GET['hub_mode']          ?? '';
    $token     = $_GET['hub_verify_token']  ?? '';
    $challenge = $_GET['hub_challenge']     ?? '';

    if ($mode === 'subscribe' && $token === WA_VERIFY_TOKEN) {
        wa_log('Webhook verificado com sucesso');
        http_response_code(200);
        echo $challenge;
    } else {
        wa_log('Webhook: verificação falhou', ['token_recebido' => $token]);
        http_response_code(403);
        echo 'Forbidden';
    }
    exit;
}

// ════════════════════════════════════════════════════════════════
// POST — Eventos recebidos da Meta
// ════════════════════════════════════════════════════════════════
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Responde 200 imediatamente — Meta exige resposta em < 20s
    http_response_code(200);
    echo 'OK';

    // Lê e valida o payload
    $raw     = file_get_contents('php://input');
    $payload = json_decode($raw, true);

    if (!$payload || ($payload['object'] ?? '') !== 'whatsapp_business_account') {
        wa_log('Webhook: payload inválido ou objeto desconhecido');
        exit;
    }

    // Persiste o log bruto antes de qualquer processamento
    try {
        gravarLogWebhook($payload);
    } catch (Throwable $e) {
        wa_log('Erro ao gravar log webhook', ['erro' => $e->getMessage()]);
    }

    // Itera entradas e mudanças
    foreach ($payload['entry'] ?? [] as $entry) {
        foreach ($entry['changes'] ?? [] as $change) {
            $value = $change['value'] ?? [];

            // ── Mensagens recebidas ───────────────────────────
            foreach ($value['messages'] ?? [] as $mensagem) {
                try {
                    processarMensagem($mensagem, $value['metadata'] ?? []);
                } catch (Throwable $e) {
                    wa_log('Erro ao processar mensagem', [
                        'erro'     => $e->getMessage(),
                        'from'     => $mensagem['from'] ?? '?',
                        'msg_id'   => $mensagem['id']   ?? '?',
                    ]);
                }
            }

            // ── Status de entrega (delivered / read / failed) ─
            foreach ($value['statuses'] ?? [] as $status) {
                try {
                    processarStatus($status);
                } catch (Throwable $e) {
                    wa_log('Erro ao processar status', ['erro' => $e->getMessage()]);
                }
            }
        }
    }
    exit;
}

// Qualquer outro método
http_response_code(405);
echo 'Method Not Allowed';
exit;

// ════════════════════════════════════════════════════════════════
// PROCESSAMENTO DE MENSAGEM RECEBIDA
// ════════════════════════════════════════════════════════════════
function processarMensagem(array $msg, array $metadata): void {
    $de   = $msg['from']             ?? null;  // Ex: 5514999998888
    $tipo = $msg['type']             ?? '';
    $text = $msg['text']['body']     ?? '';    // Texto digitado pelo membro

    if (!$de || $tipo !== 'text' || !$text) {
        wa_log('Mensagem ignorada (sem texto ou tipo inválido)', ['tipo' => $tipo, 'de' => $de]);
        return;
    }

    $telefoneE164 = '+' . preg_replace('/\D/', '', $de);
    wa_log('Mensagem recebida', ['de' => $telefoneE164, 'texto' => $text]);

    $pdo = wa_db();

    // ── Busca confirmação pendente/enviada para este telefone ─
    $st = $pdo->prepare("
        SELECT ec.id AS confirmacao_id, ec.escala_id, ec.status, ec.token,
               e.data_escala, e.horario, e.funcao,
               mem.nome AS membro_nome,
               min.nome AS ministerio
        FROM escala_confirmacoes ec
        JOIN escalas      e   ON e.id   = ec.escala_id
        LEFT JOIN membros mem ON mem.id = ec.membro_id
        LEFT JOIN ministerios min ON min.id = e.ministerio_id
        WHERE ec.telefone = ?
          AND ec.status IN ('pendente', 'enviado')
        ORDER BY ec.criado_em DESC
        LIMIT 1
    ");
    $st->execute([$telefoneE164]);
    $confirmacao = $st->fetch();

    if (!$confirmacao) {
        wa_log('Nenhuma confirmação pendente para este número', ['telefone' => $telefoneE164]);
        // Responde informando que não há escala pendente
        enviarTexto($telefoneE164, "Olá! Não encontramos nenhuma escala pendente de confirmação para este número. Em caso de dúvidas, fale com seu supervisor.");
        marcarLogProcessado($pdo, $telefoneE164);
        return;
    }

    // ── Normaliza o texto recebido ────────────────────────────
    $textoNorm = normalizarTexto($text);

    // ── Verifica palavra-chave ────────────────────────────────
    if (contemPalavra($textoNorm, WA_PALAVRAS_CONFIRMAR)) {
        // Membro confirmou
        atualizarConfirmacao($pdo, $confirmacao['confirmacao_id'], $confirmacao['escala_id'], 'confirmado', $text);
        $resposta = "✅ Presença confirmada! Obrigado, {$confirmacao['membro_nome']}. Te esperamos no dia {$confirmacao['data_escala']} às " . substr($confirmacao['horario'],0,5) . " como *{$confirmacao['funcao']}* no ministério *{$confirmacao['ministerio']}*.";
        enviarTexto($telefoneE164, $resposta);
        wa_log('Presença confirmada', ['confirmacao_id' => $confirmacao['confirmacao_id']]);

    } elseif (contemPalavra($textoNorm, WA_PALAVRAS_RECUSAR)) {
        // Membro recusou
        atualizarConfirmacao($pdo, $confirmacao['confirmacao_id'], $confirmacao['escala_id'], 'recusado', $text);
        $resposta = "❌ Recebemos sua ausência, {$confirmacao['membro_nome']}. Seu supervisor será notificado. Qualquer dúvida, entre em contato.";
        enviarTexto($telefoneE164, $resposta);
        wa_log('Presença recusada', ['confirmacao_id' => $confirmacao['confirmacao_id']]);

    } else {
        // Resposta não reconhecida — envia instruções
        $ajuda = "Olá, {$confirmacao['membro_nome']}! Não entendemos sua resposta.\n\n"
               . "Você está escalado(a) para *{$confirmacao['ministerio']}* "
               . "em *" . date('d/m/Y', strtotime($confirmacao['data_escala'])) . "* "
               . "às *" . substr($confirmacao['horario'],0,5) . "* "
               . "como *{$confirmacao['funcao']}*.\n\n"
               . "Responda:\n"
               . "✅ *CONFIRMAR* — para confirmar presença\n"
               . "❌ *NÃO POSSO* — se não puder comparecer";
        enviarTexto($telefoneE164, $ajuda);
        wa_log('Resposta não reconhecida', ['texto' => $text]);
    }

    marcarLogProcessado($pdo, $telefoneE164);
}

// ════════════════════════════════════════════════════════════════
// PROCESSAMENTO DE STATUS DE ENTREGA
// ════════════════════════════════════════════════════════════════
function processarStatus(array $status): void {
    $messageId    = $status['id']         ?? null;
    $statusNome   = $status['status']     ?? null;   // sent / delivered / read / failed
    $telefone     = '+' . preg_replace('/\D/', '', $status['recipient_id'] ?? '');

    if (!$messageId || !$statusNome) return;

    wa_log("Status entrega: $statusNome", ['message_id' => $messageId, 'telefone' => $telefone]);

    $pdo = wa_db();

    // Atualiza o log de envio com o status mais recente
    $pdo->prepare("UPDATE whatsapp_envios SET status_api=? WHERE message_id=?")
        ->execute([$statusNome, $messageId]);

    // Se falhou na entrega, marca confirmação como erro
    if ($statusNome === 'failed') {
        $st = $pdo->prepare("
            UPDATE escala_confirmacoes ec
            JOIN whatsapp_envios we ON we.confirmacao_id = ec.id
            SET ec.status = 'erro_envio'
            WHERE we.message_id = ? AND ec.status = 'enviado'
        ");
        $st->execute([$messageId]);
        wa_log('Entrega falhou, status revertido para erro_envio', ['message_id' => $messageId]);
    }
}

// ════════════════════════════════════════════════════════════════
// ATUALIZA CONFIRMAÇÃO E ESCALA NO BANCO
// ════════════════════════════════════════════════════════════════
function atualizarConfirmacao(PDO $pdo, int $confirmacaoId, int $escalaId, string $decisao, string $respostaRaw): void {
    // Atualiza a confirmação
    $pdo->prepare("
        UPDATE escala_confirmacoes
        SET status = ?, respondido_em = NOW(), resposta_raw = ?
        WHERE id = ?
    ")->execute([$decisao, $respostaRaw, $confirmacaoId]);

    // Atualiza o status da escala principal
    $statusEscala = $decisao === 'confirmado' ? 'confirmado' : 'ausente';
    $pdo->prepare("UPDATE escalas SET status = ? WHERE id = ?")
        ->execute([$statusEscala, $escalaId]);

    wa_log("Escala $escalaId → $statusEscala");
}

// ════════════════════════════════════════════════════════════════
// ENVIA MENSAGEM DE TEXTO SIMPLES (RESPOSTA AO MEMBRO)
// ════════════════════════════════════════════════════════════════
function enviarTexto(string $telefone, string $texto): void {
    $payload = [
        'messaging_product' => 'whatsapp',
        'to'                => $telefone,
        'type'              => 'text',
        'text'              => ['body' => $texto],
    ];

    $url = WA_API_URL . WA_PHONE_NUMBER_ID . '/messages';
    $ch  = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => json_encode($payload),
        CURLOPT_HTTPHEADER     => [
            'Content-Type: application/json',
            'Authorization: Bearer ' . WA_TOKEN,
        ],
        CURLOPT_TIMEOUT => 10,
    ]);
    $resp = curl_exec($ch);
    $err  = curl_error($ch);
    curl_close($ch);

    if ($err) wa_log('Erro cURL ao responder membro', ['erro' => $err]);
    else      wa_log('Resposta enviada ao membro', ['para' => $telefone, 'preview' => substr($texto,0,60)]);
}

// ════════════════════════════════════════════════════════════════
// HELPERS
// ════════════════════════════════════════════════════════════════

/** Normaliza texto: remove acentos, converte para minúsculo, trim */
function normalizarTexto(string $texto): string {
    $texto = mb_strtolower(trim($texto), 'UTF-8');
    $de    = ['á','à','ã','â','é','ê','í','ó','ô','õ','ú','ü','ç','ñ'];
    $para  = ['a','a','a','a','e','e','i','o','o','o','u','u','c','n'];
    return str_replace($de, $para, $texto);
}

/** Verifica se o texto normalizado contém alguma das palavras-chave */
function contemPalavra(string $textoNorm, array $palavras): bool {
    foreach ($palavras as $p) {
        if (str_contains($textoNorm, normalizarTexto($p))) return true;
    }
    return false;
}

/** Grava o payload bruto do webhook no log */
function gravarLogWebhook(array $payload): void {
    $pdo = wa_db();

    // Tenta extrair campos principais para facilitar consultas
    $entry   = $payload['entry'][0]                    ?? [];
    $change  = $entry['changes'][0]['value']           ?? [];
    $msg     = $change['messages'][0]                  ?? null;
    $status  = $change['statuses'][0]                  ?? null;

    $tipo     = $msg    ? 'messages' : ($status ? 'statuses' : 'outros');
    $de       = $msg    ? ('+' . preg_replace('/\D/', '', $msg['from'] ?? ''))
              : ($status ? ('+' . preg_replace('/\D/', '', $status['recipient_id'] ?? '')) : null);
    $conteudo = $msg    ? ($msg['text']['body'] ?? json_encode($msg))
              : ($status ? ($status['status'] ?? '') : json_encode($payload));

    $pdo->prepare("
        INSERT INTO webhook_logs (tipo, de, conteudo, payload_raw, processado)
        VALUES (?, ?, ?, ?, 0)
    ")->execute([$tipo, $de, $conteudo, json_encode($payload, JSON_UNESCAPED_UNICODE)]);
}

/** Marca todos os logs deste número como processados */
function marcarLogProcessado(PDO $pdo, string $telefone): void {
    $pdo->prepare("UPDATE webhook_logs SET processado=1 WHERE de=? AND processado=0")
        ->execute([$telefone]);
}
