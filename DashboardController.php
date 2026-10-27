<?php
/**
 * DashboardController
 * controllers/DashboardController.php
 */
require_once __DIR__ . '/../models/DashboardModel.php';

class DashboardController {
    private DashboardModel $model;
    public function __construct() { $this->model = new DashboardModel(); }

    public function carregar(): void {
        if (metodo() !== 'GET') metodoNaoSuportado();
        resposta([
            'kpis'             => $this->model->kpis(),
            'proximas_escalas' => $this->model->proximasEscalas(),
            'proximos_eventos' => $this->model->proximosEventos(),
            'ministerios'      => $this->model->resumoMinisterios(),
        ]);
    }
}

(new DashboardController())->carregar();
