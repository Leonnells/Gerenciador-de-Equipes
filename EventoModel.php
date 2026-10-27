<?php
/**
 * EventoModel — Queries de eventos e evento_ministerios
 * models/EventoModel.php
 */
class EventoModel {
    private PDO $db;
    public function __construct() { $this->db = conexao(); }

    public function listarPorMes(int $mes, int $ano): array {
        $st = $this->db->prepare("
            SELECT e.*, mp.nome AS ministerio_principal, mp.cor_hex AS cor_principal,
                   GROUP_CONCAT(DISTINCT mi.nome  ORDER BY mi.nome SEPARATOR ', ') AS ministerios_participantes,
                   GROUP_CONCAT(DISTINCT em.ministerio_id ORDER BY em.ministerio_id) AS ministerios_ids
            FROM eventos e
            LEFT JOIN ministerios mp ON mp.id = e.ministerio_id
            LEFT JOIN evento_ministerios em ON em.evento_id = e.id
            LEFT JOIN ministerios mi ON mi.id = em.ministerio_id
            WHERE MONTH(e.data_evento)=? AND YEAR(e.data_evento)=?
            GROUP BY e.id ORDER BY e.data_evento, e.horario");
        $st->execute([$mes,$ano]);
        return $st->fetchAll();
    }

    public function buscarPorId(int $id): ?array {
        $st = $this->db->prepare("SELECT e.*, mp.nome AS ministerio_principal, mp.cor_hex AS cor_principal,
               mp.icone AS icone_principal FROM eventos e
               LEFT JOIN ministerios mp ON mp.id = e.ministerio_id WHERE e.id=?");
        $st->execute([$id]);
        $row = $st->fetch();
        if (!$row) return null;
        $stM = $this->db->prepare("SELECT m.id, m.nome, m.cor_hex, m.icone FROM ministerios m
               JOIN evento_ministerios em ON em.ministerio_id = m.id WHERE em.evento_id=? ORDER BY m.nome");
        $stM->execute([$id]);
        $row['ministerios'] = $stM->fetchAll();
        return $row;
    }

    public function criar(array $d): int {
        $mins = $d['ministerios'] ?? [];
        $minPrincipal = !empty($mins) ? (int)$mins[0] : null;
        $this->db->beginTransaction();
        $this->db->prepare("INSERT INTO eventos (titulo,descricao,ministerio_id,data_evento,horario,tipo)
               VALUES (?,?,?,?,?,?)")
            ->execute([$d['titulo'],$d['descricao']??null,$minPrincipal,
                       $d['data_evento'],$d['horario'],$d['tipo']??'culto']);
        $id = (int)$this->db->lastInsertId();
        $this->vincularMinisterios($id,$mins);
        $this->db->commit();
        return $id;
    }

    public function atualizar(int $id, array $d): void {
        $mins = $d['ministerios'] ?? [];
        $minPrincipal = !empty($mins) ? (int)$mins[0] : null;
        $this->db->beginTransaction();
        $this->db->prepare("UPDATE eventos SET titulo=?,descricao=?,ministerio_id=?,data_evento=?,horario=?,tipo=? WHERE id=?")
            ->execute([$d['titulo'],$d['descricao']??null,$minPrincipal,
                       $d['data_evento'],$d['horario'],$d['tipo']??'culto',$id]);
        $this->db->prepare("DELETE FROM evento_ministerios WHERE evento_id=?")->execute([$id]);
        $this->vincularMinisterios($id,$mins);
        $this->db->commit();
    }

    public function remover(int $id): void {
        // evento_ministerios → CASCADE
        $this->db->prepare("DELETE FROM eventos WHERE id=?")->execute([$id]);
    }

    private function vincularMinisterios(int $eventoId, array $ids): void {
        if (empty($ids)) return;
        $ins = $this->db->prepare("INSERT IGNORE INTO evento_ministerios (evento_id,ministerio_id) VALUES (?,?)");
        foreach ($ids as $mId) $ins->execute([$eventoId,(int)$mId]);
    }
}
