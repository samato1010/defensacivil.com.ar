<?php
declare(strict_types=1);

require_once __DIR__ . '/config.php';
require_login();

$user = current_username();
$role = current_role();
$perms = current_perms();

function badge(string $txt): string {
  $t = htmlspecialchars($txt, ENT_QUOTES, 'UTF-8');
  return '<span class="badge">'.$t.'</span>';
}
?>
<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1"/>
  <title>Admin &middot; DefensaCivil</title>
  <link rel="icon" type="image/svg+xml" href="/favicon.svg"/>
  <style>
    :root{
      --az1:#003366; --az2:#004080; --n:#ff6600;
      --bg:#f6f7f9; --card:#fff; --tx:#222; --mut:#555;
      --b:rgba(0,0,0,.10); --r:14px; --link:#0066cc;
      --ok:#0a6b2b; --bad:#b00000;
    }
    *{box-sizing:border-box}
    body{font-family:Arial,Helvetica,sans-serif;margin:0;background:var(--bg);color:var(--tx);line-height:1.6}

    /* Header */
    header{background:linear-gradient(135deg,var(--az1) 0%,var(--az2) 60%,#004a99 100%);color:#fff;padding:18px 16px;box-shadow:0 4px 20px rgba(0,0,0,.18);}
    .head{max-width:1100px;margin:0 auto;display:flex;align-items:center;justify-content:space-between;gap:12px;flex-wrap:wrap;}
    h1{margin:0;font-size:1.35em;display:flex;align-items:center;gap:10px;}
    .sub{opacity:.85;margin:4px 0 0;font-size:.92em}
    .nav{display:flex;gap:8px;flex-wrap:wrap;align-items:center}
    .nav a{text-decoration:none;color:#fff;background:rgba(255,255,255,.12);padding:9px 14px;border-radius:999px;border:1px solid rgba(255,255,255,.18);font-weight:bold;display:inline-flex;align-items:center;gap:6px;font-size:.9em;transition:.2s}
    .nav a:hover{background:rgba(255,255,255,.22);transform:translateY(-1px)}
    .nav a.logout{background:rgba(255,100,0,.25);border-color:rgba(255,100,0,.35)}
    .nav a.logout:hover{background:rgba(255,100,0,.40)}

    /* Content */
    .wrap{max-width:1100px;margin:0 auto;padding:18px 16px 60px;}

    /* Welcome card */
    .welcome{background:linear-gradient(135deg,#eaf1ff 0%,#f4f7ff 50%,#eef5ff 100%);border:1px solid rgba(0,51,102,.10);border-left:4px solid var(--n);border-radius:var(--r);padding:20px 22px;margin-bottom:20px;display:flex;align-items:center;justify-content:space-between;gap:16px;flex-wrap:wrap;animation:fadeInUp .4s ease both}
    .welcome-left h2{margin:0 0 4px;color:var(--az1);font-size:1.15em}
    .welcome-left .meta{display:flex;gap:8px;flex-wrap:wrap;align-items:center;margin-top:8px}
    .welcome-right{text-align:right}
    .clock{font-family:ui-monospace,SFMono-Regular,Menlo,Monaco,Consolas,monospace;font-size:.92em;color:var(--az1);background:#fff;padding:6px 14px;border-radius:999px;border:1px solid rgba(0,51,102,.10);display:inline-flex;align-items:center;gap:8px;white-space:nowrap}
    .clock-dot{width:8px;height:8px;background:#22c55e;border-radius:50%;animation:pulse 2s ease infinite}
    @keyframes pulse{0%,100%{opacity:1}50%{opacity:.4}}

    /* Badges */
    .badge{display:inline-flex;align-items:center;gap:6px;padding:5px 12px;border-radius:999px;font-weight:900;font-size:.88em;
      background:#eaf1ff;color:var(--az1);border:1px solid rgba(0,51,102,0.12);}
    .badge.orange{background:#fff6ee;color:#7a2d00;border-color:rgba(255,102,0,.25);}

    /* Grid */
    .grid{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:14px;}

    /* Cards */
    .card{background:var(--card);border:1px solid rgba(0,0,0,.08);border-left:4px solid transparent;border-radius:var(--r);box-shadow:0 4px 14px rgba(0,0,0,.05);padding:18px 16px;transition:transform .25s ease,box-shadow .25s ease,border-color .25s ease;position:relative;overflow:hidden}
    .card::after{content:'';position:absolute;inset:0;background:linear-gradient(135deg,transparent 60%,rgba(0,51,102,.02) 100%);pointer-events:none;transition:opacity .25s}
    .card:hover{transform:translateY(-5px);box-shadow:0 12px 32px rgba(0,0,0,.12);border-left-color:var(--n)}
    .card:hover::after{opacity:0}
    .card h2{margin:0 0 8px;font-size:1.05em;color:var(--az1);border-left:4px solid var(--n);padding-left:10px;display:flex;align-items:center;gap:8px}
    .card p{margin:6px 0 0;color:var(--mut);font-size:.93em;line-height:1.5}
    .card-actions{display:flex;gap:8px;flex-wrap:wrap;margin-top:14px;align-items:center}

    /* Stat pill */
    .stat{display:inline-flex;align-items:center;gap:5px;background:#eaf1ff;color:var(--az1);padding:4px 12px;border-radius:999px;font-size:.82em;font-weight:900;border:1px solid rgba(0,51,102,.10);margin-top:8px}
    .stat .num{background:var(--az1);color:#fff;padding:1px 8px;border-radius:999px;font-size:.95em;min-width:20px;text-align:center}
    .stat.loading .num{background:rgba(0,51,102,.15);color:transparent;animation:shimmer 1.2s ease infinite}
    @keyframes shimmer{0%,100%{opacity:.4}50%{opacity:.8}}

    /* Buttons */
    .btn{display:inline-flex;align-items:center;gap:8px;margin:0;
      border:0;border-radius:999px;padding:10px 16px;cursor:pointer;font-weight:bold;font-size:.9em;
      background:var(--az2);color:#fff;box-shadow:0 4px 12px rgba(0,0,0,.14);transition:.2s;text-decoration:none;}
    .btn:hover{background:#0066cc;transform:translateY(-1px);box-shadow:0 6px 18px rgba(0,0,0,.18)}
    .btn.alt{background:#e9eef6;color:var(--az1);box-shadow:none;border:1px solid rgba(0,0,0,.08);}
    .btn.alt:hover{background:#dde7f7;transform:translateY(-1px)}

    /* Animations */
    @keyframes fadeInUp{from{opacity:0;transform:translateY(18px)}to{opacity:1;transform:translateY(0)}}
    .grid .card:nth-child(1){animation:fadeInUp .45s ease both;animation-delay:.05s}
    .grid .card:nth-child(2){animation:fadeInUp .45s ease both;animation-delay:.12s}
    .grid .card:nth-child(3){animation:fadeInUp .45s ease both;animation-delay:.19s}
    .grid .card:nth-child(4){animation:fadeInUp .45s ease both;animation-delay:.26s}
    .grid .card:nth-child(5){animation:fadeInUp .45s ease both;animation-delay:.33s}

    /* Footer */
    footer{background:linear-gradient(135deg,var(--az1),var(--az2));color:#fff;text-align:center;padding:24px 16px;margin-top:40px;font-size:.9em}
    footer p{margin:0;opacity:.85}

    /* Scrollbar */
    ::-webkit-scrollbar{width:8px;height:8px}
    ::-webkit-scrollbar-track{background:#f1f1f1;border-radius:4px}
    ::-webkit-scrollbar-thumb{background:#b0bec5;border-radius:4px}
    ::-webkit-scrollbar-thumb:hover{background:#90a4ae}
    html{scroll-behavior:smooth}

    /* Responsive */
    @media(max-width:980px){.grid{grid-template-columns:1fr;} .welcome{flex-direction:column;text-align:center} .welcome-right{text-align:center}}
    @media(max-width:600px){.nav{gap:6px} .nav a{padding:7px 10px;font-size:.82em}}
  </style>
</head>
<body>

<header>
  <div class="head">
    <div>
      <h1>Panel Admin</h1>
      <div class="sub">Defensa Civil Argentina &middot; Gesti&oacute;n del sitio</div>
    </div>
    <div class="nav">
      <a href="/index.html">&#127968; Sitio</a>
      <a href="/directorio.html">&#128214; Directorio</a>
      <a href="/notas.html">&#128240; Notas</a>
      <a class="logout" href="/admin/logout.php">&#128682; Salir</a>
    </div>
  </div>
</header>

<div class="wrap">

  <!-- Welcome card -->
  <div class="welcome">
    <div class="welcome-left">
      <h2>&#128075; Hola, <?= htmlspecialchars($user, ENT_QUOTES, 'UTF-8') ?></h2>
      <div class="meta">
        <?= badge("Rol: " . ($role ?: "\xe2\x80\x94")) ?>
        <?php if (is_admin()): ?>
          <span class="badge orange">&#11088; ADMIN</span>
        <?php endif; ?>
      </div>
    </div>
    <div class="welcome-right">
      <div class="clock">
        <span class="clock-dot"></span>
        <span id="clock">cargando...</span>
      </div>
    </div>
  </div>

  <!-- Editor cards grid -->
  <div class="grid">

    <div class="card">
      <h2>&#128506;&#65039; Mapa / Estado del sitio</h2>
      <p>Verifica rutas, JSON, permisos, y estado general desde la mirada admin.</p>
      <div class="card-actions">
        <a class="btn" href="/admin/mapa.php">Abrir mapa</a>
      </div>
    </div>

    <?php if (user_can('directorio')): ?>
    <div class="card">
      <h2>&#128210; Editor de Directorio</h2>
      <p>Gestionar contactos de organismos de Defensa Civil por provincia.</p>
      <div class="stat loading"><span>Entradas:</span> <span class="num" id="stat-dir">&bull;&bull;&bull;</span></div>
      <div class="card-actions">
        <a class="btn" href="/admin/directorio_editor.php">Abrir editor</a>
      </div>
    </div>
    <?php endif; ?>

    <?php if (user_can('historia')): ?>
    <div class="card">
      <h2>&#128204; Editor de Normativa</h2>
      <p>Editar normativa y marco legal institucional.</p>
      <div class="card-actions">
        <a class="btn" href="/admin/normativa_editor.php">Abrir editor</a>
      </div>
    </div>
    <?php endif; ?>

    <?php if (user_can('notas')): ?>
    <div class="card">
      <h2>&#128221; Editor de Notas</h2>
      <p>Publicar notas y actualizaciones con links e im&aacute;genes.</p>
      <div class="stat loading"><span>Notas:</span> <span class="num" id="stat-notas">&bull;&bull;&bull;</span></div>
      <div class="card-actions">
        <a class="btn" href="/admin/notas_editor.php">Abrir editor</a>
      </div>
    </div>
    <?php endif; ?>

    <?php if (is_admin()): ?>
    <div class="card">
      <h2>&#129309; Colaboradores (Admin)</h2>
      <p>Revisar altas p&uacute;blicas, aprobar, asignar rol/permisos y auditar.</p>
      <div class="stat loading"><span>Aprobados:</span> <span class="num" id="stat-colab">&bull;&bull;&bull;</span></div>
      <div class="card-actions">
        <a class="btn" href="/admin/colaboradores_admin.php">Gestionar</a>
        <a class="btn alt" href="/admin/colaboradores_editor.php">Editor contenido</a>
      </div>
    </div>
    <?php endif; ?>

  </div>

</div>

<footer>
  <p>defensacivil.com.ar &copy; <?= date('Y') ?></p>
</footer>

<script>
// Live clock
(function(){
  function updateClock(){
    var el=document.getElementById('clock');
    if(!el)return;
    var now=new Date();
    var opts={weekday:'long',year:'numeric',month:'long',day:'numeric',hour:'2-digit',minute:'2-digit'};
    el.textContent=now.toLocaleDateString('es-AR',opts);
  }
  updateClock();
  setInterval(updateClock,30000);
})();

// Quick stats
(function(){
  function setCount(id,n){
    var el=document.getElementById(id);
    if(!el)return;
    el.textContent=n;
    var pill=el.closest('.stat');
    if(pill)pill.classList.remove('loading');
  }

  function load(){
    var v='?v='+Date.now();

    fetch('/data/directorio.json'+v).then(function(r){return r.json()}).then(function(d){
      var entries=d.entries||d.data||d.items||[];
      setCount('stat-dir',Array.isArray(entries)?entries.length:0);
    }).catch(function(){});

    fetch('/data/notas.json'+v).then(function(r){return r.json()}).then(function(d){
      var notes=d.notes||d.data||d.items||[];
      setCount('stat-notas',Array.isArray(notes)?notes.length:0);
    }).catch(function(){});

    fetch('/data/colaboradores.json'+v).then(function(r){return r.json()}).then(function(d){
      var items=d.items||[];
      var count=0;
      for(var i=0;i<items.length;i++){if((items[i].status||'').toLowerCase()==='aprobado')count++;}
      setCount('stat-colab',count);
    }).catch(function(){});
  }

  load();
})();
</script>

</body>
</html>
