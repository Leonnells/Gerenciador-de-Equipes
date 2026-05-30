<?php
/**
 * GERENCIADOR DE EQUIPES — Envio de Confirmação via WhatsApp
 * Arquivo: whatsapp/envia_whatsapp.php
 *
 * Uso via API interna:
 *   POST api/index.php?recurso=whatsapp_enviar
 *   Body: {"escala_id": 5}
 *
 * Ou diretamente via CLI (cron):
 *   php envia_whatsapp.php --escala_id=5
 *
 * Fluxo:
 *  1. Busca a escala e o membro no banco
 *  2. Gera um token único para rastreamento
 *  3. Registra em escala_confirmacoes
 *  4. Envia mensagem via template aprovado (WhatsApp Cloud API)
 *  5. Registra o resultado em whatsapp_envios
 *  6. Retorna JSON com status
 */

require_once __DIR__ . '/config_whatsapp.php';

// ── Entrada ───────────────────────────────────────────────────
$escalaId = null;

// Via POST (chamado pelo frontend ou pela API)
if (php_sapi_name() !== 'cli') {
    header('Content-Type: application/json; charset=utf-8');
    $body     = json_decode(file_get_contents('php://input'), true) ?? [];
    $escalaId = (int)($body['escala_id'] ?? $_GET['escala_id'] ?? 0);
} else {
    // Via CLI / cron: php envia_whatsapp.php --escala_id=5
    $opts     = getopt('', ['escala_id:']);
    $escalaId = (int)($opts['escala_id'] ?? 0);
}

if (!$escalaId) {
    responder(['erro' => 'escala_id é obrigatório'], 400);
}

