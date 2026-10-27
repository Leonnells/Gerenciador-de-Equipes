<?php
/**
 * EscalaModel — Queries de escalas
 * models/EscalaModel.php
 */
class EscalaModel {
    private PDO $db;
    public function __construct() { $this->db = conexao(); }

    public function listar(array $filtros = []): array {
        $pagina  = max(1,(int)($filtros['pagina']??1));
        $porPag  = 10;
        $offset  = ($pagina-1)*$porPag;
        $where   = "WHERE 1=1";
        $params  = [];
        if (!empty($filtros['ministerio'])) { $where .= " AND e.ministerio_id=?"; $params[] = $filtros['ministerio']; }
        if (!empty($filtros['status']))     { $where .= " AND e.status=?";        $params[] = $filtros['status']; }
        if (!empty($filtros['membro']))     { $where .= " AND e.membro_id=?";     $params[] = $filtros['membro']; }
        if (!empty($filtros['data_ini']))   { $where .= " AND e.data_escala>=?";  $params[] = $filtros['data_ini']; }
        if (!empty($filtros['data_fim']))   { $where .= " AND e.data_escala<=?";  $params[] = $filtros['data_fim']; }
        $total = (int)$this->db->prepare("SELECT COUNT(*) FROM escalas e {$where}")
                               ->execute($params) ? $this->db->query("SELECT FOUND_ROWS()")->fetchColumn() : 0;
        // Re-executa para o total correto
        $stTotal = $this->db->prepare("SELECT COUNT(*) FROM escalas e {$where}");
        $stTotal->execute($params);
        $total = (int)$stTotal->fetchColumn();

        $sql = "SELECT e.*, min.nome AS ministerio, min.cor_hex AS ministerio_cor,
                       min.icone AS ministerio_icone, sup.nome AS supervisor_nome, mem.nome AS membro_nome
                FROM escalas e
                LEFT JOIN ministerios  min ON min.id = e.ministerio_id
                LEFT JOIN supervisores sup ON sup.id = e.supervisor_id
                LEFT JOIN membros      mem ON mem.id = e.membro_id
                {$where} ORDER BY e.data_escala DESC, e.horario LIMIT ? OFFSET ?";
        $st = $this->db->prepare($sql);
        $st->execute(array_merge($params,[$porPag,$offset]));
        return [
            'total'         => $total,
            'pagina'        => $pagina,
            'por_pagina'    => $porPag,
            'total_paginas' => (int)ceil($total/$porPag),
            'escalas'       => $st->fetchAll(),
        ];
    }

    public function buscarPorId(int $id): ?array {
        $st = $this->db->prepare("SELECT e.*,
               min.nome AS ministerio, min.cor_hex AS ministerio_cor, min.icone AS ministerio_icone,
               sup.nome AS supervisor_nome,
               mem.nome AS membro_nome, mem.email AS membro_email,
               mem.telefone AS membro_telefone, mem.avatar_iniciais AS membro_iniciais, mem.avatar_cor AS membro_cor
               FROM escalas e
               LEFT JOIN ministerios  min ON min.id = e.ministerio_id
               LEFT JOIN supervisores sup ON sup.id = e.supervisor_id
               LEFT JOIN membros      mem ON mem.id = e.membro_id
               WHERE e.id = ?");
        $st->execute([$id]);
        return $st->fetch() ?: null;
    }

    public function criar(array $d): int {
        $this->db->prepare("INSERT INTO escalas
               (evento_id,ministerio_id,supervisor_id,membro_id,data_escala,horario,funcao,status,observacoes)
               VALUES (?,?,?,?,?,?,?,?,?)")
            ->execute([$d['evento_id']??null,$d['ministerio_id']??null,$d['supervisor_id']??null,
                       $d['membro_id']??null,$d['data_escala'],$d['horario'],
                       $d['funcao']??null,$d['status']??'pendente',$d['observacoes']??null]);
        return (int)$this->db->lastInsertId();
    }

    public function atualizar(int $id, array $d): void {
        $this->db->prepare("UPDATE escalas SET ministerio_id=?,supervisor_id=?,membro_id=?,
               data_escala=?,horario=?,funcao=?,status=?,observacoes=? WHERE id=?")
            ->execute([$d['ministerio_id']??null,$d['supervisor_id']??null,$d['membro_id']??null,
                       $d['data_escala'],$d['horario'],$d['funcao']??null,
                       $d['status']??'pendente',$d['observacoes']??null,$id]);
    }

    public function remover(int $id): void {
        $this->db->prepare("DELETE FROM escalas WHERE id=?")->execute([$id]);
    }
}
