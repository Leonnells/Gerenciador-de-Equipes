/**
 * GERENCIADOR DE EQUIPES — Lógica da Aplicação
 * Igreja Verbo da Vida · Bauru
 * Arquivo: assets/js/app.js
 * Depende de: assets/js/api.js
 */

/* ────────────────────────────────────────────────────────────
   BANCO EM MEMÓRIA
──────────────────────────────────────────────────────────── */
const DB = {
  ministerios:[
    {id:1,nome:'Louvor',   cor_hex:'#0D9488',icone:'🎵'},
    {id:2,nome:'Mídia',    cor_hex:'#F97316',icone:'📷'},
    {id:3,nome:'Kids',     cor_hex:'#EAB308',icone:'🧒'},
    {id:4,nome:'Recepção', cor_hex:'#16A34A',icone:'🤝'},
    {id:5,nome:'Oração',   cor_hex:'#7C3AED',icone:'🙏'},
  ],
  supervisores:[
    {id:1,nome:'Ana Souza',     email:'ana.souza@email.com',  telefone:'(14) 99123-4567',ministerio_id:1,status:'ativo',   avatar_iniciais:'AS',avatar_cor:'#0D9488'},
    {id:2,nome:'Carlos Lima',   email:'carlos.lima@email.com',telefone:'(14) 98765-4321',ministerio_id:1,status:'ativo',   avatar_iniciais:'CL',avatar_cor:'#F97316'},
    {id:3,nome:'Maria Oliveira',email:'maria.o@email.com',    telefone:'(14) 97654-3210',ministerio_id:2,status:'pendente',avatar_iniciais:'MO',avatar_cor:'#7C3AED'},
    {id:4,nome:'Paulo Ferreira',email:'paulo.f@email.com',    telefone:'(14) 96543-2109',ministerio_id:2,status:'ativo',   avatar_iniciais:'PF',avatar_cor:'#16A34A'},
    {id:5,nome:'Lúcia Mendes',  email:'lucia.m@email.com',    telefone:'(14) 95432-1098',ministerio_id:3,status:'pendente',avatar_iniciais:'LM',avatar_cor:'#EAB308'},
    {id:6,nome:'Marcos Dias',   email:'marcos.d@email.com',   telefone:'(14) 94321-0987',ministerio_id:4,status:'ativo',   avatar_iniciais:'MD',avatar_cor:'#EC4899'},
  ],
  membros:[
    {id:1,nome:'Ana Beatriz Costa',    email:'ana.b@email.com',     ministerio_id:1,supervisor_id:1,funcao:'Violão',       habilidades:['Violão'],       disponibilidades:['Dom. Manhã','Dom. Noite'],avatar_iniciais:'A',avatar_cor:'#0D9488'},
    {id:2,nome:'Carlos Eduardo Melo',  email:'c.melo@email.com',    ministerio_id:2,supervisor_id:4,funcao:'Câmera',       habilidades:['Câmera'],       disponibilidades:['Qua. Noite','Dom. Manhã'],avatar_iniciais:'C',avatar_cor:'#F97316'},
    {id:3,nome:'Fernanda Lima Rocha',  email:'fernanda.r@email.com',ministerio_id:3,supervisor_id:5,funcao:'Professora',   habilidades:['Professora'],   disponibilidades:['Dom. Manhã'],             avatar_iniciais:'F',avatar_cor:'#7C3AED'},
    {id:4,nome:'Roberto Santos Jr.',   email:'roberto.s@email.com', ministerio_id:4,supervisor_id:6,funcao:'Recepcionista',habilidades:['Recepcionista'],disponibilidades:['Qua. Noite','Dom. Noite'],avatar_iniciais:'R',avatar_cor:'#16A34A'},
    {id:5,nome:'Juliana Pereira Silva',email:'juliana.s@email.com', ministerio_id:1,supervisor_id:1,funcao:'Vocal',        habilidades:['Vocal'],        disponibilidades:['Dom. Manhã','Dom. Noite'],avatar_iniciais:'J',avatar_cor:'#EAB308'},
    {id:6,nome:'Marcos Rodrigues',     email:'marcos.r@email.com',  ministerio_id:2,supervisor_id:4,funcao:'Designer',    habilidades:['Designer'],     disponibilidades:['Qua. Noite'],             avatar_iniciais:'M',avatar_cor:'#EC4899'},
  ],
  escalas:[
    {id:1, ministerio_id:1,ministerio:'Louvor',  membro_id:1,membro_nome:'Ana Beatriz Costa',    funcao:'Violão',       data_escala:'2025-04-06',horario:'09:00',status:'confirmado',observacoes:''},
    {id:2, ministerio_id:1,ministerio:'Louvor',  membro_id:5,membro_nome:'Juliana Pereira Silva',funcao:'Teclado',      data_escala:'2025-04-06',horario:'19:30',status:'confirmado',observacoes:''},
    {id:3, ministerio_id:2,ministerio:'Mídia',   membro_id:2,membro_nome:'Carlos Eduardo Melo',  funcao:'Câmera 1',     data_escala:'2025-04-09',horario:'19:30',status:'pendente',  observacoes:''},
    {id:4, ministerio_id:3,ministerio:'Kids',    membro_id:3,membro_nome:'Fernanda Lima Rocha',  funcao:'Professora',   data_escala:'2025-04-13',horario:'09:00',status:'confirmado',observacoes:''},
    {id:5, ministerio_id:4,ministerio:'Recepção',membro_id:4,membro_nome:'Roberto Santos Jr.',   funcao:'Recepção',     data_escala:'2025-04-13',horario:'08:30',status:'pendente',  observacoes:''},
    {id:6, ministerio_id:2,ministerio:'Mídia',   membro_id:2,membro_nome:'Carlos Eduardo Melo',  funcao:'Som',          data_escala:'2025-04-16',horario:'19:30',status:'ausente',   observacoes:'Imprevisto'},
    {id:7, ministerio_id:1,ministerio:'Louvor',  membro_id:5,membro_nome:'Juliana Pereira Silva',funcao:'Vocal',        data_escala:'2025-04-20',horario:'09:00',status:'confirmado',observacoes:''},
    {id:8, ministerio_id:3,ministerio:'Kids',    membro_id:3,membro_nome:'Fernanda Lima Rocha',  funcao:'Auxiliar',     data_escala:'2025-04-20',horario:'09:00',status:'pendente',  observacoes:''},
    {id:9, ministerio_id:1,ministerio:'Louvor',  membro_id:1,membro_nome:'Ana Beatriz Costa',    funcao:'Violão',       data_escala:'2025-04-23',horario:'09:00',status:'ausente',   observacoes:'Viagem'},
    {id:10,ministerio_id:2,ministerio:'Mídia',   membro_id:6,membro_nome:'Marcos Rodrigues',     funcao:'Designer',     data_escala:'2025-04-23',horario:'19:30',status:'confirmado',observacoes:''},
  ],
  eventos:[
    {id:1, titulo:'Culto de Louvor',   data_evento:'2025-04-06',horario:'09:00',ministerios_ids:[1,2],tipo:'louvor'},
    {id:2, titulo:'Culto de Quarta',   data_evento:'2025-04-09',horario:'19:30',ministerios_ids:[1,2,4],tipo:'culto'},
    {id:3, titulo:'Escala Especial',   data_evento:'2025-04-13',horario:'09:00',ministerios_ids:[1,2,3,4],tipo:'escala_especial'},
    {id:4, titulo:'Ministério Kids',   data_evento:'2025-04-13',horario:'09:00',ministerios_ids:[3],tipo:'kids'},
    {id:5, titulo:'Culto de Louvor',   data_evento:'2025-04-20',horario:'09:00',ministerios_ids:[1,2],tipo:'louvor'},
    {id:6, titulo:'Culto de Quarta',   data_evento:'2025-04-23',horario:'19:30',ministerios_ids:[1,2],tipo:'culto'},
    {id:7, titulo:'Reunião de Equipe', data_evento:'2025-04-27',horario:'19:00',ministerios_ids:[1,2,3,4,5],tipo:'reuniao'},
    {id:8, titulo:'Culto de Louvor',   data_evento:'2025-05-04',horario:'09:00',ministerios_ids:[1,2],tipo:'louvor'},
    {id:9, titulo:'Ministério Kids',   data_evento:'2025-05-11',horario:'09:00',ministerios_ids:[3],tipo:'kids'},
    {id:10,titulo:'Culto de Quarta',   data_evento:'2025-05-14',horario:'19:30',ministerios_ids:[1,2,4],tipo:'culto'},
  ],
  nextId:{sup:7,mem:7,esc:11,evt:11,min:6}
};

/* ────────────────────────────────────────────────────────────
   ESTADO
──────────────────────────────────────────────────────────── */
const PALETA  = ['#0D9488','#F97316','#EAB308','#16A34A','#7C3AED','#EC4899','#3B82F6','#EF4444','#14B8A6','#F59E0B'];
const ICONES  = ['🎵','📷','🧒','🤝','🙏','🎤','🎸','📢','✝️','⛪','🕊️','📖','🎺','🥁','🎹'];
const MESES   = ['Janeiro','Fevereiro','Março','Abril','Maio','Junho','Julho','Agosto','Setembro','Outubro','Novembro','Dezembro'];
const DIAS    = ['Dom','Seg','Ter','Qua','Qui','Sex','Sáb'];

