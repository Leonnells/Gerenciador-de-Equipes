<?php
/**
 * NotificacaoModel — Queries de notificações
 * models/NotificacaoModel.php
 */
class NotificacaoModel {
    private PDO $db;
    public function __construct() { $this->db = conexao(); }

    public function listar(bool $somenteNaoLidas = false): array {
        $sql = "SELECT * FROM notificacoes" . ($somenteNaoLidas ? " WHERE lida=0" : "") . " ORDER BY criado_em DESC LIMIT 20";
        return $this->db->query($sql)->fetchAll();
    }

    public function totalNaoLidas(): int {
        return (int)$this->db->query("SELECT COUNT(*) FROM notificacoes WHERE lida=0")->fetchColumn();
    }

    public function marcarLida(int $id): void {
        $this->db->prepare("UPDATE notificacoes SET lida=1 WHERE id=?")->execute([$id]);
    }

    public function marcarTodasLidas(): void {
        $this->db->query("UPDATE notificacoes SET lida=1 WHERE lida=0");
    }

    public function remover(int $id): void {
        $this->db->prepare("DELETE FROM notificacoes WHERE id=?")->execute([$id]);
    }
}
