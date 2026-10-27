<?php
/**
 * GERENCIADOR DE EQUIPES — Roteador Central
 * router.php
 *
 * Único ponto de entrada da API.
 * Uso: router.php?recurso=ministerios[&id=N][&tipo=xxx]
 *
 * Fluxo:
 *   Browser → router.php → Controller → Model → MySQL
 */
require_once __DIR__ . '/config/conexao.php';
require_once __DIR__ . '/helpers/helpers.php';

cabecalhos();
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(204); exit; }

$mapa = [
    'dashboard'    => __DIR__ . '/controllers/DashboardController.php',
    'ministerios'  => __DIR__ . '/controllers/MinisterioController.php',
    'supervisores' => __DIR__ . '/controllers/SupervisorController.php',
    'membros'      => __DIR__ . '/controllers/MembroController.php',
    'escalas'      => __DIR__ . '/controllers/EscalaController.php',
    'eventos'      => __DIR__ . '/controllers/EventoController.php',
    'relatorios'   => __DIR__ . '/controllers/RelatorioController.php',
    'notificacoes' => __DIR__ . '/controllers/NotificacaoController.php',
];

$recurso = trim($_GET['recurso'] ?? '');

if (!$recurso) {
    erro('Parâmetro "recurso" obrigatório. Disponíveis: ' . implode(', ', array_keys($mapa)), 400);
}
if (!array_key_exists($recurso, $mapa)) {
    erro("Recurso \"{$recurso}\" não encontrado. Disponíveis: " . implode(', ', array_keys($mapa)), 404);
}

try {
    require $mapa[$recurso];
} catch (PDOException $e) {
    erro('Erro de banco de dados: ' . $e->getMessage(), 500);
} catch (Throwable $e) {
    erro('Erro interno: ' . $e->getMessage(), 500);
}
