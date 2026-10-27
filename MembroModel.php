<?php
/**
 * MembroModel — Queries de membros
 * models/MembroModel.php
 */
class MembroModel {
    private PDO $db;
    public function __construct() { $this->db = conexao(); }

    public function listar(array $filtros = []): array {
        $sql = "SELECT mem.*, min.nome AS ministerio, min.cor_hex AS ministerio_cor,
                       min.icone AS ministerio_icone, sup.nome AS supervisor_nome
                FROM membros mem
                LEFT JOIN ministerios  min ON min.id = mem.ministerio_id
                LEFT JOIN supervisores sup ON sup.id = mem.supervisor_id
                WHERE 1=1";
        $params = [];
        if (!empty($filtros['busca']))      { $sql .= " AND mem.nome LIKE ?";     $params[] = "%{$filtros['busca']}%"; }
        if (!empty($filtros['ministerio'])) { $sql .= " AND mem.ministerio_id=?"; $params[] = $filtros['ministerio']; }
        if (!empty($filtros['status']))     { $sql .= " AND mem.status=?";        $params[] = $filtros['status']; }
        $sql .= " ORDER BY mem.nome";
        $st = $this->db->prepare($sql);
        $st->execute($params);
        $rows = $st->fetchAll();
        foreach ($rows as &$r) {
            $r['habilidades']     = $this->habilidades($r['id']);
            $r['disponibilidades']= $this->disponibilidades($r['id']);
        }
        return $rows;
    }

    public function buscarPorId(int $id): ?array {
        $st = $this->db->prepare("SELECT mem.*, min.nome AS ministerio, min.cor_hex AS ministerio_cor,
               min.icone AS ministerio_icone, sup.nome AS supervisor_nome, sup.email AS supervisor_email
               FROM membros mem
               LEFT JOIN ministerios  min ON min.id = mem.ministerio_id
               LEFT JOIN supervisores sup ON sup.id = mem.supervisor_id
               WHERE mem.id = ?");
        $st->execute([$id]);
        $row = $st->fetch();
        if (!$row) return null;
        $row['habilidades']      = $this->habilidades($id);
        $row['disponibilidades'] = $this->disponibilidades($id);
        $row['escalas']          = $this->escalas($id);
        $row['presenca_resumo']  = $this->resumoPresenca($id);
        return $row;
    }

    public function habilidades(int $membroId): array {
        $st = $this->db->prepare("SELECT h.id, h.nome FROM habilidades h
               JOIN membro_habilidades mh ON mh.habilidade_id = h.id WHERE mh.membro_id = ?");
        $st->execute([$membroId]);
        return $st->fetchAll();
    }

    public function disponibilidades(int $membroId): array {
        $st = $this->db->prepare("SELECT d.id, d.descricao FROM disponibilidades d
               JOIN membro_disponibilidades md ON md.disponibilidade_id = d.id WHERE md.membro_id = ?");
        $st->execute([$membroId]);
        return $st->fetchAll();
    }

    public function escalas(int $membroId, int $limite = 10): array {
        $st = $this->db->prepare("SELECT e.*, min.nome AS ministerio, min.cor_hex AS ministerio_cor
               FROM escalas e LEFT JOIN ministerios min ON min.id = e.ministerio_id
               WHERE e.membro_id = ? ORDER BY e.data_escala DESC LIMIT {$limite}");
        $st->execute([$membroId]);
        return $st->fetchAll();
    }

    public function resumoPresenca(int $membroId): array {
        $st = $this->db->prepare("SELECT COUNT(*) AS total,
               SUM(status='confirmado') AS confirmados,
               SUM(status='pendente')   AS pendentes,
               SUM(status='ausente')    AS ausentes
               FROM escalas WHERE membro_id = ?");
        $st->execute([$membroId]);
        return $st->fetch();
    }

    public function criar(array $d): int {
        $this->db->prepare("INSERT INTO membros
               (nome,email,telefone,ministerio_id,supervisor_id,funcao,status,avatar_iniciais,avatar_cor)
               VALUES (?,?,?,?,?,?,?,?,?)")
            ->execute([$d['nome'],$d['email']??null,$d['telefone']??null,
                       $d['ministerio_id']??null,$d['supervisor_id']??null,
                       $d['funcao']??null,$d['status']??'ativo',
                       mb_strtoupper(mb_substr($d['nome'],0,1)),$d['avatar_cor']??'#0D9488']);
        $id = (int)$this->db->lastInsertId();
        if (!empty($d['habilidades'])) $this->vincularHabilidades($id, $d['habilidades']);
        if (!empty($d['disponibilidades'])) $this->vincularDisponibilidades($id, $d['disponibilidades']);
        return $id;
    }

    public function atualizar(int $id, array $d): void {
        $this->db->prepare("UPDATE membros SET nome=?,email=?,telefone=?,ministerio_id=?,
               supervisor_id=?,funcao=?,status=?,avatar_iniciais=? WHERE id=?")
            ->execute([$d['nome'],$d['email']??null,$d['telefone']??null,
                       $d['ministerio_id']??null,$d['supervisor_id']??null,
                       $d['funcao']??null,$d['status']??'ativo',
                       mb_strtoupper(mb_substr($d['nome'],0,1)),$id]);
        if (isset($d['habilidades'])) {
            $this->db->prepare("DELETE FROM membro_habilidades WHERE membro_id=?")->execute([$id]);
            $this->vincularHabilidades($id, $d['habilidades']);
        }
    }

    public function remover(int $id): void {
        $this->db->prepare("DELETE FROM membros WHERE id=?")->execute([$id]);
    }

    private function vincularHabilidades(int $id, array $ids): void {
        $ins = $this->db->prepare("INSERT IGNORE INTO membro_habilidades VALUES (?,?)");
        foreach ($ids as $hId) $ins->execute([$id,(int)$hId]);
    }

    private function vincularDisponibilidades(int $id, array $ids): void {
        $ins = $this->db->prepare("INSERT IGNORE INTO membro_disponibilidades VALUES (?,?)");
        foreach ($ids as $dId) $ins->execute([$id,(int)$dId]);
    }
}
