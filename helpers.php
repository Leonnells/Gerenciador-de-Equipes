<?php
/**
 * GERENCIADOR DE EQUIPES — Funções Auxiliares
 * Arquivo: helpers/helpers.php
 *
 * Funções utilitárias compartilhadas por todos os Controllers.
 * Carregado pelo router.php antes de qualquer Controller.
 */

/** Envia cabeçalhos CORS + JSON para todas as respostas */
function cabecalhos(): void {
    header('Content-Type: application/json; charset=utf-8');
    header('Access-Control-Allow-Origin: *');
    header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
    header('Access-Control-Allow-Headers: Content-Type, Authorization');
}

/** Resposta JSON de sucesso */
function resposta(array $dados, int $codigo = 200): void {
    http_response_code($codigo);
    echo json_encode($dados, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    exit;
}

/** Resposta JSON de erro — encerra a execução */
function erro(string $mensagem, int $codigo = 400): void {
    resposta(['erro' => $mensagem, 'codigo' => $codigo], $codigo);
}

/** Lê e decodifica o corpo JSON da requisição */
function corpo(): array {
    return json_decode(file_get_contents('php://input'), true) ?? [];
}

/** Retorna o método HTTP atual em maiúsculas */
function metodo(): string {
    return strtoupper($_SERVER['REQUEST_METHOD']);
}

/** Retorna o ?id=N da URL como inteiro ou null */
function urlId(): ?int {
    return isset($_GET['id']) ? (int)$_GET['id'] : null;
}

/** Retorna parâmetro GET sanitizado ou valor padrão */
function param(string $chave, string $padrao = ''): string {
    return trim($_GET[$chave] ?? $padrao);
}

/** Valida campos obrigatórios no corpo — encerra com erro 422 se ausente */
function validar(array $corpo, array $campos): void {
    foreach ($campos as $campo) {
        if (empty($corpo[$campo])) {
            erro("Campo obrigatório ausente: {$campo}", 422);
        }
    }
}

/** Gera iniciais a partir do nome completo (ex: "Ana Souza" → "AS") */
function iniciais(string $nome): string {
    $partes = array_values(array_filter(explode(' ', trim($nome))));
    return mb_strtoupper(
        mb_substr($partes[0] ?? '', 0, 1) .
        mb_substr($partes[1] ?? '', 0, 1)
    );
}

/** Calcula percentual seguro (evita divisão por zero) */
function percentual(int $parte, int $total): float {
    return $total > 0 ? round(($parte / $total) * 100, 1) : 0.0;
}

/** Encerra com erro 405 para método não suportado */
function metodoNaoSuportado(): void {
    erro('Método HTTP não suportado para este recurso.', 405);
}
