<?php
// /admin/normativa_editor.php - FINAL + edición inline + Ctrl+S
declare(strict_types=1);
session_start();

function h(string $s): string {
  return htmlspecialchars($s, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

$user = (string)($_SESSION['user'] ?? $_SESSION['username'] ?? 'admin');
?>
<!doctype html>
<html lang="es">
<head>
<meta charset="utf-8"/>
<meta name="viewport" content="width=device-width, initial-scale=1"/>
<title>Editor Normativa · por filas</title>
<style>
:root{
  --az1:#003366; --az2:#004080; --n:#ff6600;
  --bg:#f8f9fa; --card:#fff; --tx:#222; --mut:#555;
  --b:rgba(0,0,0,.10); --r:14px; --link:#0066cc;
  --ok:#0a6b2b; --warn:#b85c00; --bad:#b00000;
}
*{box-sizing:border-box}
body{font-family:Arial,Helvetica,sans-serif;margin:0;background:var(--bg);color:var(--tx);}
header{background:linear-gradient(to bottom,var(--az1),var(--az2));color:#fff;padding:18px 16px;}
.head{max-width:1400px;margin:0 auto;display:flex;gap:12px;align-items:center;justify-content:space-between;flex-wrap:wrap;}
h1{margin:0;font-size:1.3em}
.sub{opacity:.92;margin:6px 0 0}
.nav a{text-decoration:none;color:#fff;background:rgba(255,255,255,.12);padding:10px 12px;border-radius:999px;border:1px solid rgba(255,255,255,.20);font-weight:bold;display:inline-block;margin-left:8px;}
.nav a:hover{background:rgba(255,255,255,.20);}
.wrap{max-width:1400px;margin:18px auto;padding:0 16px 60px;}

.row{display:flex;gap:12px;align-items:center;flex-wrap:wrap;margin:12px 0;}
.tool{background:#fff;border:1px solid var(--b);border-radius:999px;padding:10px 12px;display:flex;gap:10px;align-items:center;box-shadow:0 2px 10px rgba(0,0,0,.04);}
.tool label{font-weight:bold;color:var(--az1);white-space:nowrap;font-size:.95em;}
.tool input,.tool select{border:0;outline:0;background:transparent;font-size:1em;min-width:210px;}
.tool select{min-width:165px;cursor:pointer}

.btn{border:0;border-radius:999px;padding:10px 14px;cursor:pointer;font-weight:bold;background:var(--az2);color:#fff;box-shadow:0 4px 12px rgba(0,0,0,.14);transition:.15s;}
.btn:hover{background:#0066cc;transform:translateY(-1px);}
.btn:disabled{opacity:.6;cursor:not-allowed;transform:none;}
.btn.alt{background:#e9eef6;color:var(--az1);box-shadow:none;border:1px solid rgba(0,0,0,.08);}
.btn.alt:hover{background:#dde7f7;}
.btn.danger{background:#8b0000;color:#fff;}
.btn.danger:hover{background:#b00000;}
.btn-small{padding:8px 10px;font-size:.92em;}

.card{background:var(--card);border:1px solid rgba(0,0,0,.08);border-radius:var(--r);box-shadow:0 6px 16px rgba(0,0,0,.06);padding:14px;}
.section-title{margin:6px 0 10px;color:var(--az1);border-left:4px solid var(--n);padding-left:10px;}
.hint{color:var(--mut);margin:4px 0 10px;font-size:.95em;}
kbd{background:#eef3ff;border:1px solid rgba(0,0,0,.15);border-bottom-width:2px;border-radius:6px;padding:2px 6px;font-family:ui-monospace,Menlo,Consolas,monospace;font-size:.9em}

.table-wrap{overflow:auto;background:#fff;border:1px solid rgba(0,0,0,.08);border-radius:12px;box-shadow:0 6px 16px rgba(0,0,0,.06);}
table{width:100%;border-collapse:collapse;min-width:1500px;}
thead th{position:sticky;top:0;z-index:2;background:linear-gradient(to bottom,#eaf1ff,#dbe8ff);color:var(--az1);text-align:left;padding:12px;border-bottom:2px solid rgba(0,0,0,.08);font-size:.95em;}
tbody td{padding:12px;border-bottom:1px solid rgba(0,0,0,.06);vertical-align:top;font-size:.98em;}
tbody tr:hover{background:#f6f9ff;}
.mono{font-family:ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, "Liberation Mono", monospace;font-size:.93em;}

.chip{display:inline-flex;align-items:center;gap:8px;font-weight:bold;color:var(--az1);background:#eaf1ff;border:1px solid rgba(0,51,102,.12);padding:4px 10px;border-radius:999px;white-space:nowrap;}

.tags{display:flex;gap:6px;flex-wrap:wrap;}
.tag{display:inline-flex;align-items:center;font-weight:bold;color:#234;background:#eef3ff;border:1px solid rgba(0,51,102,.12);padding:3px 8px;border-radius:999px;font-size:.85em;}

a.link{color:var(--link);font-weight:bold;text-decoration:none;}
a.link:hover{text-decoration:underline;color:var(--az2);}

.pager{display:flex;gap:10px;align-items:center;flex-wrap:wrap;margin:12px 0;}
.small{color:var(--mut);font-size:.92em;}

.msg{margin:10px 0;padding:12px;border-radius:12px;border:1px solid rgba(0,0,0,.10);background:#fff;display:none;}
.msg.ok{background:#eefaf0;border-color:rgba(10,107,43,.25);color:var(--ok);}
.msg.err{background:#fff3f3;border-color:rgba(180,0,0,.2);color:#7a0000;}

.inline{cursor:text; border-radius:8px; padding:4px 6px; display:inline-block; min-width:20px;}
.inline:hover{background:#f0f6ff;}
.inline.editing{background:#fff7e8; outline:2px solid rgba(255,102,0,.35);}
.inline.saving{background:#eefaf0; outline:2px solid rgba(10,107,43,.25);}
.inline.error{background:#fff3f3; outline:2px solid rgba(180,0,0,.25);}

.inline input, .inline textarea, .inline select{
  width:100%;
  border:1px solid rgba(0,0,0,.18);
  border-radius:10px;
  padding:8px 10px;
  font-size:1em;
  outline:none;
  background:#fff;
}
.inline textarea{min-height:90px; resize:vertical}

.row-saving{opacity:.75; pointer-events:none;}
.row-saving .inline{pointer-events:auto;} /* permite editar el campo actual si querés */

@media (max-width:980px){
  table{min-width:1300px;}
  .tool input{min-width:160px;}
}

/* Modal */
.modal-backdrop{position:fixed;inset:0;background:rgba(0,0,0,.45);display:none;align-items:center;justify-content:center;z-index:9999;padding:18px;}
.modal{width:min(980px, 96vw);background:#fff;border-radius:16px;box-shadow:0 18px 55px rgba(0,0,0,.28);border:1px solid rgba(0,0,0,.12);overflow:hidden;}
.modal-head{padding:14px 16px;background:linear-gradient(to bottom,#f4f7ff,#eaf1ff);border-bottom:1px solid rgba(0,0,0,.08);display:flex;align-items:center;justify-content:space-between;gap:10px;}
.modal-head h3{margin:0;color:var(--az1);font-size:1.1em;}
.modal-body{padding:14px 16px;}
.grid2{display:grid;grid-template-columns:1fr 1fr;gap:12px;}
.field{display:flex;flex-direction:column;gap:6px;}
.field label{font-weight:bold;color:var(--az1);font-size:.95em;}
.field input,.field textarea,.field select{border:1px solid rgba(0,0,0,.14);border-radius:12px;padding:10px 12px;font-size:1em;outline:none;}
textarea{min-height:110px;resize:vertical}
.modal-foot{padding:14px 16px;border-top:1px solid rgba(0,0,0,.08);display:flex;gap:10px;justify-content:flex-end;flex-wrap:wrap;}
.links-box{border:1px solid rgba(0,0,0,.12);border-radius:12px;padding:10px;background:#fafcff;}
.link-row{display:grid;grid-template-columns:1.3fr 1.2fr .5fr auto;gap:8px;align-items:center;margin:8px 0;}

/* UI enhancements */
.card{transition:box-shadow .2s ease}
.card:hover{box-shadow:0 8px 28px rgba(0,0,0,.09)}
html{scroll-behavior:smooth}
::-webkit-scrollbar{width:8px;height:8px}
::-webkit-scrollbar-track{background:#f1f1f1;border-radius:4px}
::-webkit-scrollbar-thumb{background:#b0bec5;border-radius:4px}
::-webkit-scrollbar-thumb:hover{background:#90a4ae}
</style>
</head>
<body>

<header>
  <div class="head">
    <div>
      <h1>Editor de Normativa · por filas</h1>
      <div class="sub">Usuario: <strong><?= h($user) ?></strong> · API: normativa_api.php</div>
    </div>
    <div class="nav">
    <a href="/admin/index.php">Tablero</a>
      <a href="../historia.html" target="_blank" rel="noopener">Historia</a>
      <a href="logout.php">Salir</a>
    </div>
  </div>
</header>

<div class="wrap">
  <div id="msg" class="msg"></div>

  <div class="card">
    <h2 class="section-title">Listado</h2>
    <div class="hint">
      Doble click para editar en línea. Guardado automático al salir del campo o con <kbd>Enter</kbd>.
      Guardar manual con <kbd>Ctrl</kbd>+<kbd>S</kbd>. <span class="small">(Para links y detalle: botón “Editar” abre modal.)</span>
    </div>

    <div class="row">
      <div class="tool"><label>🔎 Buscar</label><input id="q" placeholder="título, provincia, tags, descripción..."></div>
      <div class="tool"><label>🗺️ Provincia</label><select id="prov"></select></div>
      <div class="tool"><label>🏷️ Tipo</label><select id="tipo"></select></div>
      <div class="tool"><label>🧩 Origen</label><select id="origen"></select></div>
      <div class="tool"><label>↕️ Orden</label>
        <select id="sort">
          <option value="fecha_desc">Fecha ↓</option>
          <option value="fecha_asc">Fecha ↑</option>
        </select>
      </div>
      <div class="tool"><label>📄</label>
        <select id="per">
          <option value="25">25</option>
          <option value="50" selected>50</option>
          <option value="100">100</option>
        </select>
      </div>
      <button class="btn alt" id="clear">Limpiar</button>
      <button class="btn" id="newItem">+ Nuevo</button>
    </div>

    <div class="pager">
      <button class="btn alt" id="prev">←</button>
      <div class="mono" id="pageInfo">Página —</div>
      <button class="btn alt" id="next">→</button>
      <span class="small" id="countInfo"></span>
    </div>

    <div class="table-wrap">
      <table>
        <thead>
          <tr>
            <th style="width:120px">Fecha</th>
            <th style="width:160px">Provincia</th>
            <th style="width:160px">Tipo</th>
            <th style="width:80px">Origen</th>
            <th style="width:360px">Título</th>
            <th style="width:260px">Tags</th>
            <th style="width:420px">Descripción</th>
            <th style="width:90px">Links</th>
            <th style="width:150px">Acciones</th>
          </tr>
        </thead>
        <tbody id="tbody"></tbody>
      </table>
    </div>
  </div>
</div>

<!-- MODAL -->
<div id="modalBackdrop" class="modal-backdrop" aria-hidden="true">
  <div class="modal" role="dialog" aria-modal="true">
    <div class="modal-head">
      <h3 id="modalTitle">Editar entrada</h3>
      <button class="btn alt btn-small" id="modalClose">Cerrar</button>
    </div>

    <div class="modal-body">
      <div class="grid2">
        <div class="field">
          <label>Fecha (YYYY-MM-DD)</label>
          <input id="fFecha" placeholder="2026-01-28">
        </div>
        <div class="field">
          <label>Etiqueta fecha</label>
          <input id="fFechaLabel" placeholder="Enero 2026">
        </div>

        <div class="field">
          <label>Provincia</label>
          <select id="fProv"></select>
        </div>

        <div class="field">
          <label>Tipo</label>
          <select id="fTipo"></select>
        </div>

        <div class="field" id="fTipoNewWrap" style="display:none;">
          <label>Nuevo tipo</label>
          <input id="fTipoNew" placeholder="Ej: Resolución">
        </div>

        <div class="field">
          <label>Origen</label>
          <select id="fOrigen">
            <option value="LT">LT</option>
            <option value="LP">LP</option>
            <option value="PP">PP</option>
          </select>
        </div>
      </div>

      <div class="field" style="margin-top:16px;">
        <label>Título</label>
        <input id="fTitulo" placeholder="Título de la norma / noticia">
      </div>

      <div class="field" style="margin-top:16px;">
        <label>Descripción</label>
        <textarea id="fDesc" placeholder="Detalle / resumen..."></textarea>
      </div>

      <div class="field" style="margin-top:16px;">
        <label>Tags (coma)</label>
        <input id="fTags" placeholder="incendios, evacuación, ley 19587">
      </div>

      <div class="field" style="margin-top:16px;">
        <label>Links</label>
        <div class="links-box" id="linksBox"></div>
        <button class="btn alt btn-small" id="addLink">+ Agregar link</button>
      </div>
    </div>

    <div class="modal-foot">
      <button class="btn alt" id="mDuplicate">Duplicar</button>
      <button class="btn danger" id="mDelete">Borrar</button>
      <button class="btn" id="mSave">Guardar</button>
    </div>
  </div>
</div>

<script>
const API = 'normativa_api.php';

let csrfToken = '';
let state = {
  page: 1,
  per: 50,
  items: [],
  options: { provincias: [], tipos: [], origenes: ['LT','LP','PP'] },
  meta: {},
  paging: { page:1, pages:1, total:0, per_page:50 }
};
let editing = null;
let saving = false;

// editor inline activo (para Ctrl+S)
let inlineActive = null; // { commit(), cancel() }

function esc(s){
  s = (s ?? '').toString();
  return s.replaceAll('&','&amp;').replaceAll('<','&lt;').replaceAll('>','&gt;')
          .replaceAll('"','&quot;').replaceAll("'","&#039;");
}
function showMsg(text, type='ok'){
  const el = document.getElementById('msg');
  el.textContent = text;
  el.className = 'msg ' + (type === 'ok' ? 'ok' : 'err');
  el.style.display = 'block';
  clearTimeout(showMsg._t);
  showMsg._t = setTimeout(() => el.style.display = 'none', 4500);
}

async function apiGet(paramsObj){
  const qs = new URLSearchParams(paramsObj).toString();
  const url = `${API}?${qs}`;
  const res = await fetch(url, { headers: { 'X-CSRF-Token': csrfToken } });
  const txt = await res.text();
  try {
    const json = JSON.parse(txt);
    if (!json.ok) throw new Error(json.error || 'Error API');
    return json;
  } catch(e){
    throw new Error('Respuesta no JSON desde API. Inicio del body: ' + txt.slice(0, 80));
  }
}

async function apiPost(action, body){
  const url = `${API}?action=${encodeURIComponent(action)}`;
  const res = await fetch(url, {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json',
      'X-CSRF-Token': csrfToken
    },
    body: JSON.stringify(body ?? {})
  });
  const txt = await res.text();
  try {
    const json = JSON.parse(txt);
    if (!json.ok) throw new Error(json.error || 'Error API');
    return json;
  } catch(e){
    throw new Error('Respuesta no JSON desde API. Inicio del body: ' + txt.slice(0, 80));
  }
}

async function initCsrf(){
  const res = await apiGet({ action: 'csrf' });
  csrfToken = res.csrf;
}

function buildFilters(){
  const provSel = document.getElementById('prov');
  const tipoSel = document.getElementById('tipo');
  const oriSel  = document.getElementById('origen');

  const provs = state.options.provincias || [];
  const tipos = state.options.tipos || [];
  const origenes = state.options.origenes || ['LT','LP','PP'];

  provSel.innerHTML = `<option value="">Todas</option>` + provs.map(p => `<option value="${esc(p)}">${esc(p)}</option>`).join('');
  tipoSel.innerHTML = `<option value="">Todos</option>` + tipos.map(t => `<option value="${esc(t)}">${esc(t)}</option>`).join('');
  oriSel.innerHTML  = `<option value="">Todos</option>` + origenes.map(o => `<option value="${esc(o)}">${esc(o)}</option>`).join('');
}

function setPager(){
  const p = state.paging;
  document.getElementById('pageInfo').textContent = `Página ${p.page} / ${p.pages}`;
  document.getElementById('countInfo').textContent = `${p.total} registros`;
  document.getElementById('prev').disabled = (p.page <= 1);
  document.getElementById('next').disabled = (p.page >= p.pages);
}

function shortText(s, max=240){
  s = (s ?? '').toString().trim();
  if (s.length <= max) return s;
  return s.slice(0, max-1) + '…';
}

function renderTags(tags){
  if (!Array.isArray(tags) || tags.length === 0) return '—';
  return `<div class="tags">` + tags.slice(0, 12).map(t => `<span class="tag">${esc(t)}</span>`).join('') + `</div>`;
}

function getItemById(id){
  return state.items.find(x => (x.id || '') === id) || null;
}

// ===== Validación fecha =====
function isValidDateYYYYMMDD(s){
  s = (s || '').trim();
  if (s === '') return true;
  if (!/^\d{4}-\d{2}-\d{2}$/.test(s)) return false;
  const [y,m,d] = s.split('-').map(n => parseInt(n,10));
  const dt = new Date(Date.UTC(y, m-1, d));
  return dt.getUTCFullYear() === y && (dt.getUTCMonth()+1) === m && dt.getUTCDate() === d;
}

// ===== Guardando row =====
function setRowSaving(tr, on){
  if (!tr) return;
  if (on) tr.classList.add('row-saving');
  else tr.classList.remove('row-saving');
}

// ===== Guardar item (reusa API) =====
async function saveItem(item){
  // validación suave
  if (!isValidDateYYYYMMDD(item.fecha || '')) {
    throw new Error('Fecha inválida. Usá YYYY-MM-DD (ej: 2026-02-07).');
  }
  const res = await apiPost('save_item', item);
  return res.item;
}

// ===== Render tabla con celdas inline =====
function renderTable(){
  const tbody = document.getElementById('tbody');
  tbody.innerHTML = '';

  state.items.forEach(it => {
    const firstLink = it.links?.[0]?.href || '';
    const linkCell = firstLink
      ? `<a class="link" href="${esc(firstLink)}" target="_blank" rel="noopener">Abrir</a>`
      : '—';

    const tr = document.createElement('tr');
    tr.dataset.id = it.id;

    tr.innerHTML = `
      <td class="mono">
        <span class="inline" data-field="fecha" data-type="text">${esc(it.fecha || '—')}</span>
      </td>

      <td>
        <span class="inline chip" data-field="provincia" data-type="select-prov">${esc(it.provincia || 'NACIONAL')}</span>
      </td>

      <td>
        <span class="inline" data-field="tipo" data-type="select-tipo">${esc(it.tipo || '—')}</span>
      </td>

      <td class="mono">
        <span class="inline" data-field="origen" data-type="select-origen">${esc(it.origen || '—')}</span>
      </td>

      <td>
        <span class="inline" data-field="titulo" data-type="text">${esc(it.titulo || '(sin título)')}</span>
      </td>

      <td>
        <span class="inline" data-field="tags" data-type="tags">${renderTags(it.tags)}</span>
      </td>

      <td>
        <span class="inline" data-field="descripcion" data-type="textarea">${esc(shortText(it.descripcion || '', 300))}</span>
      </td>

      <td>${linkCell}</td>

      <td>
        <button class="btn alt btn-small" data-act="edit" data-id="${esc(it.id)}">Editar</button>
      </td>
    `;

    tbody.appendChild(tr);
  });
}

async function loadList(){
  state.per = parseInt(document.getElementById('per').value, 10) || 50;

  const data = await apiGet({
    action: 'list',
    page: state.page,
    per_page: state.per,
    q: document.getElementById('q').value.trim(),
    prov: document.getElementById('prov').value,
    tipo: document.getElementById('tipo').value,
    origen: document.getElementById('origen').value,
    sort: document.getElementById('sort').value
  });

  state.meta = data.meta || {};
  state.options = data.options || state.options;
  state.items = data.items || [];
  state.paging = data.paging || { page: state.page, pages: 1, total: state.items.length, per_page: state.per };

  buildFilters();
  setPager();
  renderTable();
}

// ===== Modal (mantengo como antes) =====
function openModal(item, modeTitle){
  editing = item || { id:'', fecha:'', fecha_label:'', provincia:'NACIONAL', tipo:'Otro', origen:'LT', titulo:'', descripcion:'', tags:[], links:[] };

  document.getElementById('modalTitle').textContent = modeTitle || (editing.id ? 'Editar entrada' : 'Nueva entrada');

  const provs = ['NACIONAL', ...(state.options.provincias || [])].filter((v,i,a)=>a.indexOf(v)===i);
  const tipos = [...(state.options.tipos || [])];
  if (!tipos.includes('Otro')) tipos.push('Otro');

  const fProv = document.getElementById('fProv');
  fProv.innerHTML = provs.map(p => `<option value="${esc(p)}">${esc(p)}</option>`).join('');

  const fTipo = document.getElementById('fTipo');
  fTipo.innerHTML = tipos
    .filter((v,i,a)=>a.indexOf(v)===i)
    .sort()
    .map(t => `<option value="${esc(t)}">${esc(t)}</option>`).join('')
    + `<option value="__NEW__">+ Nuevo tipo…</option>`;

  document.getElementById('fFecha').value = editing.fecha || '';
  document.getElementById('fFechaLabel').value = editing.fecha_label || '';
  document.getElementById('fProv').value = editing.provincia || 'NACIONAL';

  const currentTipo = (editing.tipo || 'Otro').toString();
  const tipoInList = Array.from(fTipo.options).some(o => o.value === currentTipo);
  if (tipoInList) {
    fTipo.value = currentTipo;
    document.getElementById('fTipoNewWrap').style.display = 'none';
    document.getElementById('fTipoNew').value = '';
  } else {
    fTipo.value = '__NEW__';
    document.getElementById('fTipoNewWrap').style.display = 'block';
    document.getElementById('fTipoNew').value = currentTipo;
  }

  document.getElementById('fOrigen').value = (editing.origen || 'LT');
  document.getElementById('fTitulo').value = editing.titulo || '';
  document.getElementById('fDesc').value = editing.descripcion || '';
  document.getElementById('fTags').value = Array.isArray(editing.tags) ? editing.tags.join(', ') : (editing.tags || '');

  renderLinks(editing.links || []);
  document.getElementById('mDelete').style.display = editing.id ? 'inline-block' : 'none';

  document.getElementById('modalBackdrop').style.display = 'flex';
  document.getElementById('modalBackdrop').setAttribute('aria-hidden','false');
}

function closeModal(){
  if (saving) return;
  document.getElementById('modalBackdrop').style.display = 'none';
  document.getElementById('modalBackdrop').setAttribute('aria-hidden','true');
  editing = null;
}

function renderLinks(links){
  const box = document.getElementById('linksBox');
  box.innerHTML = '';

  const arr = Array.isArray(links) ? links : [];
  if (arr.length === 0) {
    box.innerHTML = `<div class="small">Sin links. Podés agregar uno (InfoLeg / BO / PDF / web).</div>`;
    return;
  }

  arr.forEach((l, idx) => {
    const row = document.createElement('div');
    row.className = 'link-row';
    row.innerHTML = `
      <input data-k="href"  data-i="${idx}" placeholder="https://..." value="${esc(l.href || '')}">
      <input data-k="label" data-i="${idx}" placeholder="Ver" value="${esc(l.label || 'Ver')}">
      <input data-k="icon"  data-i="${idx}" placeholder="📖" value="${esc(l.icon || '📖')}">
      <button class="btn alt btn-small" data-act="rmLink" data-i="${idx}">Quitar</button>
    `;
    box.appendChild(row);
  });
}

function collectLinks(){
  const box = document.getElementById('linksBox');
  const hrefs = box.querySelectorAll('input[data-k="href"]');
  const out = [];
  hrefs.forEach(inp => {
    const idx = parseInt(inp.dataset.i, 10);
    const href = (inp.value || '').trim();
    if (!href) return;
    const label = (box.querySelector(`input[data-k="label"][data-i="${idx}"]`)?.value || 'Ver').trim();
    const icon  = (box.querySelector(`input[data-k="icon"][data-i="${idx}"]`)?.value || '📖').trim();
    out.push({ href, label, icon });
  });
  return out;
}

function setSaving(on){
  saving = on;
  const saveBtn = document.getElementById('mSave');
  const delBtn  = document.getElementById('mDelete');
  const dupBtn  = document.getElementById('mDuplicate');
  const closeBtn= document.getElementById('modalClose');
  const addLink = document.getElementById('addLink');

  saveBtn.disabled = on;
  delBtn.disabled = on;
  dupBtn.disabled = on;
  closeBtn.disabled = on;
  addLink.disabled = on;

  saveBtn.textContent = on ? 'Guardando…' : 'Guardar';
}

async function saveCurrent(){
  const fecha = document.getElementById('fFecha').value.trim();
  if (!isValidDateYYYYMMDD(fecha)){
    showMsg('Fecha inválida. Usá formato YYYY-MM-DD (ej: 2026-02-07).', 'err');
    document.getElementById('fFecha').focus();
    return;
  }

  const tipoSel = document.getElementById('fTipo').value;
  let tipoFinal = tipoSel;
  if (tipoSel === '__NEW__') {
    tipoFinal = document.getElementById('fTipoNew').value.trim();
    if (!tipoFinal) {
      showMsg('Poné el “Nuevo tipo” o elegí uno existente.', 'err');
      document.getElementById('fTipoNew').focus();
      return;
    }
  }

  const tagsRaw = document.getElementById('fTags').value || '';
  const tags = tagsRaw.split(',').map(s => s.trim()).filter(Boolean);

  const payload = {
    id: editing?.id || '',
    fecha,
    fecha_label: document.getElementById('fFechaLabel').value.trim(),
    provincia: document.getElementById('fProv').value,
    tipo: tipoFinal,
    origen: document.getElementById('fOrigen').value,
    titulo: document.getElementById('fTitulo').value.trim(),
    descripcion: document.getElementById('fDesc').value.trim(),
    tags,
    links: collectLinks()
  };

  setSaving(true);
  try {
    await apiPost('save_item', payload);
    showMsg('Guardado OK.', 'ok');
    closeModal();
    await loadList();
  } catch (e) {
    showMsg(e.message || 'Error guardando', 'err');
  } finally {
    setSaving(false);
  }
}

async function deleteCurrent(){
  if (!editing?.id) return;
  if (!confirm('¿Borrar esta entrada?')) return;

  setSaving(true);
  try {
    await apiPost('delete_item', { id: editing.id });
    showMsg('Borrado OK.', 'ok');
    closeModal();
    await loadList();
  } catch(e){
    showMsg(e.message || 'Error borrando', 'err');
  } finally {
    setSaving(false);
  }
}

function duplicateCurrent(){
  const clone = JSON.parse(JSON.stringify(editing || {}));
  clone.id = '';
  openModal(clone, 'Duplicar (nuevo ítem)');
}

// ===== Inline editing =====
function startInlineEdit(span){
  if (!span) return;
  const tr = span.closest('tr');
  const id = tr?.dataset?.id || '';
  const it = getItemById(id);
  if (!it) return;

  // si hay otro editor inline activo -> commit antes
  if (inlineActive?.commit) {
    inlineActive.commit(true);
  }

  const field = span.dataset.field;
  const type  = span.dataset.type;

  span.classList.add('editing');

  const prevHtml = span.innerHTML;
  const prevText = span.textContent;

  function cleanup(){
    span.classList.remove('editing','saving','error');
    inlineActive = null;
  }

  function setDisplayFromItem(){
    if (field === 'tags') {
      span.innerHTML = renderTags(it.tags);
    } else if (field === 'descripcion') {
      span.textContent = shortText(it.descripcion || '', 300);
    } else if (field === 'provincia') {
      span.classList.add('chip');
      span.textContent = it.provincia || 'NACIONAL';
    } else {
      span.textContent = (it[field] ?? '—').toString() || '—';
    }
  }

  function cancel(){
    span.innerHTML = prevHtml;
    cleanup();
  }

  async function commit(force=false){
    // si todavía está mostrando el editor, levantamos el valor
    try {
      const editor = span.querySelector('input, textarea, select');
      if (!editor) { cleanup(); return; }

      let newVal = (editor.value ?? '').toString().trim();

      // normalizaciones por campo
      if (field === 'fecha') {
        if (newVal === '—') newVal = '';
        if (!isValidDateYYYYMMDD(newVal)) {
          span.classList.add('error');
          showMsg('Fecha inválida. Usá YYYY-MM-DD (ej: 2026-02-07).', 'err');
          editor.focus();
          return;
        }
        it.fecha = newVal;
      } else if (field === 'provincia') {
        it.provincia = (newVal || 'NACIONAL').toUpperCase();
      } else if (field === 'tipo') {
        it.tipo = newVal || 'Otro';
      } else if (field === 'origen') {
        it.origen = (newVal || 'LT').toUpperCase();
      } else if (field === 'titulo') {
        it.titulo = newVal || '(sin título)';
      } else if (field === 'descripcion') {
        it.descripcion = newVal;
      } else if (field === 'tags') {
        const tags = newVal.split(',').map(s => s.trim()).filter(Boolean);
        it.tags = Array.from(new Set(tags));
      }

      // guardado
      span.classList.remove('error');
      span.classList.add('saving');
      setRowSaving(tr, true);

      // dejo visual "optimista"
      setDisplayFromItem();

      // guardo en backend
      const saved = await saveItem(it);
      // refresco item local con respuesta (por si el backend normaliza)
      Object.assign(it, saved);

      span.classList.remove('saving');
      setRowSaving(tr, false);
      setDisplayFromItem();
      cleanup();
      showMsg('Guardado.', 'ok');
    } catch(e){
      span.classList.remove('saving');
      setRowSaving(tr, false);
      span.classList.add('error');
      showMsg(e.message || 'Error guardando', 'err');
      // vuelvo a modo edición si es posible
      if (!force) return;
      cleanup();
    }
  }

  // armo editor según type
  if (type === 'textarea') {
    span.innerHTML = `<textarea>${esc(field==='descripcion' ? (it.descripcion || '') : prevText)}</textarea>`;
    const ta = span.querySelector('textarea');
    ta.focus();
    ta.setSelectionRange(ta.value.length, ta.value.length);

    ta.addEventListener('keydown', (e) => {
      if (e.key === 'Escape') { e.preventDefault(); cancel(); }
      // Enter con Ctrl (para no cortar líneas) -> guardar
      if (e.key === 'Enter' && (e.ctrlKey || e.metaKey)) { e.preventDefault(); commit(); }
    });
    ta.addEventListener('blur', () => commit());
  }
  else if (type === 'tags') {
    const tagsStr = Array.isArray(it.tags) ? it.tags.join(', ') : '';
    span.innerHTML = `<input type="text" value="${esc(tagsStr)}" placeholder="tag1, tag2">`;
    const inp = span.querySelector('input');
    inp.focus();
    inp.select();
    inp.addEventListener('keydown', (e) => {
      if (e.key === 'Escape') { e.preventDefault(); cancel(); }
      if (e.key === 'Enter') { e.preventDefault(); commit(); }
    });
    inp.addEventListener('blur', () => commit());
  }
  else if (type === 'select-prov') {
    const provs = ['NACIONAL', ...(state.options.provincias || [])].filter((v,i,a)=>a.indexOf(v)===i);
    span.innerHTML = `<select>${provs.map(p=>`<option value="${esc(p)}">${esc(p)}</option>`).join('')}</select>`;
    const sel = span.querySelector('select');
    sel.value = (it.provincia || 'NACIONAL').toUpperCase();
    sel.focus();
    sel.addEventListener('keydown', (e) => {
      if (e.key === 'Escape') { e.preventDefault(); cancel(); }
      if (e.key === 'Enter') { e.preventDefault(); commit(); }
    });
    sel.addEventListener('change', ()=> commit());
    sel.addEventListener('blur', ()=> commit());
  }
  else if (type === 'select-tipo') {
    const tipos = [...(state.options.tipos || [])];
    if (!tipos.includes('Otro')) tipos.push('Otro');
    tipos.sort();
    span.innerHTML = `<select>${tipos.map(t=>`<option value="${esc(t)}">${esc(t)}</option>`).join('')}</select>`;
    const sel = span.querySelector('select');
    sel.value = it.tipo || 'Otro';
    sel.focus();
    sel.addEventListener('keydown', (e) => {
      if (e.key === 'Escape') { e.preventDefault(); cancel(); }
      if (e.key === 'Enter') { e.preventDefault(); commit(); }
    });
    sel.addEventListener('change', ()=> commit());
    sel.addEventListener('blur', ()=> commit());
  }
  else if (type === 'select-origen') {
    const origenes = (state.options.origenes || ['LT','LP','PP']).map(x=>x.toUpperCase());
    span.innerHTML = `<select>${origenes.map(o=>`<option value="${esc(o)}">${esc(o)}</option>`).join('')}</select>`;
    const sel = span.querySelector('select');
    sel.value = (it.origen || 'LT').toUpperCase();
    sel.focus();
    sel.addEventListener('keydown', (e) => {
      if (e.key === 'Escape') { e.preventDefault(); cancel(); }
      if (e.key === 'Enter') { e.preventDefault(); commit(); }
    });
    sel.addEventListener('change', ()=> commit());
    sel.addEventListener('blur', ()=> commit());
  }
  else {
    // text común
    const cur = (field==='fecha' ? (it.fecha||'') : (it[field] ?? '').toString());
    span.innerHTML = `<input type="text" value="${esc(cur)}">`;
    const inp = span.querySelector('input');
    inp.focus();
    inp.select();
    inp.addEventListener('keydown', (e) => {
      if (e.key === 'Escape') { e.preventDefault(); cancel(); }
      if (e.key === 'Enter') { e.preventDefault(); commit(); }
    });
    inp.addEventListener('blur', () => commit());
  }

  inlineActive = { commit, cancel };
}

// ===== EVENTS =====

// doble click para editar inline (delegación)
document.getElementById('tbody').addEventListener('dblclick', (e) => {
  const span = e.target.closest('.inline');
  if (!span) return;
  startInlineEdit(span);
});

// botón editar -> modal
document.getElementById('tbody').addEventListener('click', (e) => {
  const btn = e.target.closest('button[data-act="edit"]');
  if (!btn) return;
  const id = btn.dataset.id;
  const it = getItemById(id);
  if (!it) return;
  openModal(it, 'Editar entrada');
});

// modal events
document.getElementById('newItem').onclick = () => openModal(null, 'Nueva entrada');
document.getElementById('modalClose').onclick = closeModal;
document.getElementById('mSave').onclick = saveCurrent;
document.getElementById('mDelete').onclick = deleteCurrent;
document.getElementById('mDuplicate').onclick = duplicateCurrent;

document.getElementById('addLink').onclick = () => {
  const links = Array.isArray(editing?.links) ? editing.links : [];
  links.push({ href:'', label:'Ver', icon:'📖' });
  editing.links = links;
  renderLinks(links);
};

document.getElementById('linksBox').addEventListener('click', (e) => {
  const btn = e.target.closest('button[data-act="rmLink"]');
  if (!btn) return;
  const i = parseInt(btn.dataset.i, 10);
  const links = Array.isArray(editing?.links) ? editing.links : [];
  links.splice(i, 1);
  editing.links = links;
  renderLinks(links);
});

document.getElementById('fTipo').addEventListener('change', () => {
  const v = document.getElementById('fTipo').value;
  document.getElementById('fTipoNewWrap').style.display = (v === '__NEW__') ? 'block' : 'none';
  if (v !== '__NEW__') document.getElementById('fTipoNew').value = '';
});

// paginado
document.getElementById('prev').onclick = async () => { if (state.page > 1) { state.page--; await loadList(); } };
document.getElementById('next').onclick = async () => { if (state.page < state.paging.pages) { state.page++; await loadList(); } };

// filtros (debounce)
let tDeb = null;
function debouncedReload(){
  clearTimeout(tDeb);
  tDeb = setTimeout(async () => {
    state.page = 1;
    await loadList();
  }, 350);
}
['q','prov','tipo','origen','sort','per'].forEach(id => {
  const el = document.getElementById(id);
  if (!el) return;
  el.addEventListener(id === 'q' ? 'input' : 'change', debouncedReload);
});

document.getElementById('clear').onclick = async () => {
  document.getElementById('q').value = '';
  document.getElementById('prov').value = '';
  document.getElementById('tipo').value = '';
  document.getElementById('origen').value = '';
  document.getElementById('sort').value = 'fecha_desc';
  document.getElementById('per').value = '50';
  state.page = 1;
  await loadList();
};

// Ctrl+S (inline o modal)
document.addEventListener('keydown', (e) => {
  const key = (e.key || '').toLowerCase();
  if ((e.ctrlKey || e.metaKey) && key === 's') {
    e.preventDefault();

    // si hay editor inline activo, commit
    if (inlineActive?.commit) {
      inlineActive.commit();
      return;
    }

    // si modal abierto, guardar
    const modalOpen = document.getElementById('modalBackdrop').style.display === 'flex';
    if (modalOpen) {
      saveCurrent();
      return;
    }

    showMsg('No hay nada para guardar (no hay edición activa).', 'ok');
  }

  // Escape cierra modal si está abierto y no está guardando
  if (key === 'escape') {
    const modalOpen = document.getElementById('modalBackdrop').style.display === 'flex';
    if (modalOpen) closeModal();
  }
});

// Init
(async () => {
  try {
    await initCsrf();
    await loadList();
  } catch(e){
    showMsg(e.message || 'Error iniciando editor', 'err');
  }
})();
</script>

</body>
</html>