try {
    $pdo = wa_db();

    // ── 1. Busca escala + membro + ministério ─────────────────
    $st = $pdo->prepare("
        SELECT e.id AS escala_id, e.data_escala, e.horario, e.funcao, e.status,
               mem.id   AS membro_id,
               mem.nome AS membro_nome,
               mem.telefone,
               min.nome AS ministerio
        FROM escalas e
        LEFT JOIN membros     mem ON mem.id = e.membro_id
        LEFT JOIN ministerios min ON min.id = e.ministerio_id
        WHERE e.id = ?
    ");
    $st->execute([$escalaId]);
    $escala = $st->fetch();

    if (!$escala) {
        responder(['erro' => 'Escala não encontrada'], 404);
    }
    if (empty($escala['telefone'])) {
        responder(['erro' => 'Membro sem telefone cadastrado'], 422);
    }

    // ── 2. Formata telefone para E.164 (+55DDNÚMERO) ─────────
    $telefone = formatarTelefone($escala['telefone']);
    if (!$telefone) {
        responder(['erro' => 'Telefone inválido: ' . $escala['telefone']], 422);
    }

    // ── 3. Gera token único ───────────────────────────────────
    $token = bin2hex(random_bytes(32)); // 64 chars hex

    // ── 4. Verifica se já existe confirmação ativa ────────────
    $check = $pdo->prepare("SELECT id, status FROM escala_confirmacoes WHERE escala_id=? AND status NOT IN ('confirmado','recusado')");
    $check->execute([$escalaId]);
    $existente = $check->fetch();

    if ($existente) {
        // Já existe — apenas reenvia usando o registro existente
        $confirmacaoId = $existente['id'];
        $pdo->prepare("UPDATE escala_confirmacoes SET tentativas=tentativas+1, atualizado_em=NOW() WHERE id=?")
            ->execute([$confirmacaoId]);
        // Recupera token original
        $tk = $pdo->prepare("SELECT token FROM escala_confirmacoes WHERE id=?");
        $tk->execute([$confirmacaoId]); $token = $tk->fetchColumn();
    } else {
        // ── 5. Insere registro de confirmação ─────────────────
        $pdo->prepare("
            INSERT INTO escala_confirmacoes
              (escala_id, membro_id, telefone, token, status, tentativas)
            VALUES (?, ?, ?, ?, 'pendente', 1)
        ")->execute([$escalaId, $escala['membro_id'], $telefone, $token]);
        $confirmacaoId = (int)$pdo->lastInsertId();
    }

    // ── 6. Monta os parâmetros do template ───────────────────
    // Template esperado (cadastre exatamente assim na Meta):
    // Nome: confirmacao_escala | Idioma: pt_BR
    //
    // Corpo do template:
    // "Olá, {{1}}! Você está escalado(a) para o ministério *{{2}}* no dia
    //  *{{3}}* às *{{4}}* como *{{5}}*.
    //
    //  Responda *CONFIRMAR* para confirmar sua presença ou
    //  *NÃO POSSO* se não puder comparecer."
    //
    // Parâmetros: nome, ministério, data, horário, função

    $params = [
        $escala['membro_nome'],                          // {{1}} nome
        $escala['ministerio'] ?? 'ministério',           // {{2}} ministério
        date('d/m/Y', strtotime($escala['data_escala'])),// {{3}} data
        substr($escala['horario'], 0, 5),                // {{4}} horário
        $escala['funcao'] ?? 'voluntário(a)',             // {{5}} função
    ];

    // ── 7. Chama a API WhatsApp Cloud ─────────────────────────
    $resultado = enviarTemplate($telefone, WA_TEMPLATE_NOME, WA_TEMPLATE_IDIOMA, $params);

    // ── 8. Registra o envio ───────────────────────────────────
    $statusApi = isset($resultado['messages'][0]['id']) ? 'sent' : 'failed';
    $messageId = $resultado['messages'][0]['id'] ?? null;
    $erro      = isset($resultado['error']) ? json_encode($resultado['error']) : null;

    $pdo->prepare("
        INSERT INTO whatsapp_envios
          (confirmacao_id, message_id, status_api, payload_enviado, resposta_api, erro)
        VALUES (?, ?, ?, ?, ?, ?)
    ")->execute([
        $confirmacaoId,
        $messageId,
        $statusApi,
        json_encode(['template'=>WA_TEMPLATE_NOME,'params'=>$params,'telefone'=>$telefone]),
        json_encode($resultado),
        $erro,
    ]);

    // ── 9. Atualiza status da confirmação ─────────────────────
    $novoStatus = $statusApi === 'sent' ? 'enviado' : 'erro_envio';
    $pdo->prepare("UPDATE escala_confirmacoes SET status=? WHERE id=?")
        ->execute([$novoStatus, $confirmacaoId]);

    // ── 10. Também atualiza campo status da escala se enviado ─
    if ($novoStatus === 'enviado') {
        $pdo->prepare("UPDATE escalas SET status='pendente' WHERE id=? AND status='pendente'")
            ->execute([$escalaId]);
    }

    wa_log("Envio $statusApi", ['escala_id'=>$escalaId,'telefone'=>$telefone,'message_id'=>$messageId]);

    responder([
        'sucesso'        => $statusApi === 'sent',
        'status'         => $novoStatus,
        'confirmacao_id' => $confirmacaoId,
        'message_id'     => $messageId,
        'membro'         => $escala['membro_nome'],
        'telefone'       => $telefone,
        'erro'           => $erro,
    ]);

} catch (PDOException $e) {
    wa_log('Erro BD', ['msg' => $e->getMessage()]);
    responder(['erro' => 'Erro de banco: ' . $e->getMessage()], 500);
} catch (Throwable $e) {
    wa_log('Erro geral', ['msg' => $e->getMessage()]);
    responder(['erro' => 'Erro interno: ' . $e->getMessage()], 500);
}

// ════════════════════════════════════════════════════════════════
// FUNÇÕES AUXILIARES
// ════════════════════════════════════════════════════════════════

/**
 * Envia um template via WhatsApp Cloud API.
 * Retorna o JSON decodificado da resposta.
 */
function enviarTemplate(string $telefone, string $template, string $idioma, array $params): array {
    // Monta componentes de corpo
    $componentes = [];
    if (!empty($params)) {
        $parametros = array_map(fn($p) => ['type' => 'text', 'text' => (string)$p], $params);
        $componentes[] = ['type' => 'body', 'parameters' => $parametros];
    }

    $payload = [
        'messaging_product' => 'whatsapp',
        'to'                => $telefone,
        'type'              => 'template',
        'template'          => [
            'name'       => $template,
            'language'   => ['code' => $idioma],
            'components' => $componentes,
        ],
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
        CURLOPT_TIMEOUT        => 15,
    ]);

    $resposta = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlErro = curl_error($ch);
    curl_close($ch);

    if ($curlErro) {
        wa_log('cURL erro', ['erro' => $curlErro]);
        return ['error' => ['message' => $curlErro]];
    }

    $json = json_decode($resposta, true) ?? [];
    wa_log("API Meta HTTP $httpCode", ['response' => substr($resposta, 0, 300)]);
    return $json;
}

/**
 * Formata telefone para E.164.
 * Aceita formatos: (14) 99999-9999 | 14999999999 | +5514999999999
 */
function formatarTelefone(string $tel): ?string {
    $digitos = preg_replace('/\D/', '', $tel);
    // Já tem código de país
    if (strlen($digitos) === 13 && str_starts_with($digitos, '55')) return '+' . $digitos;
    // Com DDD 11 dígitos (celular BR)
    if (strlen($digitos) === 11) return '+55' . $digitos;
    // Com DDD 10 dígitos (fixo BR)
    if (strlen($digitos) === 10) return '+55' . $digitos;
    return null;
}

function responder(array $data, int $code = 200): void {
    if (php_sapi_name() !== 'cli') {
        http_response_code($code);
        echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    } else {
        echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) . PHP_EOL;
    }
    exit;
}
