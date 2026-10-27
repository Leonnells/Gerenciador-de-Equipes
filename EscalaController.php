<?php
/**
 * EscalaController
 * controllers/EscalaController.php
 */
require_once __DIR__ . '/../models/EscalaModel.php';

class EscalaController {
    private EscalaModel $model;
    public function __construct() { $this->model = new EscalaModel(); }

    public function listar(): void {
        $filtros = array_filter([
            'pagina'     => param('pagina','1'),
            'ministerio' => param('ministerio'),
            'status'     => param('status'),
            'membro'     => param('membro'),
            'data_ini'   => param('data_ini'),
            'data_fim'   => param('data_fim'),
        ]);
        resposta($this->model->listar($filtros));
    }

    public function detalhar(int $id): void {
        $e = $this->model->buscarPorId($id);
        $e ? resposta($e) : erro('Escala não encontrada.', 404);
    }

    public function criar(): void {
        $d = corpo();
        validar($d, ['data_escala','horario']);
        $id = $this->model->criar($d);
        resposta(['id' => $id, 'mensagem' => 'Escala criada.'], 201);
    }

    public function atualizar(int $id): void {
        $d = corpo();
        validar($d, ['data_escala','horario']);
        $this->model->atualizar($id, $d);
        resposta(['mensagem' => 'Escala atualizada.']);
    }

    public function remover(int $id): void {
        $this->model->remover($id);
        resposta(['mensagem' => 'Escala removida.']);
    }
}

$ctrl = new EscalaController();
$id   = urlId();
switch (metodo()) {
    case 'GET':    $id ? $ctrl->detalhar($id) : $ctrl->listar();    break;
    case 'POST':   $ctrl->criar();                                   break;
    case 'PUT':    $id ? $ctrl->atualizar($id) : erro('ID obrigatório.',422); break;
    case 'DELETE': $id ? $ctrl->remover($id)   : erro('ID obrigatório.',422); break;
    default:       metodoNaoSuportado();
}
