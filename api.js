/**
 * Camada de comunicação com o backend
 * assets/js/api.js
 *
 * Todas as chamadas fetch apontam para router.php.
 * Nenhum dado em memória — tudo vem do banco via PHP.
 */

const API_URL = 'router.php';

async function _fetch(recurso, metodo = 'GET', corpo = null, params = {}) {
  const qs  = new URLSearchParams({ recurso, ...params }).toString();
  const url = `${API_URL}?${qs}`;
  const opt = { method: metodo, headers: { 'Content-Type': 'application/json' } };
  if (corpo && metodo !== 'GET') opt.body = JSON.stringify(corpo);
  const resp = await fetch(url, opt);
  const json = await resp.json();
  if (!resp.ok) throw new Error(json.erro || `Erro ${resp.status}`);
  return json;
}

const API = {
  dashboard: {
    carregar: () => _fetch('dashboard'),
  },
  ministerios: {
    listar:    ()          => _fetch('ministerios'),
    detalhar:  (id)        => _fetch('ministerios','GET', null,{ id }),
    criar:     (dados)     => _fetch('ministerios','POST', dados),
    atualizar: (id, dados) => _fetch('ministerios','PUT',  dados,{ id }),
    remover:   (id)        => _fetch('ministerios','DELETE', null,{ id }),
  },
  supervisores: {
    listar:    (f = {})    => _fetch('supervisores','GET', null, f),
    detalhar:  (id)        => _fetch('supervisores','GET', null,{ id }),
    criar:     (dados)     => _fetch('supervisores','POST', dados),
    atualizar: (id, dados) => _fetch('supervisores','PUT',  dados,{ id }),
    remover:   (id)        => _fetch('supervisores','DELETE', null,{ id }),
  },
  membros: {
    listar:    (f = {})    => _fetch('membros','GET', null, f),
    detalhar:  (id)        => _fetch('membros','GET', null,{ id }),
    criar:     (dados)     => _fetch('membros','POST', dados),
    atualizar: (id, dados) => _fetch('membros','PUT',  dados,{ id }),
    remover:   (id)        => _fetch('membros','DELETE', null,{ id }),
  },
  escalas: {
    listar:    (f = {})    => _fetch('escalas','GET', null, f),
    detalhar:  (id)        => _fetch('escalas','GET', null,{ id }),
    criar:     (dados)     => _fetch('escalas','POST', dados),
    atualizar: (id, dados) => _fetch('escalas','PUT',  dados,{ id }),
    remover:   (id)        => _fetch('escalas','DELETE', null,{ id }),
  },
  eventos: {
    listar:    (mes, ano)  => _fetch('eventos','GET', null,{ mes, ano }),
    detalhar:  (id)        => _fetch('eventos','GET', null,{ id }),
    criar:     (dados)     => _fetch('eventos','POST', dados),
    atualizar: (id, dados) => _fetch('eventos','PUT',  dados,{ id }),
    remover:   (id)        => _fetch('eventos','DELETE', null,{ id }),
  },
  relatorios: {
    geral:    ()       => _fetch('relatorios','GET', null,{ tipo:'geral' }),
    presenca: ()       => _fetch('relatorios','GET', null,{ tipo:'presenca' }),
    equipes:  ()       => _fetch('relatorios','GET', null,{ tipo:'equipes' }),
    membros:  (f = {}) => _fetch('relatorios','GET', null,{ tipo:'membros', ...f }),
  },
  notificacoes: {
    listar:          ()    => _fetch('notificacoes'),
    listarNaoLidas:  ()    => _fetch('notificacoes','GET', null,{ nao_lidas:1 }),
    marcarLida:      (id)  => _fetch('notificacoes','PUT', null,{ id }),
    marcarTodasLidas:()    => _fetch('notificacoes','PUT'),
    remover:         (id)  => _fetch('notificacoes','DELETE', null,{ id }),
  },
};