let calState  = {mes:new Date().getMonth(), ano:new Date().getFullYear()};
let escPage   = 1;
let abaRel    = 'geral';
let supView   = 'cards';
let confirmCb = null;

/* ────────────────────────────────────────────────────────────
   HELPERS
──────────────────────────────────────────────────────────── */
const minById  = id => DB.ministerios.find(m=>m.id==id);
const minCor   = id => minById(id)?.cor_hex||'#64748B';
const minBg    = id => minCor(id)+'18';
const minIcon  = id => minById(id)?.icone||'⛪';
const fmtData  = d  => { if(!d) return '—'; const [y,m,day]=d.split('-'); return `${day}/${m}/${y}`; };
const fmtHora  = h  => h?h.substring(0,5):'—';
const pct      = (n,t) => t?Math.round(n/t*100):0;
const corTaxa  = t  => t>=75?'#16A34A':t>=50?'#EAB308':'#EF4444';

function badgeStatus(s){
  const m={confirmado:'badge-green',pendente:'badge-yellow',ausente:'badge-red',ativo:'badge-green',inativo:'badge-red'};
  const l={confirmado:'Confirmado',pendente:'Pendente',ausente:'Ausente',ativo:'Ativo',inativo:'Inativo'};
  return `<span class="badge ${m[s]||'badge-blue'}">${l[s]||s}</span>`;
}

function debounce(fn,ms){let t; return (...a)=>{clearTimeout(t);t=setTimeout(()=>fn(...a),ms);};}

function showToast(msg,tipo='success'){
  const c=document.getElementById('toasts');
  const t=document.createElement('div');
  t.className=`toast ${tipo}`;
  t.innerHTML=`<span>${{success:'✅',error:'❌',warning:'⚠️',info:'ℹ️'}[tipo]||'ℹ️'}</span> ${msg}`;
  c.appendChild(t); setTimeout(()=>t.remove(),3200);
}

function showConfirm(titulo,msg,cb){
  document.getElementById('confirm-title').textContent=titulo;
  document.getElementById('confirm-msg').textContent=msg;
  document.getElementById('confirm-overlay').classList.add('open');
  confirmCb=cb;
}
function closeConfirm(){document.getElementById('confirm-overlay').classList.remove('open');confirmCb=null;}
document.getElementById('confirm-ok').onclick=()=>{if(confirmCb)confirmCb();closeConfirm();};

/* ────────────────────────────────────────────────────────────
   NAVEGAÇÃO
──────────────────────────────────────────────────────────── */
function showPage(id,btn){
  document.querySelectorAll('.page').forEach(p=>p.classList.remove('active'));
  document.querySelectorAll('.tab').forEach(t=>t.classList.remove('active'));
  document.getElementById('page-'+id).classList.add('active');
  if(btn) btn.classList.add('active');
  closeDrawer();
  ({
    dashboard:  carregarDashboard,
    ministerios:renderMinisterios,
    supervisores:renderSupervisores,
    membros:    renderMembros,
    escalas:    ()=>renderEscalas(1),
    calendario: ()=>{renderCal('cal-full',false);renderProximosEventos();renderLegenda();},
    relatorios: carregarRelatorios,
  })[id]?.();
}

/* ────────────────────────────────────────────────────────────
   MODAIS
──────────────────────────────────────────────────────────── */
function openModal(id){
  document.getElementById(id)?.classList.add('open');
  preencherSelects();
  if(id==='modal-evento') preencherCbMinisterios();
  if(id==='modal-ministerio') preencherPaleta();
}
function closeModal(id){document.getElementById(id)?.classList.remove('open');}
document.querySelectorAll('.modal-overlay').forEach(o=>o.addEventListener('click',e=>{if(e.target===o)o.classList.remove('open');}));

function preencherSelects(){
  ['sup-ministerio','mem-ministerio','esc-ministerio','fil-sup-min','fil-mem-min'].forEach(sid=>{
    const el=document.getElementById(sid); if(!el) return;
    const val=el.value; const isFil=sid.startsWith('fil-');
    el.innerHTML=`<option value="">${isFil?'Ministério ▾':'Selecione…'}</option>`;
    DB.ministerios.forEach(m=>{const o=document.createElement('option');o.value=m.id;o.textContent=`${m.icone||''} ${m.nome}`;el.appendChild(o);});
    el.value=val;
  });
}

function atualizarSelectSups(){
  const minId=document.getElementById('mem-ministerio')?.value;
  const sel=document.getElementById('mem-supervisor'); if(!sel) return;
  sel.innerHTML='<option value="">Selecione…</option>';
  DB.supervisores.filter(s=>!minId||s.ministerio_id==minId).forEach(s=>{const o=document.createElement('option');o.value=s.id;o.textContent=s.nome;sel.appendChild(o);});
}

function atualizarSelectMembros(){
  const minId=document.getElementById('esc-ministerio')?.value;
  const sel=document.getElementById('esc-membro'); if(!sel) return;
  sel.innerHTML='<option value="">Selecione…</option>';
  DB.membros.filter(m=>!minId||m.ministerio_id==minId).forEach(m=>{const o=document.createElement('option');o.value=m.id;o.textContent=m.nome;sel.appendChild(o);});
}

function preencherCbMinisterios(){
  const box=document.getElementById('evt-min-checks'); if(!box) return;
  box.innerHTML=DB.ministerios.map(m=>`
    <label class="min-cb-item">
      <input type="checkbox" name="evt-min" value="${m.id}">
      <span class="min-cb-dot" style="background:${m.cor_hex}"></span>
      <span style="font-size:13px;font-weight:600">${m.icone||''} ${m.nome}</span>
    </label>`).join('');
}

function preencherPaleta(){
  const ic=document.getElementById('min-icone').value;
  const co=document.getElementById('min-cor').value;
  document.getElementById('min-icones').innerHTML=ICONES.map(i=>
    `<button type="button" class="paleta-ic${ic===i?' on':''}" data-val="${i}" onclick="selIcone('${i}')">${i}</button>`).join('');
  document.getElementById('min-cores').innerHTML=PALETA.map(c=>
    `<button type="button" class="paleta-cor${co===c?' on':''}" data-val="${c}" style="background:${c}" onclick="selCor('${c}')"></button>`).join('');
}

function selIcone(v){
  document.getElementById('min-icone').value=v;
  document.querySelectorAll('.paleta-ic').forEach(b=>{b.classList.toggle('on',b.dataset.val===v);});
}
function selCor(v){
  document.getElementById('min-cor').value=v;
  document.querySelectorAll('.paleta-cor').forEach(b=>{b.classList.toggle('on',b.dataset.val===v);});
}

/* ────────────────────────────────────────────────────────────
   DRAWER
──────────────────────────────────────────────────────────── */
function openDrawer(badge,title,sub,bodyHtml,footerHtml=''){
  document.getElementById('drw-badge').textContent  = badge;
  document.getElementById('drw-title').textContent  = title;
  document.getElementById('drw-sub').textContent    = sub;
  document.getElementById('drw-body').innerHTML     = bodyHtml;
  document.getElementById('drw-footer').innerHTML   = footerHtml;
  document.getElementById('drawer').classList.add('open');
  document.getElementById('drawer-overlay').classList.add('open');
}
function closeDrawer(){
  document.getElementById('drawer').classList.remove('open');
  document.getElementById('drawer-overlay').classList.remove('open');
}

/* ────────────────────────────────────────────────────────────
   DASHBOARD
──────────────────────────────────────────────────────────── */
function carregarDashboard(){
  document.getElementById('dash-min').textContent = DB.ministerios.length;
  document.getElementById('dash-sup').textContent = DB.supervisores.filter(s=>s.status==='ativo').length;
  document.getElementById('dash-mem').textContent = DB.membros.length;

  const hoje=new Date(); const ini=new Date(hoje); ini.setDate(hoje.getDate()-hoje.getDay());
  const fim=new Date(ini); fim.setDate(ini.getDate()+6);
  const escSem=DB.escalas.filter(e=>{const d=new Date(e.data_escala+'T00:00');return d>=ini&&d<=fim;}).length;
  document.getElementById('dash-esc').textContent=escSem;

  // badge escalas pendentes
  const pendentes=DB.escalas.filter(e=>e.status==='pendente').length;
  const badge=document.getElementById('badge-escalas');
  if(pendentes>0){badge.textContent=pendentes;badge.style.display='inline-flex';}else{badge.style.display='none';}

  // notif
  document.getElementById('notif-dot').classList.remove('hidden');

  // Próximas escalas list
  const hojeStr=new Date().toISOString().slice(0,10);
  const prox=DB.escalas.filter(e=>e.data_escala>=hojeStr).slice(0,5);
  document.getElementById('dash-escalas-list').innerHTML = prox.length
    ? prox.map(e=>`
        <div class="list-row" onclick="abrirDrawerEscala(${e.id})">
          <div class="list-row-avatar" style="background:${minCor(e.ministerio_id)}">${minIcon(e.ministerio_id)}</div>
          <div class="list-row-info">
            <div class="list-row-name">${e.membro_nome||'—'} · ${e.funcao||'—'}</div>
            <div class="list-row-sub">${e.ministerio} · ${fmtData(e.data_escala)} ${fmtHora(e.horario)}</div>
          </div>
          <div class="list-row-right">${badgeStatus(e.status)}</div>
        </div>`).join('')
    : '<div class="empty" style="padding:20px"><div class="empty-txt">Nenhuma escala futura</div></div>';

  // Ministérios mini-list
  document.getElementById('dash-min-list').innerHTML=DB.ministerios.map(m=>{
    const nS=DB.supervisores.filter(s=>s.ministerio_id==m.id).length;
    const nM=DB.membros.filter(x=>x.ministerio_id==m.id).length;
    return `
    <div class="list-row" onclick="abrirDrawerMinisterio(${m.id})">
      <div class="list-row-avatar" style="background:${m.cor_hex};border-radius:10px">${m.icone}</div>
      <div class="list-row-info">
        <div class="list-row-name">${m.nome}</div>
        <div class="list-row-sub">${nS} sup. · ${nM} vol.</div>
      </div>
      <span class="list-row-right" style="font-size:16px">›</span>
    </div>`;
  }).join('');

  renderCal('cal-mini',true);
}

