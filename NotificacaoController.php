<?php
/**
 * NotificacaoController
 * controllers/NotificacaoController.php
 */
require_once __DIR__ . '/../models/NotificacaoModel.php';

class NotificacaoController {
    private NotificacaoModel $model;
    public function __construct() { $this->model = new NotificacaoModel(); }

    public function listar(): void {
        $somenteNaoLidas = param('nao_lidas') === '1';
        resposta([
            'nao_lidas'    => $this->model->totalNaoLidas(),
            'notificacoes' => $this->model->listar($somenteNaoLidas),
        ]);
    }

    public function marcarLida(?int $id): void {
        $id ? $this->model->marcarLida($id) : $this->model->marcarTodasLidas();
        resposta(['mensagem' => $id ? 'Notificação marcada como lida.' : 'Todas marcadas como lidas.']);
    }

    public function remover(int $id): void {
        $this->model->remover($id);
        resposta(['mensagem' => 'Notificação removida.']);
    }
}

$ctrl = new NotificacaoController();
$id   = urlId();
switch (metodo()) {
    case 'GET':    $ctrl->listar();                                            break;
    case 'PUT':    $ctrl->marcarLida($id);                                     break;
    case 'DELETE': $id ? $ctrl->remover($id) : erro('ID obrigatório.',422);    break;
    default:       metodoNaoSuportado();
}
