<?php
/**
 * MinisterioController
 * controllers/MinisterioController.php
 *
 * GET    ?recurso=ministerios          → listar()
 * GET    ?recurso=ministerios&id=N     → detalhar()
 * POST   ?recurso=ministerios          → criar()
 * PUT    ?recurso=ministerios&id=N     → atualizar()
 * DELETE ?recurso=ministerios&id=N     → remover()
 */
require_once __DIR__ . '/../models/MinisterioModel.php';

class MinisterioController {
    private MinisterioModel $model;
    public function __construct() { $this->model = new MinisterioModel(); }

    public function listar(): void {
        resposta($this->model->listar());
    }

    public function detalhar(int $id): void {
        $m = $this->model->buscarPorId($id);
        $m ? resposta($m) : erro('Ministério não encontrado.', 404);
    }

    public function criar(): void {
        $d = corpo();
        validar($d, ['nome']);
        $id = $this->model->criar($d);
        resposta(['id' => $id, 'mensagem' => 'Ministério criado.'], 201);
    }

    public function atualizar(int $id): void {
        $d = corpo();
        validar($d, ['nome']);
        $this->model->atualizar($id, $d);
        resposta(['mensagem' => 'Ministério atualizado.']);
    }

    public function remover(int $id): void {
        $this->model->remover($id);
        resposta(['mensagem' => 'Ministério removido. Vínculos desfeitos automaticamente.']);
    }
}

// ── Despacho ──────────────────────────────────────────────────
$ctrl = new MinisterioController();
$id   = urlId();
switch (metodo()) {
    case 'GET':    $id ? $ctrl->detalhar($id) : $ctrl->listar();    break;
    case 'POST':   $ctrl->criar();                                   break;
    case 'PUT':    $id ? $ctrl->atualizar($id) : erro('ID obrigatório.',422); break;
    case 'DELETE': $id ? $ctrl->remover($id)   : erro('ID obrigatório.',422); break;
    default:       metodoNaoSuportado();
}
