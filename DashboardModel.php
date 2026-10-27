<?php
/**
 * DashboardModel — Queries de KPIs e resumos
 * models/DashboardModel.php
 */
class DashboardModel {
    private PDO $db;
    public function __construct() { $this->db = conexao(); }

    public function kpis(): array {
        return [
            'ministerios_ativos'     => (int)$this->db->query("SELECT COUNT(*) FROM ministerios WHERE ativo=1")->fetchColumn(),
            'supervisores_ativos'    => (int)$this->db->query("SELECT COUNT(*) FROM supervisores WHERE status='ativo'")->fetchColumn(),
            'membros_cadastrados'    => (int)$this->db->query("SELECT COUNT(*) FROM membros WHERE status='ativo'")->fetchColumn(),
            'escalas_na_semana'      => (int)$this->db->query("SELECT COUNT(*) FROM escalas WHERE WEEK(data_escala)=WEEK(CURDATE()) AND YEAR(data_escala)=YEAR(CURDATE())")->fetchColumn(),
            'escalas_pendentes'      => (int)$this->db->query("SELECT COUNT(*) FROM escalas WHERE status='pendente'")->fetchColumn(),
            'notificacoes_nao_lidas' => (int)$this->db->query("SELECT COUNT(*) FROM notificacoes WHERE lida=0")->fetchColumn(),
        ];
    }

    public function proximasEscalas(int $limite = 5): array {
        $st = $this->db->prepare("
            SELECT e.*, min.nome AS ministerio, min.cor_hex AS ministerio_cor, min.icone AS ministerio_icone,
                   mem.nome AS membro_nome
            FROM escalas e
            LEFT JOIN ministerios min ON min.id = e.ministerio_id
            LEFT JOIN membros     mem ON mem.id = e.membro_id
            WHERE e.data_escala >= CURDATE()
            ORDER BY e.data_escala, e.horario LIMIT ?");
        $st->execute([$limite]);
        return $st->fetchAll();
    }

    public function proximosEventos(int $limite = 5): array {
        $st = $this->db->prepare("
            SELECT e.id, e.titulo, e.data_evento, e.horario, e.tipo,
                   mp.nome AS ministerio_principal, mp.cor_hex AS cor_principal,
                   GROUP_CONCAT(DISTINCT mi.nome ORDER BY mi.nome SEPARATOR ', ') AS ministerios_participantes
            FROM eventos e
            LEFT JOIN ministerios mp ON mp.id = e.ministerio_id
            LEFT JOIN evento_ministerios em ON em.evento_id = e.id
            LEFT JOIN ministerios mi ON mi.id = em.ministerio_id
            WHERE e.data_evento >= CURDATE()
            GROUP BY e.id ORDER BY e.data_evento, e.horario LIMIT ?");
        $st->execute([$limite]);
        return $st->fetchAll();
    }

    public function resumoMinisterios(): array {
        return $this->db->query("
            SELECT min.id, min.nome, min.cor_hex, min.icone,
                   COUNT(DISTINCT s.id)   AS total_supervisores,
                   COUNT(DISTINCT mem.id) AS total_membros,
                   COUNT(DISTINCT e.id)   AS total_escalas
            FROM ministerios min
            LEFT JOIN supervisores s   ON s.ministerio_id   = min.id
            LEFT JOIN membros      mem ON mem.ministerio_id = min.id
            LEFT JOIN escalas      e   ON e.ministerio_id   = min.id
            WHERE min.ativo=1 GROUP BY min.id ORDER BY min.nome
        ")->fetchAll();
    }
}