/* ────────────────────────────────────────────────────────────
   MINISTÉRIOS
──────────────────────────────────────────────────────────── */
function renderMinisterios(){
  const grid=document.getElementById('grid-ministerios');
  if(!DB.ministerios.length){grid.innerHTML='<div class="empty"><div class="empty-icon">⛪</div><div class="empty-txt">Nenhum ministério</div></div>';return;}
  grid.innerHTML=DB.ministerios.map(m=>{
    const nS=DB.supervisores.filter(s=>s.ministerio_id==m.id).length;
    const nM=DB.membros.filter(x=>x.ministerio_id==m.id).length;
    const nE=DB.escalas.filter(e=>e.ministerio_id==m.id).length;
    return `
    <div class="min-card" onclick="abrirDrawerMinisterio(${m.id})">
      <div class="min-card-stripe" style="background:${m.cor_hex}"></div>
      <div class="min-card-icon" style="background:${m.cor_hex}22">${m.icone}</div>
      <div class="min-card-name">${m.nome}</div>
      <div class="min-card-desc">Clique para ver equipe completa</div>
      <div class="min-card-stats">
        <div class="min-stat"><div class="min-stat-num" style="color:${m.cor_hex}">${nS}</div><div class="min-stat-lbl">Superv.</div></div>
        <div class="min-stat"><div class="min-stat-num" style="color:${m.cor_hex}">${nM}</div><div class="min-stat-lbl">Membros</div></div>
        <div class="min-stat"><div class="min-stat-num" style="color:${m.cor_hex}">${nE}</div><div class="min-stat-lbl">Escalas</div></div>
      </div>
      <div class="min-card-footer">
        <span>Ver equipe completa</span>
        <span style="transition:transform .2s">›</span>
      </div>
    </div>`;
  }).join('');
}

function abrirDrawerMinisterio(id){
  const m=DB.ministerios.find(x=>x.id===id); if(!m) return;
  const sups=DB.supervisores.filter(s=>s.ministerio_id==id);
  const mems=DB.membros.filter(x=>x.ministerio_id==id);
  const escs=DB.escalas.filter(e=>e.ministerio_id==id);
  const conf=escs.filter(e=>e.status==='confirmado').length;
  const taxa=pct(conf,escs.length);

  const stC={ativo:'var(--green-l)',pendente:'var(--yellow-l)',inativo:'var(--red-l)'};
  const stT={ativo:'#065F46',pendente:'#713F12',inativo:'#991B1B'};

  const body=`
    <div class="min-stat-row">
      <div class="min-stat-box"><div class="min-stat-box-num" style="color:${m.cor_hex}">${sups.length}</div><div class="min-stat-box-lbl">Supervisores</div></div>
      <div class="min-stat-box"><div class="min-stat-box-num" style="color:${m.cor_hex}">${mems.length}</div><div class="min-stat-box-lbl">Voluntários</div></div>
      <div class="min-stat-box"><div class="min-stat-box-num" style="color:${m.cor_hex}">${escs.length}</div><div class="min-stat-box-lbl">Escalas</div></div>
      <div class="min-stat-box"><div class="min-stat-box-num" style="color:${corTaxa(taxa)}">${escs.length?taxa+'%':'—'}</div><div class="min-stat-box-lbl">Presença</div></div>
    </div>
    <div class="drawer-section">
      <div class="drawer-section-title">👤 Supervisores <span class="drawer-count">${sups.length}</span></div>
      ${sups.length?sups.map(s=>`
        <div class="drawer-person-row">
          <div class="drawer-person-av" style="background:${s.avatar_cor||m.cor_hex}">${s.avatar_iniciais||s.nome[0]}</div>
          <div><div class="drawer-person-name">${s.nome}</div><div class="drawer-person-role">${s.email}</div></div>
          <span class="drawer-person-badge" style="background:${stC[s.status]||'#E2E8F0'};color:${stT[s.status]||'#666'}">${s.status}</span>
        </div>`).join(''):'<div class="drawer-empty">Nenhum supervisor</div>'}
    </div>
    <div class="drawer-section">
      <div class="drawer-section-title">👥 Voluntários <span class="drawer-count">${mems.length}</span></div>
      ${mems.length?mems.map(x=>`
        <div class="drawer-person-row">
          <div class="drawer-person-av" style="background:${x.avatar_cor||m.cor_hex}">${x.avatar_iniciais||x.nome[0]}</div>
          <div><div class="drawer-person-name">${x.nome}</div><div class="drawer-person-role">${x.funcao||'Voluntário'}</div></div>
          <span class="drawer-person-badge" style="background:${m.cor_hex}22;color:${m.cor_hex}">${x.funcao||'—'}</span>
        </div>`).join(''):'<div class="drawer-empty">Nenhum voluntário</div>'}
    </div>`;

  const footer=`
    <button class="btn btn-ghost" style="flex:1" onclick="editarMinisterio(${id})">✏️ Editar</button>
    <button class="btn btn-danger" style="flex:1" onclick="excluirMinisterio(${id})">🗑 Remover</button>`;

  openDrawer(m.icone||'⛪',m.nome,`${sups.length} supervisores · ${mems.length} voluntários`,body,footer);
}

function abrirModalMinisterio(){
  document.getElementById('modal-min-titulo').textContent='Novo Ministério';
  document.getElementById('min-id').value='';
  document.getElementById('min-nome').value='';
  document.getElementById('min-icone').value='⛪';
  document.getElementById('min-cor').value='#0D9488';
  openModal('modal-ministerio');
}

function editarMinisterio(id){
  const m=DB.ministerios.find(x=>x.id===id); if(!m) return;
  document.getElementById('modal-min-titulo').textContent='Editar Ministério';
  document.getElementById('min-id').value=m.id;
  document.getElementById('min-nome').value=m.nome;
  document.getElementById('min-icone').value=m.icone||'⛪';
  document.getElementById('min-cor').value=m.cor_hex;
  closeDrawer();
  openModal('modal-ministerio');
  setTimeout(()=>{
    document.querySelectorAll('.paleta-ic').forEach(b=>b.classList.toggle('on',b.dataset.val===m.icone));
    document.querySelectorAll('.paleta-cor').forEach(b=>b.classList.toggle('on',b.dataset.val===m.cor_hex));
  },30);
}

function salvarMinisterio(){
  const nome=document.getElementById('min-nome').value.trim();
  const icone=document.getElementById('min-icone').value||'⛪';
  const cor=document.getElementById('min-cor').value||'#0D9488';
  const id=document.getElementById('min-id').value;
  if(!nome){showToast('Nome obrigatório','error');return;}
  if(id){
    const idx=DB.ministerios.findIndex(m=>m.id==id);
    if(idx>=0) DB.ministerios[idx]={...DB.ministerios[idx],nome,icone,cor_hex:cor};
    showToast('Ministério atualizado!','success');
  } else {
    DB.ministerios.push({id:DB.nextId.min++,nome,icone,cor_hex:cor});
    showToast('Ministério criado!','success');
  }
  closeModal('modal-ministerio');
  preencherSelects();
  renderMinisterios();
  carregarDashboard();
}

function excluirMinisterio(id){
  const m=DB.ministerios.find(x=>x.id===id);
  const nS=DB.supervisores.filter(s=>s.ministerio_id==id).length;
  const nM=DB.membros.filter(x=>x.ministerio_id==id).length;
  showConfirm('Excluir Ministério',`Remover "${m?.nome}"?${nS||nM?` ${nS} sup. e ${nM} membros serão desvinculados.`:''}`,()=>{
    DB.ministerios=DB.ministerios.filter(x=>x.id!==id);
    DB.supervisores.filter(s=>s.ministerio_id==id).forEach(s=>{s.ministerio_id=null;});
    DB.membros.filter(x=>x.ministerio_id==id).forEach(x=>{x.ministerio_id=null;});
    DB.escalas.filter(e=>e.ministerio_id==id).forEach(e=>{e.ministerio_id=null;e.ministerio='';});
    closeDrawer();
    showToast('Ministério removido','warning');
    preencherSelects();
    renderMinisterios();
    carregarDashboard();
  });
}

