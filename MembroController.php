<?php
/**
 * MembroController
 * controllers/MembroController.php
 */
require_once __DIR__ . '/../models/MembroModel.php';

class MembroController {
    private MembroModel $model;
    public function __construct() { $this->model = new MembroModel(); }

    public function listar(): void {
        $filtros = array_filter([
            'busca'      => param('busca'),
            'ministerio' => param('ministerio'),
            'status'     => param('status'),
        ]);
        resposta($this->model->listar($filtros));
    }

    public function detalhar(int $id): void {
        $m = $this->model->buscarPorId($id);
        $m ? resposta($m) : erro('Membro não encontrado.', 404);
    }

    public function criar(): void {
        $d = corpo();
        validar($d, ['nome']);
        $id = $this->model->criar($d);
        resposta(['id' => $id, 'mensagem' => 'Membro criado.'], 201);
    }

    public function atualizar(int $id): void {
        $d = corpo();
        validar($d, ['nome']);
        $this->model->atualizar($id, $d);
        resposta(['mensagem' => 'Membro atualizado.']);
    }

    public function remover(int $id): void {
        $this->model->remover($id);
        resposta(['mensagem' => 'Membro removido.']);
    }
}

$ctrl = new MembroController();
$id   = urlId();
switch (metodo()) {
    case 'GET':    $id ? $ctrl->detalhar($id) : $ctrl->listar();    break;
    case 'POST':   $ctrl->criar();                                   break;
    case 'PUT':    $id ? $ctrl->atualizar($id) : erro('ID obrigatório.',422); break;
    case 'DELETE': $id ? $ctrl->remover($id)   : erro('ID obrigatório.',422); break;
    default:       metodoNaoSuportado();
}
