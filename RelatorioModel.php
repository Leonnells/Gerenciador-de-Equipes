<?php
/**
 * RelatorioModel — Queries das views de relatório
 * models/RelatorioModel.php
 */
class RelatorioModel {
    private PDO $db;
    public function __construct() { $this->db = conexao(); }

    public function presencaGlobal(): array {
        return $this->db->query("
            SELECT COUNT(*) AS total_escalas,
                   SUM(status='confirmado') AS confirmados,
                   SUM(status='pendente')   AS pendentes,
                   SUM(status='ausente')    AS ausentes,
                   ROUND(100.0*SUM(status='confirmado')/NULLIF(COUNT(*),0),1) AS taxa_presenca_pct
            FROM escalas")->fetch();
    }

    public function presencaPorMinisterio(): array {
        return $this->db->query("
            SELECT min.id AS ministerio_id, min.nome AS ministerio, min.cor_hex, min.icone,
                   COUNT(e.id) AS total_escalas,
                   SUM(e.status='confirmado') AS confirmados,
                   SUM(e.status='pendente')   AS pendentes,
                   SUM(e.status='ausente')    AS ausentes,
                   ROUND(100.0*SUM(e.status='confirmado')/NULLIF(COUNT(e.id),0),1) AS taxa_presenca_pct
            FROM ministerios min LEFT JOIN escalas e ON e.ministerio_id=min.id
            GROUP BY min.id, min.nome, min.cor_hex, min.icone ORDER BY min.nome")->fetchAll();
    }

    public function resumoPorEquipe(): array {
        return $this->db->query("
            SELECT min.id, min.nome AS ministerio, min.cor_hex, min.icone,
                   COUNT(DISTINCT s.id)   AS total_supervisores,
                   COUNT(DISTINCT mem.id) AS total_membros,
                   COUNT(DISTINCT e.id)   AS total_escalas,
                   SUM(e.status='confirmado') AS confirmados,
                   ROUND(100.0*SUM(e.status='confirmado')/NULLIF(COUNT(e.id),0),1) AS taxa_presenca_pct
            FROM ministerios min
            LEFT JOIN supervisores s   ON s.ministerio_id   = min.id
            LEFT JOIN membros      mem ON mem.ministerio_id = min.id
            LEFT JOIN escalas      e   ON e.ministerio_id   = min.id
            GROUP BY min.id, min.nome, min.cor_hex, min.icone ORDER BY min.nome")->fetchAll();
    }

    public function presencaPorMembro(array $filtros = []): array {
        $sql = "
            SELECT mem.id AS membro_id, mem.nome AS membro, mem.funcao,
                   mem.avatar_iniciais, mem.avatar_cor,
                   min.nome AS ministerio, min.icone AS ministerio_icone,
                   COUNT(e.id) AS total_escalas,
                   SUM(e.status='confirmado') AS confirmados,
                   SUM(e.status='pendente')   AS pendentes,
                   SUM(e.status='ausente')    AS ausentes,
                   ROUND(100.0*SUM(e.status='confirmado')/NULLIF(COUNT(e.id),0),1) AS taxa_presenca_pct
            FROM membros mem
            LEFT JOIN ministerios min ON min.id = mem.ministerio_id
            LEFT JOIN escalas     e   ON e.membro_id = mem.id
            WHERE 1=1";
        $params = [];
        if (!empty($filtros['busca']))      { $sql .= " AND mem.nome LIKE ?";     $params[] = "%{$filtros['busca']}%"; }
        if (!empty($filtros['ministerio'])) { $sql .= " AND mem.ministerio_id=?"; $params[] = $filtros['ministerio']; }
        $sql .= " GROUP BY mem.id, mem.nome, mem.funcao, mem.avatar_iniciais, mem.avatar_cor, min.nome, min.icone ORDER BY mem.nome";
        $st = $this->db->prepare($sql);
        $st->execute($params);
        return $st->fetchAll();
    }

    public function frequenciaMensal(): array {
        return $this->db->query("
            SELECT MONTH(data_escala) AS mes, COUNT(*) AS total
            FROM escalas WHERE YEAR(data_escala)=YEAR(CURDATE())
            GROUP BY mes ORDER BY mes")->fetchAll();
    }

    public function desempenhoPorEquipe(): array {
        return $this->presencaPorMinisterio();
    }
}