/* ────────────────────────────────────────────────────────────
   SUPERVISORES
──────────────────────────────────────────────────────────── */
function setSupView(mode){
  supView=mode;
  document.getElementById('vbtn-sup-cards').classList.toggle('on',mode==='cards');
  document.getElementById('vbtn-sup-lista').classList.toggle('on',mode==='lista');
  document.getElementById('sup-cards-wrap').style.display=mode==='cards'?'block':'none';
  document.getElementById('sup-lista-wrap').style.display=mode==='lista'?'block':'none';
  renderSupervisores();
}

function renderSupervisores(){
  const busca=document.getElementById('fil-sup-busca')?.value.toLowerCase()||'';
  const minFil=document.getElementById('fil-sup-min')?.value||'';
  const stFil=document.getElementById('fil-sup-status')?.value||'';
  const lista=DB.supervisores.filter(s=>
    (!busca||s.nome.toLowerCase().includes(busca)||s.email.toLowerCase().includes(busca))&&
    (!minFil||s.ministerio_id==minFil)&&(!stFil||s.status===stFil)
  );
  supView==='cards'?renderSupCards(lista):renderSupLista(lista);
}

function renderSupCards(lista){
  const wrap=document.getElementById('sup-cards-wrap');
  if(!lista.length){wrap.innerHTML='<div class="empty"><div class="empty-icon">👤</div><div class="empty-txt">Nenhum supervisor</div></div>';return;}
  wrap.innerHTML=`<div class="cards-grid">${lista.map(s=>{
    const m=minById(s.ministerio_id);
    const nMem=DB.membros.filter(x=>x.ministerio_id==s.ministerio_id).length;
    const cor=s.avatar_cor||minCor(s.ministerio_id);
    const stBg={ativo:'var(--green-l)',pendente:'var(--yellow-l)',inativo:'var(--red-l)'};
    const stTx={ativo:'#065F46',pendente:'#713F12',inativo:'#991B1B'};
    return `
    <div class="person-card" onclick="abrirDrawerSupervisor(${s.id})">
      <div class="person-card-stripe" style="background:${cor}"></div>
      <div class="person-card-top">
        <div class="person-avatar" style="background:${cor}">${s.avatar_iniciais||s.nome[0]}</div>
        <div style="flex:1;min-width:0">
          <div class="person-name">${s.nome}</div>
          <div class="person-role">${m?`${m.icone} ${m.nome}`:'Sem ministério'}</div>
        </div>
        <span class="badge" style="background:${stBg[s.status]||'#E2E8F0'};color:${stTx[s.status]||'#666'};font-size:10px">${s.status}</span>
      </div>
      <div class="person-card-tags">
        <span class="tag" style="background:${minBg(s.ministerio_id)};color:${cor}">${minIcon(s.ministerio_id)} ${m?.nome||'—'}</span>
        <span class="tag" style="background:#F1F5F9;color:var(--muted)">👥 ${nMem} membro${nMem!==1?'s':''}</span>
        ${s.telefone?`<span class="tag" style="background:#F1F5F9;color:var(--muted)">📞 ${s.telefone}</span>`:''}
      </div>
      <div class="person-card-footer">
        <span>Ver equipe completa</span>
        <span class="person-card-footer-arrow">›</span>
      </div>
    </div>`;
  }).join('')}</div>`;
}

function renderSupLista(lista){
  const tbody=document.getElementById('tbody-sup');
  if(!lista.length){tbody.innerHTML=`<tr><td colspan="6" style="text-align:center;padding:30px;color:var(--muted)">Nenhum supervisor</td></tr>`;return;}
  tbody.innerHTML=lista.map(s=>{
    const m=minById(s.ministerio_id);
    const nMem=DB.membros.filter(x=>x.ministerio_id==s.ministerio_id).length;
    return `<tr onclick="abrirDrawerSupervisor(${s.id})">
      <td><div style="display:flex;align-items:center;gap:9px">
        <div style="width:34px;height:34px;border-radius:50%;background:${s.avatar_cor||'#0D9488'};display:flex;align-items:center;justify-content:center;color:#fff;font-weight:800;font-size:12px">${s.avatar_iniciais||s.nome[0]}</div>
        <div><strong>${s.nome}</strong><br><span style="color:var(--muted);font-size:11px">${s.email}</span></div>
      </div></td>
      <td style="font-weight:600;color:${minCor(s.ministerio_id)}">${minIcon(s.ministerio_id)} ${m?.nome||'—'}</td>
      <td style="font-weight:700">${nMem}</td>
      <td style="color:var(--muted);font-size:12px">${s.telefone||'—'}</td>
      <td>${badgeStatus(s.status)}</td>
      <td onclick="event.stopPropagation()">
        <button class="action-btn" onclick="editarSupervisor(${s.id})">✏️</button>
        <button class="action-btn" onclick="excluirSupervisor(${s.id})">🗑</button>
      </td>
    </tr>`;
  }).join('');
}

function abrirDrawerSupervisor(id){
  const s=DB.supervisores.find(x=>x.id===id); if(!s) return;
  const m=minById(s.ministerio_id);
  const mems=DB.membros.filter(x=>x.ministerio_id==s.ministerio_id);
  const escs=DB.escalas.filter(e=>e.ministerio_id==s.ministerio_id);
  const conf=escs.filter(e=>e.status==='confirmado').length;

  const body=`
    <div class="drawer-section">
      <div class="drawer-section-title">Informações</div>
      <div class="drawer-field"><div class="drawer-field-lbl">E-mail</div><div class="drawer-field-val">${s.email}</div></div>
      <div class="drawer-field"><div class="drawer-field-lbl">Telefone</div><div class="drawer-field-val">${s.telefone||'—'}</div></div>
      <div class="drawer-field"><div class="drawer-field-lbl">Ministério</div><div class="drawer-field-val">${m?`${m.icone} ${m.nome}`:'—'}</div></div>
      <div class="drawer-field"><div class="drawer-field-lbl">Status</div><div class="drawer-field-val">${badgeStatus(s.status)}</div></div>
    </div>
    <div class="drawer-section">
      <div class="drawer-section-title">👥 Equipe vinculada <span class="drawer-count">${mems.length}</span></div>
      ${mems.length?mems.map(x=>`
        <div class="drawer-person-row">
          <div class="drawer-person-av" style="background:${x.avatar_cor||minCor(s.ministerio_id)}">${x.avatar_iniciais||x.nome[0]}</div>
          <div><div class="drawer-person-name">${x.nome}</div><div class="drawer-person-role">${x.funcao||'Voluntário'}</div></div>
          <span class="drawer-person-badge" style="background:${minBg(s.ministerio_id)};color:${minCor(s.ministerio_id)}">${x.funcao||'—'}</span>
        </div>`).join(''):'<div class="drawer-empty">Sem membros nesta equipe</div>'}
    </div>
    <div class="drawer-section">
      <div class="drawer-section-title">📋 Escalas <span class="drawer-count">${escs.length}</span></div>
      <div style="font-size:12px;color:var(--muted)">${escs.length} escalas · ${conf} confirmadas · taxa ${pct(conf,escs.length)}%</div>
    </div>`;

  const footer=`
    <button class="btn btn-ghost" style="flex:1" onclick="editarSupervisor(${id})">✏️ Editar</button>
    <button class="btn btn-danger" style="flex:1" onclick="excluirSupervisor(${id})">🗑 Remover</button>`;

  openDrawer(s.avatar_iniciais||s.nome[0],s.nome,`${m?m.nome:'Sem ministério'} · ${mems.length} membros`,body,footer);
}

function editarSupervisor(id){
  const s=DB.supervisores.find(x=>x.id===id); if(!s) return;
  document.getElementById('modal-sup-titulo').textContent='Editar Supervisor';
  document.getElementById('sup-id').value=s.id;
  document.getElementById('sup-nome').value=s.nome;
  document.getElementById('sup-email').value=s.email;
  document.getElementById('sup-telefone').value=s.telefone||'';
  document.getElementById('sup-status').value=s.status;
  closeDrawer();
  openModal('modal-supervisor');
  setTimeout(()=>{document.getElementById('sup-ministerio').value=s.ministerio_id||'';},50);
}

function salvarSupervisor(){
  const nome=document.getElementById('sup-nome').value.trim();
  const email=document.getElementById('sup-email').value.trim();
  const tel=document.getElementById('sup-telefone').value.trim();
  const minId=document.getElementById('sup-ministerio').value;
  const status=document.getElementById('sup-status').value;
  const id=document.getElementById('sup-id').value;
  if(!nome||!email){showToast('Nome e e-mail obrigatórios','error');return;}
  const iniciais=nome.split(' ').map(p=>p[0]).join('').substring(0,2).toUpperCase();
  if(id){
    const idx=DB.supervisores.findIndex(s=>s.id==id);
    if(idx>=0) DB.supervisores[idx]={...DB.supervisores[idx],nome,email,telefone:tel,ministerio_id:minId?parseInt(minId):null,status,avatar_iniciais:iniciais};
    showToast('Supervisor atualizado!','success');
  } else {
    DB.supervisores.push({id:DB.nextId.sup++,nome,email,telefone:tel,ministerio_id:minId?parseInt(minId):null,status,avatar_iniciais:iniciais,avatar_cor:PALETA[DB.supervisores.length%PALETA.length]});
    showToast('Supervisor cadastrado!','success');
  }
  closeModal('modal-supervisor');
  document.getElementById('sup-id').value='';
  document.getElementById('modal-sup-titulo').textContent='Novo Supervisor';
  ['sup-nome','sup-email','sup-telefone'].forEach(f=>document.getElementById(f).value='');
  renderSupervisores(); carregarDashboard();
}

