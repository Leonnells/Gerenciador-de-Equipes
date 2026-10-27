<?php
/**
 * GERENCIADOR DE EQUIPES — Model de Supervisores
 * Arquivo: models/SupervisorModel.php
 *
 * Queries SQL relacionadas à tabela: supervisores
 */

require_once __DIR__ . '/../config/conexao.php';

class SupervisorModel
{
    private PDO $pdo;

    public function __construct() {
        $this->pdo = conexao();
    }

    /**
     * Lista supervisores com filtros opcionais.
     * Query: SELECT s.*, m.nome AS ministerio ... WHERE [filtros]
     * Tabelas: supervisores, ministerios
     */
    public function listar(array $filtros = []): array {
        $sql    = "
            SELECT s.*,
                   m.nome    AS ministerio,
                   m.cor_hex AS ministerio_cor,
                   m.icone   AS ministerio_icone
            FROM supervisores s
            LEFT JOIN ministerios m ON m.id = s.ministerio_id
            WHERE 1 = 1
        ";
        $params = [];

        if (!empty($filtros['ministerio'])) {
            $sql .= " AND s.ministerio_id = ?";
            $params[] = $filtros['ministerio'];
        }
        if (!empty($filtros['status'])) {
            $sql .= " AND s.status = ?";
            $params[] = $filtros['status'];
        }
        if (!empty($filtros['busca'])) {
            $sql .= " AND (s.nome LIKE ? OR s.email LIKE ?)";
            $params[] = "%{$filtros['busca']}%";
            $params[] = "%{$filtros['busca']}%";
        }

        $sql .= " ORDER BY s.nome";
        $st   = $this->pdo->prepare($sql);
        $st->execute($params);
        return $st->fetchAll();
    }

    /**
     * Busca supervisor por ID com dados do ministério.
     * Query: SELECT s.*, m.nome ... FROM supervisores s LEFT JOIN ministerios m ... WHERE s.id = ?
     * Tabelas: supervisores, ministerios
     */
    public function buscarPorId(int $id): array|false {
        $st = $this->pdo->prepare("
            SELECT s.*,
                   m.nome    AS ministerio,
                   m.cor_hex AS ministerio_cor,
                   m.icone   AS ministerio_icone
            FROM supervisores s
            LEFT JOIN ministerios m ON m.id = s.ministerio_id
            WHERE s.id = ?
        ");
        $st->execute([$id]);
        return $st->fetch();
    }

    /**
     * Busca membros da equipe vinculada ao ministério do supervisor.
     * Query: SELECT ... FROM membros WHERE ministerio_id = ?
     * Tabela: membros
     */
    public function buscarEquipe(int $ministerioId): array {
        $st = $this->pdo->prepare("
            SELECT id, nome, funcao, status, avatar_iniciais, avatar_cor
            FROM membros
            WHERE ministerio_id = ?
            ORDER BY nome
        ");
        $st->execute([$ministerioId]);
        return $st->fetchAll();
    }

    /**
     * Busca resumo de escalas geradas pelo supervisor.
     * Query: SELECT COUNT, SUM ... FROM escalas WHERE supervisor_id = ?
     * Tabela: escalas
     */
    public function buscarResumoEscalas(int $supervisorId): array {
        $st = $this->pdo->prepare("
            SELECT
                COUNT(*)                   AS total,
                SUM(status='confirmado')   AS confirmados,
                SUM(status='pendente')     AS pendentes,
                SUM(status='ausente')      AS ausentes
            FROM escalas
            WHERE supervisor_id = ?
        ");
        $st->execute([$supervisorId]);
        return $st->fetch() ?: [];
    }

    /**
     * Insere novo supervisor.
     * Query: INSERT INTO supervisores (...) VALUES (?,?,?,?,?,?,?)
     * Tabela: supervisores
     */
    public function criar(array $d): int {
        $this->pdo->prepare("
            INSERT INTO supervisores
                (nome, email, telefone, ministerio_id, status, avatar_iniciais, avatar_cor)
            VALUES (?, ?, ?, ?, ?, ?, ?)
        ")->execute([
            $d['nome'],
            $d['email'],
            $d['telefone']      ?? null,
            $d['ministerio_id'] ? (int)$d['ministerio_id'] : null,
            $d['status']        ?? 'pendente',
            iniciais($d['nome']),
            $d['avatar_cor']    ?? '#0D9488',
        ]);
        return (int) $this->pdo->lastInsertId();
    }

    /**
     * Atualiza supervisor existente.
     * Query: UPDATE supervisores SET ... WHERE id = ?
     * Tabela: supervisores
     */
    public function atualizar(int $id, array $d): void {
        $this->pdo->prepare("
            UPDATE supervisores
            SET nome = ?, email = ?, telefone = ?,
                ministerio_id = ?, status = ?, avatar_iniciais = ?
            WHERE id = ?
        ")->execute([
            $d['nome'],
            $d['email'],
            $d['telefone']      ?? null,
            $d['ministerio_id'] ? (int)$d['ministerio_id'] : null,
            $d['status']        ?? 'pendente',
            iniciais($d['nome']),
            $id,
        ]);
    }

    /**
     * Remove supervisor. FK SET NULL cuida de membros.supervisor_id.
     * Query: DELETE FROM supervisores WHERE id = ?
     * Tabela: supervisores
     */
    public function remover(int $id): void {
        $this->pdo->prepare("DELETE FROM supervisores WHERE id = ?")->execute([$id]);
    }
}
