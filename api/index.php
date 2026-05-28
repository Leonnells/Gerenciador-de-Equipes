<?php
/**
 * GERENCIADOR DE EQUIPES — API REST v3 final
 * Igreja Verbo da Vida, Bauru · LBD 3 · FATEC Bauru
 *
 * Rota: ?recurso=xxx  &id=N  &tipo=xxx
 *
 * Recursos disponíveis:
 *   dashboard, ministerios, supervisores, membros,
 *   escalas, eventos, hierarquia, notificacoes, relatorios
 */

// ── Configuração ──────────────────────────────────────────────
define('DB_HOST',    'localhost');
define('DB_NAME',    'gerenciador_equipes');
define('DB_USER',    'root');
define('DB_PASS',    '');
define('DB_CHARSET', 'utf8mb4');

// ── Headers ───────────────────────────────────────────────────
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(204); exit; }

// ── PDO singleton ─────────────────────────────────────────────
function db(): PDO {
    static $pdo = null;
    if ($pdo === null) {
        $pdo = new PDO(
            'mysql:host='.DB_HOST.';dbname='.DB_NAME.';charset='.DB_CHARSET,
            DB_USER, DB_PASS,
            [PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
             PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]
        );
    }
    return $pdo;
}

// ── Helpers ───────────────────────────────────────────────────
function ok(array $data, int $code = 200): void {
    http_response_code($code);
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    exit;
}
function err(string $msg, int $code = 400): void { ok(['erro' => $msg], $code); }
function body(): array { return json_decode(file_get_contents('php://input'), true) ?? []; }
function iniciais(string $nome): string {
    $p = array_values(array_filter(explode(' ', trim($nome))));
    return mb_strtoupper(mb_substr($p[0] ?? '', 0, 1) . mb_substr($p[1] ?? '', 0, 1));
}

// ── Roteamento ────────────────────────────────────────────────
$recurso = $_GET['recurso'] ?? '';
$id      = isset($_GET['id']) ? (int)$_GET['id'] : null;
$method  = $_SERVER['REQUEST_METHOD'];