function excluirSupervisor(id){
  const s=DB.supervisores.find(x=>x.id===id);
  showConfirm('Excluir Supervisor',`Remover "${s?.nome}"?`,()=>{
    DB.supervisores=DB.supervisores.filter(x=>x.id!==id);
    DB.membros.filter(m=>m.supervisor_id===id).forEach(m=>m.supervisor_id=null);
    closeDrawer();
    showToast('Supervisor removido','warning');
    renderSupervisores(); carregarDashboard();
  });
}

/* ────────────────────────────────────────────────────────────
   MEMBROS
──────────────────────────────────────────────────────────── */
function renderMembros(){
  const busca=document.getElementById('fil-mem-busca')?.value.toLowerCase()||'';
  const minFil=document.getElementById('fil-mem-min')?.value||'';
  const lista=DB.membros.filter(m=>(!busca||m.nome.toLowerCase().includes(busca))&&(!minFil||m.ministerio_id==minFil));
  const grid=document.getElementById('grid-membros');
  if(!lista.length){grid.innerHTML='<div class="empty"><div class="empty-icon">👥</div><div class="empty-txt">Nenhum membro</div></div>';return;}
  grid.innerHTML=lista.map(m=>{
    const mObj=minById(m.ministerio_id);
    const cor=m.avatar_cor||minCor(m.ministerio_id);
    const escM=DB.escalas.filter(e=>e.membro_id===m.id);
    const conf=escM.filter(e=>e.status==='confirmado').length;
    return `
    <div class="person-card" onclick="abrirDrawerMembro(${m.id})">
      <div class="person-card-stripe" style="background:${cor}"></div>
      <div class="person-card-top">
        <div class="person-avatar" style="background:${cor}">${m.avatar_iniciais||m.nome[0]}</div>
        <div style="flex:1;min-width:0">
          <div class="person-name">${m.nome}</div>
          <div class="person-role">${m.funcao||'Voluntário'}</div>
        </div>
      </div>
      <div class="person-card-tags">
        ${mObj?`<span class="tag" style="background:${mObj.cor_hex}22;color:${mObj.cor_hex}">${mObj.icone} ${mObj.nome}</span>`:''}
        ${(m.habilidades||[]).map(h=>`<span class="tag" style="background:#FCE7F3;color:#9D174D">${h}</span>`).join('')}
        ${(m.disponibilidades||[]).map(d=>`<span class="tag" style="background:var(--green-l);color:#065F46">${d}</span>`).join('')}
      </div>
      <div class="person-card-footer">
        <span>${escM.length} esc. · ${conf} conf.</span>
        <span class="person-card-footer-arrow">›</span>
      </div>
    </div>`;
  }).join('');
}

function abrirDrawerMembro(id){
  const m=DB.membros.find(x=>x.id===id); if(!m) return;
  const mObj=minById(m.ministerio_id);
  const sup=DB.supervisores.find(s=>s.id===m.supervisor_id);
  const escs=DB.escalas.filter(e=>e.membro_id===m.id);
  const conf=escs.filter(e=>e.status==='confirmado').length;
  const aus=escs.filter(e=>e.status==='ausente').length;
  const taxa=pct(conf,escs.length);

  const body=`
    <div class="drawer-section">
      <div class="drawer-section-title">Informações</div>
      <div class="drawer-field"><div class="drawer-field-lbl">E-mail</div><div class="drawer-field-val">${m.email||'—'}</div></div>
      <div class="drawer-field"><div class="drawer-field-lbl">Telefone</div><div class="drawer-field-val">${m.telefone||'—'}</div></div>
      <div class="drawer-field"><div class="drawer-field-lbl">Ministério</div><div class="drawer-field-val">${mObj?`${mObj.icone} ${mObj.nome}`:'—'}</div></div>
      <div class="drawer-field"><div class="drawer-field-lbl">Supervisor</div><div class="drawer-field-val">${sup?.nome||'—'}</div></div>
      <div class="drawer-field"><div class="drawer-field-lbl">Função</div><div class="drawer-field-val">${m.funcao||'—'}</div></div>
    </div>
    <div class="drawer-section">
      <div class="drawer-section-title">Habilidades e Disponibilidade</div>
      <div style="display:flex;flex-wrap:wrap;gap:4px;margin-bottom:8px">
        ${(m.habilidades||[]).map(h=>`<span class="tag" style="background:#FCE7F3;color:#9D174D">${h}</span>`).join('')||'<span style="color:var(--muted);font-size:12px">Nenhuma</span>'}
      </div>
      <div style="display:flex;flex-wrap:wrap;gap:4px">
        ${(m.disponibilidades||[]).map(d=>`<span class="tag" style="background:var(--green-l);color:#065F46">${d}</span>`).join('')||'<span style="color:var(--muted);font-size:12px">Nenhuma</span>'}
      </div>
    </div>
    <div class="drawer-section">
      <div class="drawer-section-title">📋 Histórico de Escalas</div>
      <div style="display:flex;gap:10px;margin-bottom:12px;font-size:12px">
        <span style="color:var(--green);font-weight:700">✅ ${conf} conf.</span>
        <span style="color:var(--red);font-weight:700">❌ ${aus} falt.</span>
        <span style="color:var(--muted)">Taxa: <strong>${escs.length?taxa+'%':'—'}</strong></span>
      </div>
      ${escs.slice(-5).reverse().map(e=>`
        <div style="display:flex;align-items:center;justify-content:space-between;padding:7px 10px;border-radius:8px;background:var(--bg);margin-bottom:5px;font-size:12px">
          <div>
            <span style="font-weight:700;color:${minCor(e.ministerio_id)}">${minIcon(e.ministerio_id)} ${e.ministerio||'—'}</span>
            <span style="color:var(--muted);margin-left:6px">${fmtData(e.data_escala)} · ${e.funcao||'—'}</span>
          </div>
          ${badgeStatus(e.status)}
        </div>`).join('')||'<div class="drawer-empty">Sem escalas</div>'}
    </div>`;

  const footer=`
    <button class="btn btn-ghost" style="flex:1" onclick="editarMembro(${id})">✏️ Editar</button>
    <button class="btn btn-danger" style="flex:1" onclick="excluirMembro(${id})">🗑 Remover</button>`;

  openDrawer(m.avatar_iniciais||m.nome[0],m.nome,`${mObj?mObj.nome:'Sem ministério'} · ${m.funcao||'Voluntário'}`,body,footer);
}

function editarMembro(id){
  const m=DB.membros.find(x=>x.id===id); if(!m) return;
  document.getElementById('modal-mem-titulo').textContent='Editar Membro';
  document.getElementById('mem-id').value=m.id;
  document.getElementById('mem-nome').value=m.nome;
  document.getElementById('mem-email').value=m.email||'';
  document.getElementById('mem-telefone').value=m.telefone||'';
  document.getElementById('mem-funcao').value=m.funcao||'';
  closeDrawer();
  openModal('modal-membro');
  setTimeout(()=>{
    document.getElementById('mem-ministerio').value=m.ministerio_id||'';
    atualizarSelectSups();
    setTimeout(()=>{document.getElementById('mem-supervisor').value=m.supervisor_id||'';},30);
  },50);
}

function salvarMembro(){
  const nome=document.getElementById('mem-nome').value.trim();
  const email=document.getElementById('mem-email').value.trim();
  const tel=document.getElementById('mem-telefone').value.trim();
  const minId=document.getElementById('mem-ministerio').value;
  const supId=document.getElementById('mem-supervisor').value;
  const funcao=document.getElementById('mem-funcao').value.trim();
  const id=document.getElementById('mem-id').value;
  if(!nome){showToast('Nome obrigatório','error');return;}
  if(id){
    const idx=DB.membros.findIndex(m=>m.id==id);
    if(idx>=0) DB.membros[idx]={...DB.membros[idx],nome,email,telefone:tel,ministerio_id:minId?parseInt(minId):null,supervisor_id:supId?parseInt(supId):null,funcao,avatar_iniciais:nome[0].toUpperCase()};
    showToast('Membro atualizado!','success');
  } else {
    DB.membros.push({id:DB.nextId.mem++,nome,email,telefone:tel,ministerio_id:minId?parseInt(minId):null,supervisor_id:supId?parseInt(supId):null,funcao,habilidades:[],disponibilidades:[],avatar_iniciais:nome[0].toUpperCase(),avatar_cor:PALETA[DB.membros.length%PALETA.length]});
    showToast('Membro cadastrado!','success');
  }
  closeModal('modal-membro');
  document.getElementById('mem-id').value='';
  document.getElementById('modal-mem-titulo').textContent='Novo Membro';
  ['mem-nome','mem-email','mem-telefone','mem-funcao'].forEach(f=>document.getElementById(f).value='');
  renderMembros(); carregarDashboard();
}

