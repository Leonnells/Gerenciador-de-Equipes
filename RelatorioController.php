<?php
/**
 * RelatorioController
 * controllers/RelatorioController.php
 *
 * GET ?recurso=relatorios&tipo=geral|presenca|equipes|membros
 */
require_once __DIR__ . '/../models/RelatorioModel.php';

class RelatorioController {
    private RelatorioModel $model;
    public function __construct() { $this->model = new RelatorioModel(); }

    public function despachar(): void {
        if (metodo() !== 'GET') metodoNaoSuportado();
        $tipo = param('tipo', 'geral');
        switch ($tipo) {
            case 'presenca':
                resposta([
                    'global'         => $this->model->presencaGlobal(),
                    'por_ministerio' => $this->model->presencaPorMinisterio(),
                ]);
                break;
            case 'equipes':
                resposta($this->model->resumoPorEquipe());
                break;
            case 'membros':
                $filtros = array_filter([
                    'busca'      => param('busca'),
                    'ministerio' => param('ministerio'),
                ]);
                resposta($this->model->presencaPorMembro($filtros));
                break;
            case 'geral':
            default:
                $global = $this->model->presencaGlobal();
                resposta([
                    'total_membros'      => (int)conexao()->query("SELECT COUNT(*) FROM membros WHERE status='ativo'")->fetchColumn(),
                    'total_escalas'      => (int)($global['total_escalas'] ?? 0),
                    'presenca_global'    => $global,
                    'desempenho_equipes' => $this->model->desempenhoPorEquipe(),
                    'frequencia_mensal'  => $this->model->frequenciaMensal(),
                ]);
        }
    }
}

(new RelatorioController())->despachar();