try { switch ($recurso) {

// ════════════════════════════════════════════════════════════════
// DASHBOARD
// ════════════════════════════════════════════════════════════════
case 'dashboard':
    $pdo = db();
    ok([
        'supervisores_ativos'    => (int)$pdo->query("SELECT COUNT(*) FROM supervisores WHERE status='ativo'")->fetchColumn(),
        'membros_cadastrados'    => (int)$pdo->query("SELECT COUNT(*) FROM membros WHERE status='ativo'")->fetchColumn(),
        'escalas_semana'         => (int)$pdo->query("SELECT COUNT(*) FROM escalas WHERE WEEK(data_escala)=WEEK(CURDATE()) AND YEAR(data_escala)=YEAR(CURDATE())")->fetchColumn(),
        'notificacoes_nao_lidas' => (int)$pdo->query("SELECT COUNT(*) FROM notificacoes WHERE lida=0")->fetchColumn(),
        'proximos_eventos'       => $pdo->query("
            SELECT id, titulo, data_evento, horario, tipo,
                   ministerio_principal AS ministerio,
                   cor_principal AS cor_hex,
                   ministerios_participantes,
                   ministerios_ids
            FROM vw_eventos_ministerios
            WHERE data_evento >= CURDATE()
            ORDER BY data_evento, horario
            LIMIT 5
        ")->fetchAll(),
        // Próximas escalas com membro_nome (JOIN correto)
        'proximas_escalas'       => $pdo->query("
            SELECT e.id, e.data_escala, e.horario, e.funcao, e.status,
                   min.nome AS ministerio, min.cor_hex,
                   mem.nome AS membro_nome
            FROM escalas e
            LEFT JOIN ministerios min ON min.id = e.ministerio_id
            LEFT JOIN membros     mem ON mem.id = e.membro_id
            WHERE e.data_escala >= CURDATE()
            ORDER BY e.data_escala, e.horario
            LIMIT 5
        ")->fetchAll(),
    ]);

// ════════════════════════════════════════════════════════════════
// MINISTÉRIOS — CRUD completo
// ════════════════════════════════════════════════════════════════
case 'ministerios':
    $pdo = db();
    if ($method === 'GET') {
        if ($id) {
            $st = $pdo->prepare("SELECT * FROM ministerios WHERE id=?");
            $st->execute([$id]); $row = $st->fetch();
            if (!$row) err('Ministério não encontrado', 404);
            // Supervisores deste ministério
            $s = $pdo->prepare("SELECT id,nome,email,telefone,status,avatar_iniciais,avatar_cor FROM supervisores WHERE ministerio_id=? ORDER BY nome");
            $s->execute([$id]); $row['supervisores'] = $s->fetchAll();
            // Membros deste ministério
            $m = $pdo->prepare("SELECT id,nome,funcao,status,avatar_iniciais,avatar_cor FROM membros WHERE ministerio_id=? ORDER BY nome");
            $m->execute([$id]); $row['membros'] = $m->fetchAll();
            ok($row);
        }
        // Lista com contagens
        ok($pdo->query("
            SELECT min.*,
                   COUNT(DISTINCT s.id)   AS total_supervisores,
                   COUNT(DISTINCT mem.id) AS total_membros
            FROM ministerios min
            LEFT JOIN supervisores s   ON s.ministerio_id  = min.id
            LEFT JOIN membros      mem ON mem.ministerio_id = min.id
            WHERE min.ativo = 1
            GROUP BY min.id ORDER BY min.nome
        ")->fetchAll());
    }
    if ($method === 'POST') {
        $d = body(); if (empty($d['nome'])) err('Nome obrigatório');
        $pdo->prepare("INSERT INTO ministerios (nome,descricao,cor_hex,icone) VALUES (?,?,?,?)")
            ->execute([$d['nome'], $d['descricao']??null, $d['cor_hex']??'#0D9488', $d['icone']??'⛪']);
        ok(['id' => (int)$pdo->lastInsertId(), 'mensagem' => 'Ministério criado'], 201);
    }
    if ($method === 'PUT' && $id) {
        $d = body(); if (empty($d['nome'])) err('Nome obrigatório');
        $pdo->prepare("UPDATE ministerios SET nome=?,descricao=?,cor_hex=?,icone=? WHERE id=?")
            ->execute([$d['nome'], $d['descricao']??null, $d['cor_hex']??'#0D9488', $d['icone']??'⛪', $id]);
        ok(['mensagem' => 'Ministério atualizado']);
    }
    if ($method === 'DELETE' && $id) {
        // ON DELETE SET NULL cuida de supervisores, membros, escalas
        // ON DELETE CASCADE cuida de evento_ministerios
        $pdo->prepare("DELETE FROM ministerios WHERE id=?")->execute([$id]);
        ok(['mensagem' => 'Ministério removido. Vínculos desfeitos automaticamente.']);
    }
    err('Método não suportado', 405);

// ════════════════════════════════════════════════════════════════
// SUPERVISORES — CRUD completo
// ════════════════════════════════════════════════════════════════
case 'supervisores':
    $pdo = db();
    if ($method === 'GET') {
        if ($id) {
            $st = $pdo->prepare("SELECT s.*, m.nome AS ministerio, m.cor_hex FROM supervisores s LEFT JOIN ministerios m ON m.id=s.ministerio_id WHERE s.id=?");
            $st->execute([$id]); $row=$st->fetch();
            $row ? ok($row) : err('Supervisor não encontrado', 404);
        }
        $sql = "SELECT s.*, m.nome AS ministerio, m.cor_hex, m.icone AS ministerio_icone
                FROM supervisores s LEFT JOIN ministerios m ON m.id=s.ministerio_id WHERE 1=1";
        $params = [];
        if (!empty($_GET['ministerio'])) { $sql.=" AND s.ministerio_id=?"; $params[]=$_GET['ministerio']; }
        if (!empty($_GET['status']))     { $sql.=" AND s.status=?";        $params[]=$_GET['status']; }
        if (!empty($_GET['busca']))      { $sql.=" AND s.nome LIKE ?";     $params[]="%{$_GET['busca']}%"; }
        $sql .= " ORDER BY s.nome";
        $st=$pdo->prepare($sql); $st->execute($params);
        ok($st->fetchAll());
    }
    if ($method === 'POST') {
        $d = body();
        if (empty($d['nome'])||empty($d['email'])) err('Nome e e-mail obrigatórios');
        $pdo->prepare("INSERT INTO supervisores (nome,email,telefone,ministerio_id,status,avatar_iniciais,avatar_cor) VALUES (?,?,?,?,?,?,?)")
            ->execute([$d['nome'],$d['email'],$d['telefone']??null,$d['ministerio_id']??null,$d['status']??'pendente',iniciais($d['nome']),$d['avatar_cor']??'#0D9488']);
        ok(['id'=>(int)$pdo->lastInsertId(),'mensagem'=>'Supervisor criado'], 201);
    }
    if ($method === 'PUT' && $id) {
        $d = body();
        $pdo->prepare("UPDATE supervisores SET nome=?,email=?,telefone=?,ministerio_id=?,status=?,avatar_iniciais=? WHERE id=?")
            ->execute([$d['nome'],$d['email'],$d['telefone']??null,$d['ministerio_id']??null,$d['status']??'pendente',iniciais($d['nome']),$id]);
        ok(['mensagem'=>'Supervisor atualizado']);
    }
    if ($method === 'DELETE' && $id) {
        // membros.supervisor_id → SET NULL automaticamente (FK)
        $pdo->prepare("DELETE FROM supervisores WHERE id=?")->execute([$id]);
        ok(['mensagem'=>'Supervisor removido']);
    }
    err('Método não suportado', 405);

// ════════════════════════════════════════════════════════════════
// MEMBROS — CRUD completo
// ════════════════════════════════════════════════════════════════
case 'membros':
    $pdo = db();
    if ($method === 'GET') {
        if ($id) {
            $st = $pdo->prepare("
                SELECT mem.*, min.nome AS ministerio, min.cor_hex, min.icone AS ministerio_icone,
                       sup.nome AS supervisor_nome
                FROM membros mem
                LEFT JOIN ministerios min ON min.id = mem.ministerio_id
                LEFT JOIN supervisores sup ON sup.id = mem.supervisor_id
                WHERE mem.id=?");
            $st->execute([$id]); $row=$st->fetch();
            if (!$row) err('Membro não encontrado', 404);
            $h=$pdo->prepare("SELECT h.nome FROM habilidades h JOIN membro_habilidades mh ON mh.habilidade_id=h.id WHERE mh.membro_id=?");
            $h->execute([$id]); $row['habilidades']=array_column($h->fetchAll(),'nome');
            $dsp=$pdo->prepare("SELECT d.descricao FROM disponibilidades d JOIN membro_disponibilidades md ON md.disponibilidade_id=d.id WHERE md.membro_id=?");
            $dsp->execute([$id]); $row['disponibilidades']=array_column($dsp->fetchAll(),'descricao');
            // Escalas do membro (para relatório individual)
            $esc=$pdo->prepare("SELECT e.*, min.nome AS ministerio FROM escalas e LEFT JOIN ministerios min ON min.id=e.ministerio_id WHERE e.membro_id=? ORDER BY e.data_escala DESC LIMIT 20");
            $esc->execute([$id]); $row['escalas']=$esc->fetchAll();
            ok($row);
        }
        $sql = "SELECT mem.*, min.nome AS ministerio, min.cor_hex, min.icone AS ministerio_icone,
                       sup.nome AS supervisor_nome
                FROM membros mem
                LEFT JOIN ministerios min ON min.id = mem.ministerio_id
                LEFT JOIN supervisores sup ON sup.id = mem.supervisor_id
                WHERE 1=1";
        $params=[];
        if (!empty($_GET['busca']))      { $sql.=" AND mem.nome LIKE ?";     $params[]="%{$_GET['busca']}%"; }
        if (!empty($_GET['ministerio'])) { $sql.=" AND mem.ministerio_id=?"; $params[]=$_GET['ministerio']; }
        $sql .= " ORDER BY mem.nome";
        $st=$pdo->prepare($sql); $st->execute($params);
        $rows=$st->fetchAll();
        foreach ($rows as &$r) {
            $h=$pdo->prepare("SELECT h.nome FROM habilidades h JOIN membro_habilidades mh ON mh.habilidade_id=h.id WHERE mh.membro_id=?");
            $h->execute([$r['id']]); $r['habilidades']=array_column($h->fetchAll(),'nome');
            $dsp=$pdo->prepare("SELECT d.descricao FROM disponibilidades d JOIN membro_disponibilidades md ON md.disponibilidade_id=d.id WHERE md.membro_id=?");
            $dsp->execute([$r['id']]); $r['disponibilidades']=array_column($dsp->fetchAll(),'descricao');
        }
        ok($rows);
    }
    if ($method === 'POST') {
        $d=body(); if (empty($d['nome'])) err('Nome obrigatório');
        $pdo->prepare("INSERT INTO membros (nome,email,telefone,ministerio_id,supervisor_id,funcao,status,avatar_iniciais,avatar_cor) VALUES (?,?,?,?,?,?,?,?,?)")
            ->execute([$d['nome'],$d['email']??null,$d['telefone']??null,$d['ministerio_id']??null,$d['supervisor_id']??null,$d['funcao']??null,$d['status']??'ativo',mb_strtoupper(mb_substr($d['nome'],0,1)),$d['avatar_cor']??'#0D9488']);
        $newId=(int)$pdo->lastInsertId();
        if (!empty($d['habilidades'])) {
            $ins=$pdo->prepare("INSERT IGNORE INTO membro_habilidades VALUES (?,?)");
            foreach ($d['habilidades'] as $hId) $ins->execute([$newId,(int)$hId]);
        }
        ok(['id'=>$newId,'mensagem'=>'Membro criado'], 201);
    }
    if ($method === 'PUT' && $id) {
        $d=body();
        $pdo->prepare("UPDATE membros SET nome=?,email=?,telefone=?,ministerio_id=?,supervisor_id=?,funcao=?,status=?,avatar_iniciais=? WHERE id=?")
            ->execute([$d['nome'],$d['email']??null,$d['telefone']??null,$d['ministerio_id']??null,$d['supervisor_id']??null,$d['funcao']??null,$d['status']??'ativo',mb_strtoupper(mb_substr($d['nome'],0,1)),$id]);
        ok(['mensagem'=>'Membro atualizado']);
    }
    if ($method === 'DELETE' && $id) {
        // escalas.membro_id → SET NULL automaticamente (FK)
        $pdo->prepare("DELETE FROM membros WHERE id=?")->execute([$id]);
        ok(['mensagem'=>'Membro removido']);
    }
    err('Método não suportado', 405);

// ════════════════════════════════════════════════════════════════
// ESCALAS — CRUD + paginação
// ════════════════════════════════════════════════════════════════
case 'escalas':
    $pdo=db();
    if ($method === 'GET') {
        $pag=(int)max(1,$_GET['pagina']??1); $limit=10; $offset=($pag-1)*$limit;
        $total=(int)$pdo->query("SELECT COUNT(*) FROM escalas")->fetchColumn();
        $st=$pdo->prepare("
            SELECT e.*, min.nome AS ministerio, min.cor_hex, min.icone AS ministerio_icone,
                   sup.nome AS supervisor_nome,
                   mem.nome AS membro_nome
            FROM escalas e
            LEFT JOIN ministerios min ON min.id=e.ministerio_id
            LEFT JOIN supervisores sup ON sup.id=e.supervisor_id
            LEFT JOIN membros     mem ON mem.id=e.membro_id
            ORDER BY e.data_escala, e.horario
            LIMIT ? OFFSET ?
        ");
        $st->execute([$limit,$offset]);
        ok(['total'=>$total,'pagina'=>$pag,'por_pag'=>$limit,'escalas'=>$st->fetchAll()]);
    }
    if ($method === 'POST') {
        $d=body(); if (empty($d['data_escala'])||empty($d['horario'])) err('Data e horário obrigatórios');
        $pdo->prepare("INSERT INTO escalas (evento_id,ministerio_id,supervisor_id,membro_id,data_escala,horario,funcao,status,observacoes) VALUES (?,?,?,?,?,?,?,?,?)")
            ->execute([$d['evento_id']??null,$d['ministerio_id']??null,$d['supervisor_id']??null,$d['membro_id']??null,$d['data_escala'],$d['horario'],$d['funcao']??null,$d['status']??'pendente',$d['observacoes']??null]);
        ok(['id'=>(int)$pdo->lastInsertId(),'mensagem'=>'Escala criada'], 201);
    }
    if ($method === 'PUT' && $id) {
        $d=body();
        $pdo->prepare("UPDATE escalas SET ministerio_id=?,supervisor_id=?,membro_id=?,data_escala=?,horario=?,funcao=?,status=?,observacoes=? WHERE id=?")
            ->execute([$d['ministerio_id']??null,$d['supervisor_id']??null,$d['membro_id']??null,$d['data_escala'],$d['horario'],$d['funcao']??null,$d['status']??'pendente',$d['observacoes']??null,$id]);
        ok(['mensagem'=>'Escala atualizada']);
    }
    if ($method === 'DELETE' && $id) {
        $pdo->prepare("DELETE FROM escalas WHERE id=?")->execute([$id]);
        ok(['mensagem'=>'Escala removida']);
    }
    err('Método não suportado', 405);

// ════════════════════════════════════════════════════════════════
// EVENTOS — CRUD + múltiplos ministérios (evento_ministerios)
// ════════════════════════════════════════════════════════════════
case 'eventos':
    $pdo=db();
    if ($method === 'GET') {
        $mes=$_GET['mes']??date('m'); $ano=$_GET['ano']??date('Y');
        $st=$pdo->prepare("SELECT * FROM vw_eventos_ministerios WHERE MONTH(data_evento)=? AND YEAR(data_evento)=? ORDER BY data_evento,horario");
        $st->execute([$mes,$ano]); ok($st->fetchAll());
    }
    if ($method === 'POST') {
        $d=body();
        if (empty($d['titulo'])||empty($d['data_evento'])||empty($d['horario'])) err('Título, data e horário obrigatórios');
        $mins=(array)($d['ministerios']??[]);
        $minPrincipal=!empty($mins)?(int)$mins[0]:null;
        $pdo->beginTransaction();
        $pdo->prepare("INSERT INTO eventos (titulo,descricao,ministerio_id,data_evento,horario,tipo) VALUES (?,?,?,?,?,?)")
            ->execute([$d['titulo'],$d['descricao']??null,$minPrincipal,$d['data_evento'],$d['horario'],$d['tipo']??'culto']);
        $evtId=(int)$pdo->lastInsertId();
        if (!empty($mins)) {
            $ins=$pdo->prepare("INSERT IGNORE INTO evento_ministerios (evento_id,ministerio_id) VALUES (?,?)");
            foreach ($mins as $mId) $ins->execute([$evtId,(int)$mId]);
        }
        $pdo->commit();
        ok(['id'=>$evtId,'mensagem'=>'Evento criado','ministerios_vinculados'=>count($mins)], 201);
    }
    if ($method === 'PUT' && $id) {
        $d=body(); $mins=(array)($d['ministerios']??[]);
        $minPrincipal=!empty($mins)?(int)$mins[0]:null;
        $pdo->beginTransaction();
        $pdo->prepare("UPDATE eventos SET titulo=?,descricao=?,ministerio_id=?,data_evento=?,horario=?,tipo=? WHERE id=?")
            ->execute([$d['titulo'],$d['descricao']??null,$minPrincipal,$d['data_evento'],$d['horario'],$d['tipo']??'culto',$id]);
        $pdo->prepare("DELETE FROM evento_ministerios WHERE evento_id=?")->execute([$id]);
        if (!empty($mins)) {
            $ins=$pdo->prepare("INSERT IGNORE INTO evento_ministerios (evento_id,ministerio_id) VALUES (?,?)");
            foreach ($mins as $mId) $ins->execute([$id,(int)$mId]);
        }
        $pdo->commit();
        ok(['mensagem'=>'Evento atualizado']);
    }
    if ($method === 'DELETE' && $id) {
        $pdo->prepare("DELETE FROM eventos WHERE id=?")->execute([$id]);
        ok(['mensagem'=>'Evento removido']);
    }
    err('Método não suportado', 405);

// ════════════════════════════════════════════════════════════════
// HIERARQUIA — supervisores com membros agrupados
// ════════════════════════════════════════════════════════════════
case 'hierarquia':
    $pdo=db();
    $sups=$pdo->query("SELECT * FROM vw_resumo_supervisores ORDER BY nome")->fetchAll();
    foreach ($sups as &$s) {
        $st=$pdo->prepare("SELECT id,nome,funcao,status,avatar_iniciais,avatar_cor FROM membros WHERE ministerio_id=(SELECT ministerio_id FROM supervisores WHERE id=?) ORDER BY nome");
        $st->execute([$s['id']]); $s['membros']=$st->fetchAll();
    }
    ok($sups);

// ════════════════════════════════════════════════════════════════
// NOTIFICAÇÕES
// ════════════════════════════════════════════════════════════════
case 'notificacoes':
    $pdo=db();
    if ($method==='GET') ok($pdo->query("SELECT * FROM notificacoes ORDER BY criado_em DESC LIMIT 20")->fetchAll());
    if ($method==='PUT'&&$id) { $pdo->prepare("UPDATE notificacoes SET lida=1 WHERE id=?")->execute([$id]); ok(['mensagem'=>'Lida']); }
    err('Método não suportado', 405);

// ════════════════════════════════════════════════════════════════
// RELATÓRIOS — ?tipo=geral|presenca|equipes|membros
// ════════════════════════════════════════════════════════════════
case 'relatorios':
    $pdo=db(); $tipo=$_GET['tipo']??'geral';

    if ($tipo==='presenca') {
        ok([
            'global'         => $pdo->query("SELECT * FROM vw_presenca_global")->fetch(),
            'por_ministerio' => $pdo->query("SELECT * FROM vw_presenca_por_ministerio")->fetchAll(),
        ]);
    }

    if ($tipo==='membros') {
        $sql="SELECT * FROM vw_presenca_por_membro"; $params=[];
        if (!empty($_GET['busca'])) { $sql.=" WHERE membro LIKE ?"; $params[]="%{$_GET['busca']}%"; }
        $st=$pdo->prepare($sql); $st->execute($params); ok($st->fetchAll());
    }

    if ($tipo==='equipes') {
        ok($pdo->query("SELECT * FROM vw_resumo_por_equipe")->fetchAll());
    }

    // Padrão: geral
    $global=$pdo->query("SELECT * FROM vw_presenca_global")->fetch();
    ok([
        'voluntarios_ativos'     => (int)$pdo->query("SELECT COUNT(*) FROM membros WHERE status='ativo'")->fetchColumn(),
        'total_escalas'          => (int)($global['total_escalas']??0),
        'taxa_presenca_geral'    => (float)($global['taxa_presenca_pct']??0),
        'desempenho_equipes'     => $pdo->query("SELECT * FROM vw_presenca_por_ministerio")->fetchAll(),
        'frequencia_mensal'      => $pdo->query("
            SELECT MONTH(data_escala) AS mes, COUNT(*) AS total
            FROM escalas WHERE YEAR(data_escala)=YEAR(CURDATE())
            GROUP BY mes ORDER BY mes
        ")->fetchAll(),
    ]);

default:
    err('Recurso inválido. Disponíveis: dashboard, ministerios, supervisores, membros, escalas, eventos, hierarquia, notificacoes, relatorios', 404);

}} catch (PDOException $e) {
    err('Erro de banco: '.$e->getMessage(), 500);
} catch (Throwable $e) {
    err('Erro interno: '.$e->getMessage(), 500);
}
