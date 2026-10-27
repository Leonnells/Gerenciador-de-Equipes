<?php
/**
 * SupervisorController
 * controllers/SupervisorController.php
 */
require_once __DIR__ . '/../models/SupervisorModel.php';

class SupervisorController {
    private SupervisorModel $model;
    public function __construct() { $this->model = new SupervisorModel(); }

    public function listar(): void {
        $filtros = array_filter([
            'ministerio' => param('ministerio'),
            'status'     => param('status'),
            'busca'      => param('busca'),
        ]);
        resposta($this->model->listar($filtros));
    }

    public function detalhar(int $id): void {
        $s = $this->model->buscarPorId($id);
        $s ? resposta($s) : erro('Supervisor não encontrado.', 404);
    }

    public function criar(): void {
        $d = corpo();
        validar($d, ['nome','email']);
        $id = $this->model->criar($d);
        resposta(['id' => $id, 'mensagem' => 'Supervisor criado.'], 201);
    }

    public function atualizar(int $id): void {
        $d = corpo();
        validar($d, ['nome','email']);
        $this->model->atualizar($id, $d);
        resposta(['mensagem' => 'Supervisor atualizado.']);
    }

    public function remover(int $id): void {
        $this->model->remover($id);
        resposta(['mensagem' => 'Supervisor removido.']);
    }
}

$ctrl = new SupervisorController();
$id   = urlId();
switch (metodo()) {
    case 'GET':    $id ? $ctrl->detalhar($id) : $ctrl->listar();    break;
    case 'POST':   $ctrl->criar();                                   break;
    case 'PUT':    $id ? $ctrl->atualizar($id) : erro('ID obrigatório.',422); break;
    case 'DELETE': $id ? $ctrl->remover($id)   : erro('ID obrigatório.',422); break;
    default:       metodoNaoSuportado();
}
