<?php
/**
 * GERENCIADOR DE EQUIPES — Conexão com o Banco de Dados
 * Arquivo: config/conexao.php
 *
 * Único ponto de acesso ao banco em todo o sistema.
 * Todos os Models fazem require_once deste arquivo.
 *
 * Para WordPress: substitua as constantes pelos valores
 * de DB_HOST, DB_NAME, DB_USER, DB_PASSWORD do wp-config.php.
 */

define('DB_HOST',    'localhost');
define('DB_NAME',    'gerenciador_equipes');
define('DB_USER',    'root');
define('DB_PASS',    '');
define('DB_CHARSET', 'utf8mb4');

/**
 * Retorna instância PDO (singleton).
 * Lança PDOException automaticamente em caso de falha.
 */
function conexao(): PDO {
    static $pdo = null;
    if ($pdo === null) {
        $pdo = new PDO(
            sprintf('mysql:host=%s;dbname=%s;charset=%s', DB_HOST, DB_NAME, DB_CHARSET),
            DB_USER,
            DB_PASS,
            [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
            ]
        );
    }
    return $pdo;
}