function excluirMembro(id){
  const m=DB.membros.find(x=>x.id===id);
  showConfirm('Excluir Membro',`Remover "${m?.nome}"?`,()=>{
    DB.membros=DB.membros.filter(x=>x.id!==id);
    closeDrawer(); showToast('Membro removido','warning');
    renderMembros(); carregarDashboard();
  });
}

/* ────────────────────────────────────────────────────────────
   ESCALAS
──────────────────────────────────────────────────────────── */
function renderEscalas(pagina=1){
  escPage=pagina;
  const POR=8; const ini=(pagina-1)*POR;
  const lista=DB.escalas.slice(ini,ini+POR);
  const totalPags=Math.ceil(DB.escalas.length/POR);
  const tbody=document.getElementById('tbody-escalas');
  tbody.innerHTML=lista.length
    ?lista.map(e=>`
      <tr onclick="abrirDrawerEscala(${e.id})">
        <td><span style="display:inline-flex;align-items:center;gap:5px;font-weight:700;color:${minCor(e.ministerio_id)}">${minIcon(e.ministerio_id)} ${e.ministerio||'—'}</span></td>
        <td style="color:var(--muted)">${fmtData(e.data_escala)}</td>
        <td style="color:var(--muted)">${fmtHora(e.horario)}</td>
        <td><strong>${e.membro_nome||'—'}</strong></td>
        <td style="color:var(--muted)">${e.funcao||'—'}</td>
        <td>${badgeStatus(e.status)}</td>
        <td onclick="event.stopPropagation()">
          <button class="action-btn" onclick="editarEscala(${e.id})">✏️</button>
          <button class="action-btn" onclick="excluirEscala(${e.id})">🗑</button>
        </td>
      </tr>`).join('')
    :`<tr><td colspan="7" style="text-align:center;padding:30px;color:var(--muted)">Nenhuma escala</td></tr>`;
  const pag=document.getElementById('pag-escalas');
  pag.innerHTML=`<span>Pág. ${pagina}/${totalPags||1}</span>
    <button class="page-btn" ${pagina<=1?'disabled':''} onclick="renderEscalas(1)">«</button>
    <button class="page-btn" ${pagina<=1?'disabled':''} onclick="renderEscalas(${pagina-1})">‹</button>
    <button class="page-btn" ${pagina>=totalPags?'disabled':''} onclick="renderEscalas(${pagina+1})">›</button>
    <button class="page-btn" ${pagina>=totalPags?'disabled':''} onclick="renderEscalas(${totalPags})">»</button>`;
}

function abrirDrawerEscala(id){
  const e=DB.escalas.find(x=>x.id===id); if(!e) return;
  const m=DB.membros.find(x=>x.id===e.membro_id);
  const mObj=minById(e.ministerio_id);
  const sup=m?DB.supervisores.find(s=>s.id===m.supervisor_id):null;

  const body=`
    <div class="drawer-section">
      <div class="drawer-section-title">Detalhes da Escala</div>
      <div class="drawer-field"><div class="drawer-field-lbl">Ministério</div><div class="drawer-field-val">${mObj?`${mObj.icone} ${mObj.nome}`:'—'}</div></div>
      <div class="drawer-field"><div class="drawer-field-lbl">Data</div><div class="drawer-field-val">${fmtData(e.data_escala)}</div></div>
      <div class="drawer-field"><div class="drawer-field-lbl">Horário</div><div class="drawer-field-val">${fmtHora(e.horario)}</div></div>
      <div class="drawer-field"><div class="drawer-field-lbl">Função</div><div class="drawer-field-val">${e.funcao||'—'}</div></div>
      <div class="drawer-field"><div class="drawer-field-lbl">Status</div><div class="drawer-field-val">${badgeStatus(e.status)}</div></div>
      ${e.observacoes?`<div class="drawer-field"><div class="drawer-field-lbl">Observações</div><div class="drawer-field-val">${e.observacoes}</div></div>`:''}
    </div>
    ${m?`<div class="drawer-section">
      <div class="drawer-section-title">👤 Membro Escalado</div>
      <div class="drawer-person-row">
        <div class="drawer-person-av" style="background:${m.avatar_cor||minCor(e.ministerio_id)}">${m.avatar_iniciais||m.nome[0]}</div>
        <div>
          <div class="drawer-person-name">${m.nome}</div>
          <div class="drawer-person-role">${m.email||'—'} · ${m.telefone||'—'}</div>
        </div>
      </div>
      ${sup?`<div style="font-size:12px;color:var(--muted);margin-top:4px">Supervisor: <strong>${sup.nome}</strong></div>`:''}
    </div>`:''}`;

  const footer=`
    <button class="btn btn-ghost" style="flex:1" onclick="editarEscala(${id})">✏️ Editar</button>
    <button class="btn btn-danger" style="flex:1" onclick="excluirEscala(${id})">🗑 Remover</button>`;

  openDrawer(minIcon(e.ministerio_id),`${e.ministerio||'Escala'} — ${e.funcao||''}`,`${fmtData(e.data_escala)} · ${fmtHora(e.horario)}`,body,footer);
}

function editarEscala(id){
  const e=DB.escalas.find(x=>x.id===id); if(!e) return;
  document.getElementById('modal-esc-titulo').textContent='Editar Escala';
  document.getElementById('esc-id').value=e.id;
  document.getElementById('esc-data').value=e.data_escala;
  document.getElementById('esc-horario').value=e.horario;
  document.getElementById('esc-funcao').value=e.funcao||'';
  document.getElementById('esc-status').value=e.status;
  document.getElementById('esc-obs').value=e.observacoes||'';
  closeDrawer();
  openModal('modal-escala');
  setTimeout(()=>{
    document.getElementById('esc-ministerio').value=e.ministerio_id||'';
    atualizarSelectMembros();
    setTimeout(()=>{document.getElementById('esc-membro').value=e.membro_id||'';},30);
  },50);
}

function salvarEscala(){
  const data=document.getElementById('esc-data').value;
  const hora=document.getElementById('esc-horario').value;
  const minId=document.getElementById('esc-ministerio').value;
  const funcao=document.getElementById('esc-funcao').value.trim();
  const status=document.getElementById('esc-status').value;
  const obs=document.getElementById('esc-obs').value.trim();
  const memId=document.getElementById('esc-membro').value;
  const id=document.getElementById('esc-id').value;
  if(!data||!hora){showToast('Data e horário obrigatórios','error');return;}
  const mObj=minById(minId);
  const memObj=DB.membros.find(m=>m.id==memId);
  if(id){
    const idx=DB.escalas.findIndex(e=>e.id==id);
    if(idx>=0) DB.escalas[idx]={...DB.escalas[idx],ministerio_id:minId?parseInt(minId):null,ministerio:mObj?.nome||'',membro_id:memId?parseInt(memId):null,membro_nome:memObj?.nome||'',funcao,status,observacoes:obs,data_escala:data,horario:hora};
    showToast('Escala atualizada!','success');
  } else {
    DB.escalas.push({id:DB.nextId.esc++,ministerio_id:minId?parseInt(minId):null,ministerio:mObj?.nome||'',membro_id:memId?parseInt(memId):null,membro_nome:memObj?.nome||'',funcao,status,observacoes:obs,data_escala:data,horario:hora});
    showToast('Escala criada!','success');
  }
  closeModal('modal-escala');
  document.getElementById('esc-id').value='';
  document.getElementById('modal-esc-titulo').textContent='Nova Escala';
  ['esc-data','esc-horario','esc-funcao','esc-obs'].forEach(f=>document.getElementById(f).value='');
  renderEscalas(escPage); carregarDashboard();
}

function excluirEscala(id){
  showConfirm('Excluir Escala','Remover esta escala?',()=>{
    DB.escalas=DB.escalas.filter(e=>e.id!==id);
    closeDrawer(); showToast('Escala removida','warning');
    renderEscalas(escPage); carregarDashboard();
  });
}

function exportarEscalas(){
  const h=['Ministério','Data','Horário','Membro','Função','Status'];
  const r=DB.escalas.map(e=>[e.ministerio||'—',fmtData(e.data_escala),fmtHora(e.horario),e.membro_nome||'—',e.funcao||'—',e.status]);
  const csv=[h,...r].map(row=>row.map(c=>`"${c}"`).join(',')).join('\n');
  const a=document.createElement('a');a.href=URL.createObjectURL(new Blob(['\uFEFF'+csv],{type:'text/csv;charset=utf-8;'}));a.download='escalas.csv';a.click();
  showToast('Exportado!','success');
}

/* ────────────────────────────────────────────────────────────
   CALENDÁRIO
──────────────────────────────────────────────────────────── */
function navCal(dir){
  calState.mes+=dir;
  if(calState.mes>11){calState.mes=0;calState.ano++;}
  if(calState.mes<0){calState.mes=11;calState.ano--;}
  renderCal('cal-full',false); renderCal('cal-mini',true);
  renderProximosEventos();
}

