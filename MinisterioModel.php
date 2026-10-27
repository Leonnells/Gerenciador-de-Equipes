<?php
/**
 * GERENCIADOR DE EQUIPES — Model de Ministérios
 * Arquivo: models/MinisterioModel.php
 *
 * Contém todas as queries SQL relacionadas às tabelas:
 *   - ministerios
 *   (supervisores e membros lidos por JOIN para detalhe)
 *
 * Nenhuma lógica HTTP aqui — apenas acesso a dados.
 */

require_once __DIR__ . '/../config/conexao.php';

class MinisterioModel
{
    private PDO $pdo;

    public function __construct() {
        $this->pdo = conexao();
    }

    /**
     * Lista todos os ministérios ativos com contagem de supervisores e membros.
     * Query: SELECT min.*, COUNT(DISTINCT s.id), COUNT(DISTINCT mem.id) FROM ministerios ...
     * Tabelas: ministerios, supervisores, membros
     */
    public function listar(): array {
        return $this->pdo->query("
            SELECT
                min.*,
                COUNT(DISTINCT s.id)   AS total_supervisores,
                COUNT(DISTINCT mem.id) AS total_membros
            FROM ministerios min
            LEFT JOIN supervisores s   ON s.ministerio_id   = min.id
            LEFT JOIN membros      mem ON mem.ministerio_id = min.id
            WHERE min.ativo = 1
            GROUP BY min.id
            ORDER BY min.nome
        ")->fetchAll();
    }

    /**
     * Busca um ministério pelo ID.
     * Query: SELECT * FROM ministerios WHERE id = ?
     * Tabela: ministerios
     */
    public function buscarPorId(int $id): array|false {
        $st = $this->pdo->prepare("SELECT * FROM ministerios WHERE id = ?");
        $st->execute([$id]);
        return $st->fetch();
    }

    /**
     * Busca supervisores vinculados ao ministério.
     * Query: SELECT ... FROM supervisores WHERE ministerio_id = ?
     * Tabela: supervisores
     */
    public function buscarSupervisores(int $ministerioId): array {
        $st = $this->pdo->prepare("
            SELECT id, nome, email, telefone, status, avatar_iniciais, avatar_cor
            FROM supervisores
            WHERE ministerio_id = ?
            ORDER BY nome
        ");
        $st->execute([$ministerioId]);
        return $st->fetchAll();
    }

    /**
     * Busca membros vinculados ao ministério.
     * Query: SELECT ... FROM membros WHERE ministerio_id = ?
     * Tabela: membros
     */
    public function buscarMembros(int $ministerioId): array {
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
     * Busca estatísticas de presença do ministério.
     * Query: SELECT COUNT, SUM ... FROM escalas WHERE ministerio_id = ?
     * Tabela: escalas
     */
    public function buscarPresenca(int $ministerioId): array {
        $st = $this->pdo->prepare("
            SELECT
                COUNT(*)                          AS total_escalas,
                SUM(status = 'confirmado')        AS confirmados,
                SUM(status = 'pendente')          AS pendentes,
                SUM(status = 'ausente')           AS ausentes
            FROM escalas
            WHERE ministerio_id = ?
        ");
        $st->execute([$ministerioId]);
        return $st->fetch() ?: [];
    }

    /**
     * Insere novo ministério.
     * Query: INSERT INTO ministerios (nome, descricao, cor_hex, icone) VALUES (?,?,?,?)
     * Tabela: ministerios
     */
    public function criar(string $nome, ?string $descricao, string $corHex, string $icone): int {
        $this->pdo->prepare("
            INSERT INTO ministerios (nome, descricao, cor_hex, icone)
            VALUES (?, ?, ?, ?)
        ")->execute([$nome, $descricao, $corHex, $icone]);
        return (int) $this->pdo->lastInsertId();
    }

    /**
     * Atualiza ministério existente.
     * Query: UPDATE ministerios SET nome=?, descricao=?, cor_hex=?, icone=? WHERE id=?
     * Tabela: ministerios
     */
    public function atualizar(int $id, string $nome, ?string $descricao, string $corHex, string $icone): void {
        $this->pdo->prepare("
            UPDATE ministerios
            SET nome = ?, descricao = ?, cor_hex = ?, icone = ?
            WHERE id = ?
        ")->execute([$nome, $descricao, $corHex, $icone, $id]);
    }

    /**
     * Remove ministério. FKs cuidam de SET NULL em supervisores/membros
     * e CASCADE em evento_ministerios.
     * Query: DELETE FROM ministerios WHERE id = ?
     * Tabela: ministerios
     */
    public function remover(int $id): void {
        $this->pdo->prepare("DELETE FROM ministerios WHERE id = ?")->execute([$id]);
    }
}
