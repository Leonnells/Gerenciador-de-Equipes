<?php
/**
 * EventoController
 * controllers/EventoController.php
 */
require_once __DIR__ . '/../models/EventoModel.php';

class EventoController {
    private EventoModel $model;
    public function __construct() { $this->model = new EventoModel(); }

    public function listar(): void {
        $mes = param('mes') ?: (int)date('m');
        $ano = param('ano') ?: (int)date('Y');
        resposta($this->model->listarPorMes((int)$mes, (int)$ano));
    }

    public function detalhar(int $id): void {
        $e = $this->model->buscarPorId($id);
        $e ? resposta($e) : erro('Evento não encontrado.', 404);
    }

    public function criar(): void {
        $d = corpo();
        validar($d, ['titulo','data_evento','horario']);
        $id = $this->model->criar($d);
        resposta(['id' => $id, 'mensagem' => 'Evento criado.'], 201);
    }

    public function atualizar(int $id): void {
        $d = corpo();
        validar($d, ['titulo','data_evento','horario']);
        $this->model->atualizar($id, $d);
        resposta(['mensagem' => 'Evento atualizado.']);
    }

    public function remover(int $id): void {
        $this->model->remover($id);
        resposta(['mensagem' => 'Evento removido.']);
    }
}

$ctrl = new EventoController();
$id   = urlId();
switch (metodo()) {
    case 'GET':    $id ? $ctrl->detalhar($id) : $ctrl->listar();              break;
    case 'POST':   $ctrl->criar();                                             break;
    case 'PUT':    $id ? $ctrl->atualizar($id) : erro('ID obrigatório.',422); break;
    case 'DELETE': $id ? $ctrl->remover($id)   : erro('ID obrigatório.',422); break;
    default:       metodoNaoSuportado();
}