function evtCor(evt){return minCor((evt.ministerios_ids||[])[0]);}

function renderCal(containerId,mini){
  const {mes,ano}=calState;
  const titulo=document.getElementById('cal-titulo');
  if(titulo) titulo.textContent=`${MESES[mes]} ${ano}`;
  const container=document.getElementById(containerId); if(!container) return;
  const primeiro=new Date(ano,mes,1).getDay();
  const dias=new Date(ano,mes+1,0).getDate();
  const hoje=new Date();
  const evts=DB.eventos.filter(e=>{const d=new Date(e.data_evento+'T00:00');return d.getMonth()===mes&&d.getFullYear()===ano;});

  let html=`<table class="cal-table"><thead><tr>`;
  DIAS.forEach(d=>html+=`<th>${mini?d[0]:d}</th>`);
  html+=`</tr></thead><tbody>`;
  let dia=1,cel=0;
  while(dia<=dias){
    html+='<tr>';
    for(let col=0;col<7;col++){
      if((cel<primeiro&&dia===1)||dia>dias){html+='<td></td>';}
      else{
        const isHoje=dia===hoje.getDate()&&mes===hoje.getMonth()&&ano===hoje.getFullYear();
        const evtsDia=evts.filter(e=>new Date(e.data_evento+'T00:00').getDate()===dia);
        html+=`<td class="${isHoje?'today':''}" onclick="diaClick(${dia},${mes},${ano})">`;
        html+=`<div class="cal-day-num">${dia}</div><div class="cal-cell-inner">`;
        const MAX=mini?1:2;
        evtsDia.slice(0,MAX).forEach(e=>{const c=evtCor(e);html+=`<span class="cal-evt" style="background:${c}33;color:${c}">${e.titulo}</span>`;});
        if(evtsDia.length>MAX) html+=`<span class="cal-more">+${evtsDia.length-MAX}</span>`;
        html+=`</div></td>`;
        dia++;
      }
      cel++;
    }
    html+='</tr>';
  }
  html+='</tbody></table>';
  container.innerHTML=html;
}

function diaClick(dia,mes,ano){
  const d=`${ano}-${String(mes+1).padStart(2,'0')}-${String(dia).padStart(2,'0')}`;
  document.getElementById('evt-data').value=d;
  document.getElementById('modal-evt-titulo').textContent='Novo Evento';
  document.getElementById('evt-id').value='';
  openModal('modal-evento');
}

function renderProximosEventos(){
  const agora=new Date().toISOString().slice(0,10);
  const prox=DB.eventos.filter(e=>e.data_evento>=agora).slice(0,5);
  const el=document.getElementById('cal-proximos'); if(!el) return;
  el.innerHTML=prox.length?prox.map(e=>{
    const c=evtCor(e);
    const mins=(e.ministerios_ids||[]).map(id=>minById(id)?.nome||'').filter(Boolean).join(', ');
    return `<div style="border-left:3px solid ${c};padding:8px 12px;border-radius:0 7px 7px 0;background:${c}14;margin-bottom:8px;cursor:pointer" onclick="abrirDrawerEvento(${e.id})">
      <div style="font-size:12px;font-weight:700">${e.titulo}</div>
      <div style="font-size:11px;color:var(--muted)">${fmtData(e.data_evento)} · ${mins||'—'}</div>
    </div>`;
  }).join(''):'<div style="color:var(--muted);font-size:12px;text-align:center;padding:16px">Nenhum evento</div>';
}

function renderLegenda(){
  const el=document.getElementById('cal-legenda'); if(!el) return;
  el.innerHTML=DB.ministerios.map(m=>`<div class="cal-leg-item"><div class="cal-leg-dot" style="background:${m.cor_hex}"></div>${m.icone||''} ${m.nome}</div>`).join('');
}

function abrirDrawerEvento(id){
  const e=DB.eventos.find(x=>x.id===id); if(!e) return;
  const mins=(e.ministerios_ids||[]).map(id=>minById(id)).filter(Boolean);
  const body=`
    <div class="drawer-section">
      <div class="drawer-section-title">Detalhes do Evento</div>
      <div class="drawer-field"><div class="drawer-field-lbl">Título</div><div class="drawer-field-val">${e.titulo}</div></div>
      <div class="drawer-field"><div class="drawer-field-lbl">Data</div><div class="drawer-field-val">${fmtData(e.data_evento)}</div></div>
      <div class="drawer-field"><div class="drawer-field-lbl">Horário</div><div class="drawer-field-val">${fmtHora(e.horario)}</div></div>
      <div class="drawer-field"><div class="drawer-field-lbl">Tipo</div><div class="drawer-field-val">${e.tipo}</div></div>
      ${e.descricao?`<div class="drawer-field"><div class="drawer-field-lbl">Descrição</div><div class="drawer-field-val">${e.descricao}</div></div>`:''}
    </div>
    <div class="drawer-section">
      <div class="drawer-section-title">⛪ Ministérios <span class="drawer-count">${mins.length}</span></div>
      ${mins.map(m=>`<div class="drawer-person-row">
        <div style="width:30px;height:30px;border-radius:8px;background:${m.cor_hex}22;display:flex;align-items:center;justify-content:center;font-size:16px">${m.icone}</div>
        <div><div class="drawer-person-name">${m.nome}</div></div>
      </div>`).join('')||'<div class="drawer-empty">Nenhum ministério</div>'}
    </div>`;
  const footer=`
    <button class="btn btn-ghost" style="flex:1" onclick="editarEvento(${id})">✏️ Editar</button>
    <button class="btn btn-danger" style="flex:1" onclick="excluirEvento(${id})">🗑 Remover</button>`;
  openDrawer('📅',e.titulo,`${fmtData(e.data_evento)} · ${fmtHora(e.horario)}`,body,footer);
}

function editarEvento(id){
  const e=DB.eventos.find(x=>x.id===id); if(!e) return;
  document.getElementById('modal-evt-titulo').textContent='Editar Evento';
  document.getElementById('evt-id').value=e.id;
  document.getElementById('evt-titulo').value=e.titulo;
  document.getElementById('evt-data').value=e.data_evento;
  document.getElementById('evt-horario').value=e.horario;
  document.getElementById('evt-tipo').value=e.tipo;
  document.getElementById('evt-desc').value=e.descricao||'';
  closeDrawer();
  openModal('modal-evento');
  setTimeout(()=>{
    document.querySelectorAll('#evt-min-checks input').forEach(cb=>{
      cb.checked=(e.ministerios_ids||[]).includes(parseInt(cb.value));
    });
  },60);
}

function salvarEvento(){
  const titulo=document.getElementById('evt-titulo').value.trim();
  const data=document.getElementById('evt-data').value;
  const hora=document.getElementById('evt-horario').value;
  const tipo=document.getElementById('evt-tipo').value;
  const desc=document.getElementById('evt-desc').value.trim();
  const id=document.getElementById('evt-id').value;
  if(!titulo||!data||!hora){showToast('Título, data e horário obrigatórios','error');return;}
  const mins=Array.from(document.querySelectorAll('#evt-min-checks input:checked')).map(c=>parseInt(c.value));
  if(id){
    const idx=DB.eventos.findIndex(e=>e.id==id);
    if(idx>=0) DB.eventos[idx]={...DB.eventos[idx],titulo,data_evento:data,horario:hora,tipo,descricao:desc,ministerios_ids:mins};
    showToast('Evento atualizado!','success');
  } else {
    DB.eventos.push({id:DB.nextId.evt++,titulo,data_evento:data,horario:hora,tipo,descricao:desc,ministerios_ids:mins});
    showToast('Evento criado!','success');
  }
  closeModal('modal-evento');
  document.getElementById('evt-id').value='';
  ['evt-titulo','evt-data','evt-horario','evt-desc'].forEach(f=>document.getElementById(f).value='');
  renderCal('cal-full',false); renderCal('cal-mini',true); renderProximosEventos();
}

function excluirEvento(id){
  showConfirm('Excluir Evento','Remover este evento?',()=>{
    DB.eventos=DB.eventos.filter(e=>e.id!==id);
    closeDrawer(); showToast('Evento removido','warning');
    renderCal('cal-full',false); renderCal('cal-mini',true); renderProximosEventos();
  });
}

/* ────────────────────────────────────────────────────────────
   RELATÓRIOS
──────────────────────────────────────────────────────────── */
function carregarRelatorios(){mudarAba(abaRel);}

function mudarAba(aba){
  abaRel=aba;
  document.querySelectorAll('.rel-tab').forEach(t=>t.classList.remove('on'));
  document.querySelectorAll('.rel-panel').forEach(p=>p.classList.remove('on'));
  document.getElementById('rtab-'+aba).classList.add('on');
  document.getElementById('rpanel-'+aba).classList.add('on');
  ({geral:renderRelGeral,presenca:renderRelPresenca,equipes:renderRelEquipes,membros:renderRelMembros})[aba]?.();
}

