<?php
// admin/directorio_editor.php - Editor por filas (Directorio) con Provincia/Municipio/Localidad
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
<title>Editor Directorio (por filas)</title>
<style>
:root{
  --az1:#003366; --az2:#004080; --n:#ff6600;
  --bg:#f8f9fa; --card:#fff; --tx:#222; --mut:#555;
  --b:rgba(0,0,0,.10); --r:14px; --link:#0066cc;
  --ok:#0a6b2b; --bad:#b00000;
}
*{box-sizing:border-box}
body{font-family:Arial,Helvetica,sans-serif;margin:0;background:var(--bg);color:var(--tx);}
header{background:linear-gradient(to bottom,var(--az1),var(--az2));color:#fff;padding:18px 16px;}
.head{max-width:1200px;margin:0 auto;display:flex;gap:12px;align-items:center;justify-content:space-between;flex-wrap:wrap;}
h1{margin:0;font-size:1.25em}
.sub{opacity:.92;margin:6px 0 0}
.nav a{text-decoration:none;color:#fff;background:rgba(255,255,255,.12);padding:10px 12px;border-radius:999px;border:1px solid rgba(255,255,255,.20);font-weight:bold;display:inline-block;margin-left:8px;}
.nav a:hover{background:rgba(255,255,255,.20);}
.wrap{max-width:1200px;margin:18px auto;padding:0 16px 60px;}
.card{background:var(--card);border:1px solid rgba(0,0,0,.08);border-radius:var(--r);box-shadow:0 6px 16px rgba(0,0,0,.06);padding:14px;}
.section-title{margin:6px 0 10px;color:var(--az1);border-left:4px solid var(--n);padding-left:10px;}
.row{display:flex;gap:12px;align-items:center;flex-wrap:wrap;margin:12px 0;}
.tool{background:#fff;border:1px solid var(--b);border-radius:999px;padding:10px 12px;display:flex;gap:10px;align-items:center;box-shadow:0 2px 10px rgba(0,0,0,.04);}
.tool label{font-weight:bold;color:var(--az1);white-space:nowrap;font-size:.95em;}
.tool input,.tool select{border:0;outline:0;background:transparent;font-size:1em;min-width:200px;}
.tool select{min-width:180px;cursor:pointer}
.btn{border:0;border-radius:999px;padding:10px 14px;cursor:pointer;font-weight:bold;background:var(--az2);color:#fff;box-shadow:0 4px 12px rgba(0,0,0,.14);transition:.15s;}
.btn:hover{background:#0066cc;transform:translateY(-1px);}
.btn.alt{background:#e9eef6;color:var(--az1);box-shadow:none;border:1px solid rgba(0,0,0,.08);}
.btn.alt:hover{background:#dde7f7;}
.btn.danger{background:#8b0000;color:#fff;}
.btn.danger:hover{background:#b00000;}
.msg{margin:10px 0;padding:12px;border-radius:12px;border:1px solid rgba(0,0,0,.10);background:#fff;display:none;}
.msg.ok{background:#eefaf0;border-color:rgba(10,107,43,.25);color:var(--ok);}
.msg.err{background:#fff3f3;border-color:rgba(180,0,0,.2);color:#7a0000;}

.table-wrap{overflow:auto;background:#fff;border:1px solid rgba(0,0,0,.08);border-radius:12px;box-shadow:0 6px 16px rgba(0,0,0,.06);}
table{width:100%;border-collapse:collapse;min-width:1500px;}
thead th{position:sticky;top:0;z-index:2;background:linear-gradient(to bottom,#eaf1ff,#dbe8ff);color:var(--az1);text-align:left;padding:12px;border-bottom:2px solid rgba(0,0,0,.08);font-size:.95em;}
tbody td{padding:12px;border-bottom:1px solid rgba(0,0,0,.06);vertical-align:top;font-size:.97em;}
tbody tr:hover{background:#f6f9ff;}
.mono{font-family:ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, "Liberation Mono", monospace;font-size:.93em;}
.pill{display:inline-flex;align-items:center;gap:8px;font-weight:bold;color:var(--az1);background:#eaf1ff;border:1px solid rgba(0,51,102,.12);padding:4px 10px;border-radius:999px;white-space:nowrap;}
a.link{color:var(--link);font-weight:bold;text-decoration:none;}
a.link:hover{text-decoration:underline;color:var(--az2);}
.pager{display:flex;gap:10px;align-items:center;flex-wrap:wrap;margin:12px 0;}
.small{color:var(--mut);font-size:.92em;}

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

@media (max-width:900px){
  .grid2{grid-template-columns:1fr;}
  table{min-width:1200px;}
}

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
      <h1>Editor de Directorio · por filas</h1>
      <div class="sub">Usuario: <strong><?= h($user) ?></strong> · API: directorio_api.php</div>
    </div>
    <div class="nav">
    <a href="/admin/index.php">Tablero</a>
      <a href="/directorio.html" target="_blank" rel="noopener">Ver Directorio</a>
      <a href="/admin/logout.php">Salir</a>
    </div>
  </div>
</header>

<div class="wrap">
  <div id="msg" class="msg"></div>

  <div class="card">
    <h2 class="section-title">Listado</h2>

    <div class="row">
      <div class="tool"><label>🔎 Buscar</label><input id="q" placeholder="provincia, localidad, mail..."></div>

      <div class="tool"><label>🗺️ Provincia</label><select id="prov"></select></div>
      <div class="tool"><label>🏛️ Municipio</label><select id="mun"></select></div>
      <div class="tool"><label>🏘️ Localidad</label><select id="loc"></select></div>

      <div class="tool"><label>🏷️ Denominación</label><select id="den"></select></div>

      <div class="tool"><label>↕️ Orden</label>
        <select id="sort">
          <option value="geo_asc">Geo ↑</option>
          <option value="geo_desc">Geo ↓</option>
          <option value="den_asc">Denominación ↑</option>
          <option value="den_desc">Denominación ↓</option>
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
      <button class="btn" id="newEntry">+ Nuevo</button>
    </div>

    <div class="pager">
      <button class="btn alt" id="prev">←</button>
      <div class="mono" id="pageInfo">Página</div>
      <button class="btn alt" id="next">→</button>
      <span class="small" id="countInfo"></span>
    </div>

    <div class="table-wrap">
      <table>
        <thead>
          <tr>
            <th>Provincia</th>
            <th>Municipio</th>
            <th>Localidad</th>
            <th>Denominación</th>
            <th>Domicilio</th>
            <th>Teléfono</th>
            <th>E-mail</th>
            <th>Web</th>
            <th>Instagram</th>
            <th>Facebook</th>
            <th>Comentario</th>
            <th>Acciones</th>
          </tr>
        </thead>
        <tbody id="tbody"></tbody>
      </table>
    </div>
  </div>
</div>

<!-- MODAL -->
<div id="modalBackdrop" class="modal-backdrop">
  <div class="modal">
    <div class="modal-head">
      <h3 id="modalTitle">Editar entrada</h3>
      <button class="btn alt" id="modalClose">Cerrar</button>
    </div>

    <div class="modal-body">
      <div class="grid2">
        <div class="field"><label>Provincia</label><input id="fProv" placeholder="BUENOS AIRES"></div>
        <div class="field"><label>Municipio</label><input id="fMun" placeholder="San Isidro"></div>
      </div>

      <div class="grid2" style="margin-top:12px;">
        <div class="field"><label>Localidad</label><input id="fLoc" placeholder="Martínez"></div>

        <div class="field">
          <label>Denominación</label>
          <select id="fDen">
            <option value="Defensa Civil">Defensa Civil</option>
            <option value="Protección Civil">Protección Civil</option>
            <option value="__new__">Otra (escribir)</option>
          </select>
        </div>
      </div>

      <div class="field" id="fDenNewWrap" style="display:none; margin-top:12px;">
        <label>Otra denominación</label>
        <input id="fDenNew" placeholder="Ej: Dirección de Defensa Civil">
      </div>

      <div class="grid2" style="margin-top:12px;">
        <div class="field"><label>Domicilio</label><input id="fDomicilio"></div>
        <div class="field"><label>Teléfono</label><input id="fTelefono"></div>
      </div>

      <div class="grid2" style="margin-top:12px;">
        <div class="field"><label>E-mail</label><input id="fEmail" placeholder="contacto@..."></div>
        <div class="field"><label>Website</label><input id="fWebsite" placeholder="https://..."></div>
      </div>

      <div class="grid2" style="margin-top:12px;">
        <div class="field"><label>Instagram</label><input id="fInstagram" placeholder="https://instagram.com/..."></div>
        <div class="field"><label>Facebook</label><input id="fFacebook" placeholder="https://facebook.com/..."></div>
      </div>

      <div class="field" style="margin-top:12px;">
        <label>Comentario</label>
        <textarea id="fComentario"></textarea>
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
const API = '/admin/directorio_api.php';
let csrfToken = '';
let state = {
  page:1,
  per:50,
  entries:[],
  options:{ denominaciones:[], provincias:[], municipios:[], localidades:[] },
  paging:{page:1, pages:1, total:0, per_page:50}
};
let editing = null;

function showMsg(text, type='ok'){
  const el = document.getElementById('msg');
  el.textContent = text;
  el.className = 'msg ' + (type === 'ok' ? 'ok' : 'err');
  el.style.display = 'block';
  setTimeout(() => el.style.display = 'none', 4500);
}

async function api(actionOrQuery, method='GET', body=null){
  const headers = { 'X-CSRF-Token': csrfToken };
  if (method === 'POST') headers['Content-Type'] = 'application/json';

  const url = actionOrQuery.includes('action=')
    ? `${API}?${actionOrQuery}`
    : `${API}?action=${encodeURIComponent(actionOrQuery)}`;

  const res = await fetch(url, { method, headers, body: body ? JSON.stringify(body) : null });
  const text = await res.text();

  let json;
  try { json = JSON.parse(text); }
  catch(e){ throw new Error('Respuesta no-JSON desde API. Inicio: ' + text.slice(0, 140)); }

  if (!json.ok) throw new Error(json.error || 'Error API');
  return json;
}

async function initCsrf(){
  const data = await api('csrf');
  csrfToken = data.csrf;
}

function debounce(fn, ms=350){
  let t;
  return (...args) => {
    clearTimeout(t);
    t = setTimeout(() => fn(...args), ms);
  };
}

function escapeHtml(s){ return (s ?? '').toString().replace(/[&<>"']/g, m => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#039;'}[m])); }
function escapeAttr(s){ return escapeHtml(s).replace(/"/g,'&quot;'); }

function setSelectOptions(sel, items, allLabel){
  const current = sel.value || '';
  sel.innerHTML = `<option value="">${allLabel}</option>` + items.map(v => `<option value="${escapeAttr(v)}">${escapeHtml(v)}</option>`).join('');
  sel.value = current;
}

async function loadList(){
  const params = new URLSearchParams({
    action:'list',
    page: state.page,
    per_page: state.per,
    q: document.getElementById('q').value.trim(),
    provincia: document.getElementById('prov').value,
    municipio: document.getElementById('mun').value,
    localidad: document.getElementById('loc').value,
    denominacion: document.getElementById('den').value,
    sort: document.getElementById('sort').value
  });

  const data = await api(params.toString());
  state.options = data.options || state.options;
  state.entries = data.entries || [];
  state.paging = data.paging || state.paging;

  fillFilters();
  renderTable();
  renderPaging();
}

function fillFilters(){
  setSelectOptions(document.getElementById('prov'), state.options.provincias || [], 'Todas');
  setSelectOptions(document.getElementById('mun'),  state.options.municipios || [], 'Todos');
  setSelectOptions(document.getElementById('loc'),  state.options.localidades || [], 'Todas');
  setSelectOptions(document.getElementById('den'),  state.options.denominaciones || [], 'Todas');
}

function renderPaging(){
  const p = state.paging || {};
  const page = p.page || 1;
  const pages = p.pages || 1;
  const total = p.total || 0;

  document.getElementById('pageInfo').textContent = `Página ${page} / ${pages}`;
  document.getElementById('countInfo').textContent = `${total} registros`;

  document.getElementById('prev').disabled = page <= 1;
  document.getElementById('next').disabled = page >= pages;
}

function urlCell(u){
  if (!u) return '—';
  const safe = escapeAttr(u);
  return `<a class="link" href="${safe}" target="_blank" rel="noopener">Abrir</a>`;
}

function shortText(s, max=90){
  s = (s || '').trim();
  if (!s) return '—';
  return escapeHtml(s.length > max ? s.slice(0, max-1) + '…' : s);
}

function renderTable(){
  const tbody = document.getElementById('tbody');
  tbody.innerHTML = '';

  state.entries.forEach(e => {
    const tr = document.createElement('tr');
    tr.innerHTML = `
      <td>${escapeHtml(e.provincia || '—')}</td>
      <td>${escapeHtml(e.municipio || '—')}</td>
      <td>${escapeHtml(e.localidad || '—')}</td>
      <td><span class="pill">${escapeHtml(e.denominacion || '—')}</span></td>
      <td>${shortText(e.domicilio, 110)}</td>
      <td>${escapeHtml(e.telefono || '—')}</td>
      <td>${e.email ? `<a class="link" href="mailto:${escapeAttr(e.email)}">${escapeHtml(e.email)}</a>` : '—'}</td>
      <td>${urlCell(e.website)}</td>
      <td>${urlCell(e.instagram)}</td>
      <td>${urlCell(e.facebook)}</td>
      <td>${shortText(e.comentario, 120)}</td>
      <td><button class="btn alt" data-act="edit" data-id="${escapeAttr(e.id)}">Editar</button></td>
    `;
    tbody.appendChild(tr);
  });
}

function openModal(entry){
  editing = entry ? {...entry} : {
    id:'',
    provincia:'',
    municipio:'',
    localidad:'',
    denominacion:'Defensa Civil',
    domicilio:'',
    telefono:'',
    email:'',
    website:'',
    instagram:'',
    facebook:'',
    comentario:''
  };

  document.getElementById('modalTitle').textContent = editing.id ? 'Editar entrada' : 'Nueva entrada';
  document.getElementById('modalBackdrop').style.display = 'flex';

  document.getElementById('fProv').value = editing.provincia || '';
  document.getElementById('fMun').value  = editing.municipio || '';
  document.getElementById('fLoc').value  = editing.localidad || '';

  const fDen = document.getElementById('fDen');
  const known = ['Defensa Civil','Protección Civil'];
  if (known.includes(editing.denominacion)) {
    fDen.value = editing.denominacion;
    document.getElementById('fDenNewWrap').style.display = 'none';
    document.getElementById('fDenNew').value = '';
  } else {
    fDen.value = '__new__';
    document.getElementById('fDenNewWrap').style.display = 'block';
    document.getElementById('fDenNew').value = editing.denominacion || '';
  }

  document.getElementById('fDomicilio').value  = editing.domicilio || '';
  document.getElementById('fTelefono').value   = editing.telefono || '';
  document.getElementById('fEmail').value      = editing.email || '';
  document.getElementById('fWebsite').value    = editing.website || '';
  document.getElementById('fInstagram').value  = editing.instagram || '';
  document.getElementById('fFacebook').value   = editing.facebook || '';
  document.getElementById('fComentario').value = editing.comentario || '';
}

function closeModal(){
  document.getElementById('modalBackdrop').style.display = 'none';
  editing = null;
}

function collectModal(){
  const denSel = document.getElementById('fDen').value;
  let denominacion = denSel;
  if (denSel === '__new__') denominacion = (document.getElementById('fDenNew').value || '').trim();

  return {
    id: editing?.id || '',

    provincia: (document.getElementById('fProv').value || '').trim(),
    municipio: (document.getElementById('fMun').value || '').trim(),
    localidad: (document.getElementById('fLoc').value || '').trim(),

    denominacion: denominacion || 'Defensa Civil',
    domicilio: (document.getElementById('fDomicilio').value || '').trim(),
    telefono: (document.getElementById('fTelefono').value || '').trim(),
    email: (document.getElementById('fEmail').value || '').trim(),
    website: (document.getElementById('fWebsite').value || '').trim(),
    instagram: (document.getElementById('fInstagram').value || '').trim(),
    facebook: (document.getElementById('fFacebook').value || '').trim(),
    comentario: (document.getElementById('fComentario').value || '').trim()
  };
}

// Events
document.getElementById('tbody').addEventListener('click', (e) => {
  const btn = e.target.closest('button[data-act="edit"]');
  if (!btn) return;
  const id = btn.dataset.id;
  const entry = state.entries.find(x => x.id === id);
  if (!entry) return;
  openModal(entry);
});

document.getElementById('newEntry').onclick = () => openModal(null);
document.getElementById('modalClose').onclick = closeModal;
document.getElementById('modalBackdrop').addEventListener('click', (e) => { if (e.target.id === 'modalBackdrop') closeModal(); });

document.getElementById('fDen').addEventListener('change', () => {
  document.getElementById('fDenNewWrap').style.display = (document.getElementById('fDen').value === '__new__') ? 'block' : 'none';
});

document.getElementById('mSave').onclick = async () => {
  try{
    const payload = collectModal();
    await api('save_entry', 'POST', payload);
    showMsg('Guardado OK', 'ok');
    closeModal();
    await loadList();
  }catch(err){
    showMsg(err.message || String(err), 'err');
  }
};

document.getElementById('mDelete').onclick = async () => {
  if (!editing?.id) { closeModal(); return; }
  if (!confirm('¿Borrar esta entrada?')) return;

  try{
    await api('delete_entry', 'POST', { id: editing.id });
    showMsg('Borrado OK', 'ok');
    closeModal();
    await loadList();
  }catch(err){
    showMsg(err.message || String(err), 'err');
  }
};

document.getElementById('mDuplicate').onclick = async () => {
  try{
    const payload = collectModal();
    payload.id = '';
    await api('save_entry', 'POST', payload);
    showMsg('Duplicado OK', 'ok');
    closeModal();
    await loadList();
  }catch(err){
    showMsg(err.message || String(err), 'err');
  }
};

document.getElementById('prev').onclick = async () => { if (state.page <= 1) return; state.page--; await loadList(); };
document.getElementById('next').onclick = async () => {
  const pages = state.paging?.pages || 1;
  if (state.page >= pages) return;
  state.page++;
  await loadList();
};

document.getElementById('per').addEventListener('change', async () => {
  state.per = parseInt(document.getElementById('per').value, 10) || 50;
  state.page = 1;
  await loadList();
});

document.getElementById('clear').onclick = async () => {
  document.getElementById('q').value = '';
  document.getElementById('prov').value = '';
  document.getElementById('mun').value = '';
  document.getElementById('loc').value = '';
  document.getElementById('den').value = '';
  document.getElementById('sort').value = 'geo_asc';
  state.page = 1;
  await loadList();
};

const reloadDebounced = debounce(async () => { state.page = 1; await loadList(); }, 300);
document.getElementById('q').addEventListener('input', reloadDebounced);
document.getElementById('prov').addEventListener('change', reloadDebounced);
document.getElementById('mun').addEventListener('change', reloadDebounced);
document.getElementById('loc').addEventListener('change', reloadDebounced);
document.getElementById('den').addEventListener('change', reloadDebounced);
document.getElementById('sort').addEventListener('change', reloadDebounced);

(async () => {
  try{
    await initCsrf();
    await loadList();
  }catch(err){
    showMsg(err.message || String(err), 'err');
  }
})();
</script>
</body>
</html>