function renderRelGeral(){
  const total=DB.escalas.length;
  const conf=DB.escalas.filter(e=>e.status==='confirmado').length;
  document.getElementById('rel-vol').textContent=DB.membros.length;
  document.getElementById('rel-esc').textContent=total;
  document.getElementById('rel-taxa').textContent=`${pct(conf,total)}%`;

  const dados=DB.ministerios.map(m=>({
    nome:m.nome,cor:m.cor_hex,
    conf:DB.escalas.filter(e=>e.ministerio_id==m.id&&e.status==='confirmado').length,
    aus:DB.escalas.filter(e=>e.ministerio_id==m.id&&e.status==='ausente').length,
    pend:DB.escalas.filter(e=>e.ministerio_id==m.id&&e.status==='pendente').length,
  }));
  const max=Math.max(...dados.map(d=>d.conf+d.aus+d.pend),1);
  document.getElementById('chart-equipes').innerHTML=dados.map(d=>`
    <div class="bar-grp">
      <div class="bar" style="height:${Math.round(d.conf/max*100)||4}%;background:var(--teal)"></div>
      <div class="bar" style="height:${Math.round(d.aus/max*100)||4}%;background:var(--red)"></div>
      <div class="bar" style="height:${Math.round(d.pend/max*100)||4}%;background:var(--yellow)"></div>
    </div>`).join('');
  document.getElementById('chart-labels').innerHTML=dados.map(d=>`<span class="bar-lbl">${d.nome}</span>`).join('');

  const porMes=Array(12).fill(0);
  DB.escalas.forEach(e=>{const d=new Date(e.data_escala+'T00:00');if(d.getFullYear()===new Date().getFullYear())porMes[d.getMonth()]++;});
  const maxM=Math.max(...porMes,1);
  const pts=porMes.map((v,i)=>`${i*(300/11)},${105-Math.round(v/maxM*90)}`);
  document.getElementById('chart-freq').innerHTML=`
    <defs><linearGradient id="aG" x1="0" y1="0" x2="0" y2="1"><stop offset="0%" stop-color="#0D9488" stop-opacity=".3"/><stop offset="100%" stop-color="#0D9488" stop-opacity="0"/></linearGradient></defs>
    <path d="M${pts[0]} L${pts.join(' L')} L${pts[11].split(',')[0]},110 L0,110 Z" fill="url(#aG)"/>
    <path d="M${pts.join(' L')}" fill="none" stroke="#0D9488" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
    ${porMes.map((v,i)=>{const[x,y]=pts[i].split(',');return`<circle cx="${x}" cy="${y}" r="3" fill="#0D9488"/>`;}).join('')}`;
}

function renderRelPresenca(){
  const total=DB.escalas.length;
  const conf=DB.escalas.filter(e=>e.status==='confirmado').length;
  const pend=DB.escalas.filter(e=>e.status==='pendente').length;
  const aus=DB.escalas.filter(e=>e.status==='ausente').length;
  document.getElementById('pc-total').textContent=total;
  document.getElementById('pc-conf').textContent=conf;
  document.getElementById('pc-pend').textContent=pend;
  document.getElementById('pc-aus').textContent=aus;
  document.getElementById('pc-conf-pct').textContent=`${pct(conf,total)}%`;
  document.getElementById('pc-pend-pct').textContent=`${pct(pend,total)}%`;
  document.getElementById('pc-aus-pct').textContent=`${pct(aus,total)}%`;

  document.getElementById('tbody-presenca').innerHTML=DB.ministerios.map(m=>{
    const escs=DB.escalas.filter(e=>e.ministerio_id==m.id);
    const tot=escs.length,c=escs.filter(e=>e.status==='confirmado').length;
    const p=escs.filter(e=>e.status==='pendente').length,a=escs.filter(e=>e.status==='ausente').length;
    const t=pct(c,tot); const cor=corTaxa(t);
    return `<tr>
      <td><span style="display:inline-flex;align-items:center;gap:6px;font-weight:700"><span style="width:9px;height:9px;border-radius:2px;background:${m.cor_hex};display:inline-block"></span>${m.icone} ${m.nome}</span></td>
      <td style="font-weight:700">${tot||'—'}</td>
      <td style="color:var(--green);font-weight:700">${c||'—'}</td>
      <td style="color:var(--yellow);font-weight:700">${p||'—'}</td>
      <td style="color:var(--red);font-weight:700">${a||'—'}</td>
      <td><div class="pbar-wrap"><div class="pbar-bg"><div class="pbar-fill" style="width:${t}%;background:${cor}"></div></div><span style="font-size:11px;font-weight:700;color:${cor};min-width:34px">${tot?t+'%':'—'}</span></div></td>
    </tr>`;
  }).join('');
}

function renderRelEquipes(){
  document.getElementById('tbody-equipes-rel').innerHTML=DB.ministerios.map(m=>{
    const sups=DB.supervisores.filter(s=>s.ministerio_id==m.id).length;
    const mems=DB.membros.filter(x=>x.ministerio_id==m.id).length;
    const escs=DB.escalas.filter(e=>e.ministerio_id==m.id).length;
    const conf=DB.escalas.filter(e=>e.ministerio_id==m.id&&e.status==='confirmado').length;
    const t=pct(conf,escs); const cor=corTaxa(t);
    return `<tr>
      <td><span style="display:inline-flex;align-items:center;gap:6px;font-weight:700"><span style="width:9px;height:9px;border-radius:2px;background:${m.cor_hex};display:inline-block"></span>${m.icone} ${m.nome}</span></td>
      <td style="font-weight:700">${sups}</td><td style="font-weight:700">${mems}</td><td style="font-weight:700">${escs}</td>
      <td><div class="pbar-wrap"><div class="pbar-bg"><div class="pbar-fill" style="width:${t}%;background:${cor}"></div></div><span style="font-size:11px;font-weight:700;color:${cor};min-width:34px">${escs?t+'%':'—'}</span></div></td>
    </tr>`;
  }).join('');
}

function renderRelMembros(){
  const busca=document.getElementById('fil-rel-mem')?.value.toLowerCase()||'';
  const lista=DB.membros.filter(m=>!busca||m.nome.toLowerCase().includes(busca));
  document.getElementById('tbody-membros-rel').innerHTML=lista.length?lista.map(m=>{
    const escs=DB.escalas.filter(e=>e.membro_id===m.id);
    const tot=escs.length,conf=escs.filter(e=>e.status==='confirmado').length,aus=escs.filter(e=>e.status==='ausente').length;
    const t=pct(conf,tot); const cor=tot?corTaxa(t):'#94A3B8';
    const mObj=minById(m.ministerio_id);
    return `<tr>
      <td><div style="display:flex;align-items:center;gap:8px">
        <div style="width:28px;height:28px;border-radius:50%;background:${m.avatar_cor||'#0D9488'};display:flex;align-items:center;justify-content:center;color:#fff;font-weight:800;font-size:11px">${m.avatar_iniciais||m.nome[0]}</div>
        <div><div style="font-weight:700;font-size:12px">${m.nome}</div><div style="font-size:10px;color:var(--muted)">${m.funcao||'—'}</div></div>
      </div></td>
      <td style="font-size:11px;color:var(--muted)">${mObj?`${mObj.icone} ${mObj.nome}`:'—'}</td>
      <td style="font-weight:700">${tot||'—'}</td>
      <td style="color:var(--green);font-weight:700">${conf||'—'}</td>
      <td style="color:var(--red);font-weight:700">${aus||'—'}</td>
      <td>${tot?`<div class="pbar-wrap"><div class="pbar-bg"><div class="pbar-fill" style="width:${t}%;background:${cor}"></div></div><span style="font-size:11px;font-weight:700;color:${cor};min-width:34px">${t}%</span></div>`:'<span style="color:var(--muted);font-size:11px">—</span>'}</td>
    </tr>`;
  }).join(''):
  `<tr><td colspan="6" style="text-align:center;color:var(--muted);padding:24px">Nenhum membro</td></tr>`;
}

function exportarRelatorio(){
  const h=['Ministério','Supervisores','Membros','Escalas','Taxa%'];
  const r=DB.ministerios.map(m=>{
    const escs=DB.escalas.filter(e=>e.ministerio_id==m.id).length;
    const conf=DB.escalas.filter(e=>e.ministerio_id==m.id&&e.status==='confirmado').length;
    return [m.nome,DB.supervisores.filter(s=>s.ministerio_id==m.id).length,DB.membros.filter(x=>x.ministerio_id==m.id).length,escs,escs?pct(conf,escs)+'%':'0%'];
  });
  const csv=[h,...r].map(row=>row.map(c=>`"${c}"`).join(',')).join('\n');
  const a=document.createElement('a');a.href=URL.createObjectURL(new Blob(['\uFEFF'+csv],{type:'text/csv;charset=utf-8;'}));a.download='relatorio.csv';a.click();
  showToast('Relatório exportado!','success');
}

/* ────────────────────────────────────────────────────────────
   NOTIFICAÇÕES
──────────────────────────────────────────────────────────── */
function toggleNotif(){
  showToast('Escalas pendentes aguardando confirmação.','info');
  document.getElementById('notif-dot').classList.add('hidden');
}

/* ────────────────────────────────────────────────────────────
   INICIALIZAÇÃO
──────────────────────────────────────────────────────────── */
(function init(){
  preencherSelects();
  carregarDashboard();
  renderCal('cal-mini',true);
})();
