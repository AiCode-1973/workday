<!DOCTYPE html>
<html lang="pt-BR" class="h-full">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title><?= htmlspecialchars($pageTitle ?? 'Workday') ?> — Workday</title>
  <meta name="csrf-token" content="<?= $_SESSION['csrf_token'] ?>"/>
  <link rel="preconnect" href="https://fonts.googleapis.com"/>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet"/>
  <link rel="stylesheet" href="<?= APP_URL ?>/css/app.css?v=<?= filemtime(ROOT . '/public/css/app.css') ?>"/>
</head>
<body class="h-full bg-gray-50 font-inter">

<!-- Sidebar -->
<div id="app" class="flex h-full">
  <aside id="sidebar" class="w-64 bg-gray-900 text-white flex flex-col">
    <!-- Logo -->
    <div class="flex items-center gap-3 px-5 py-4 border-b border-gray-700">
      <div class="w-8 h-8 rounded-lg bg-indigo-500 flex items-center justify-center font-bold text-sm shrink-0">W</div>
      <span class="font-semibold text-lg tracking-tight sidebar-logo-text">Workday</span>
    </div>

    <!-- Nav principal -->
    <nav class="flex-1 overflow-y-auto py-4 px-3 space-y-1">
      <a href="<?= APP_URL ?>/dashboard" class="sidebar-link <?= str_contains($_SERVER['REQUEST_URI'],'/dashboard')?'active':'' ?>" title="Dashboard">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/></svg>
        <span class="sidebar-label">Dashboard</span>
      </a>
      <a href="<?= APP_URL ?>/boards" class="sidebar-link <?= str_contains($_SERVER['REQUEST_URI'],'/boards')?'active':'' ?>" title="Quadros">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
        <span class="sidebar-label">Quadros</span>
      </a>

      <!-- Portfolios + Boards agrupados -->
      <?php if (!empty($portfolios ?? [])): ?>
        <?php foreach ($portfolios as $p): ?>

          <div class="mt-3 portfolio-section">
            <div class="flex items-center gap-2 px-2 py-1 text-xs font-semibold uppercase tracking-wider text-gray-400">
              <span class="w-2 h-2 rounded-full" style="background:<?= htmlspecialchars($p['color']) ?>"></span>
              <?= htmlspecialchars($p['name']) ?>
            </div>
            <?php foreach ($boards ?? [] as $b): if ((int)$b['portfolio_id'] !== (int)$p['id']) continue; ?>
              <a href="<?= APP_URL ?>/boards/<?= $b['id'] ?>" class="sidebar-link pl-6 text-sm">
                <span class="w-2 h-2 rounded-full shrink-0" style="background:<?= htmlspecialchars($b['color']) ?>"></span>
                <?= htmlspecialchars($b['name']) ?>
              </a>
            <?php endforeach; ?>
          </div>
        <?php endforeach; ?>
        <!-- Boards sem portfolio -->
        <?php foreach ($boards ?? [] as $b): if ($b['portfolio_id']) continue; ?>
          <a href="<?= APP_URL ?>/boards/<?= $b['id'] ?>" class="sidebar-link text-sm">
            <span class="w-2 h-2 rounded-full shrink-0" style="background:<?= htmlspecialchars($b['color']) ?>"></span>
            <?= htmlspecialchars($b['name']) ?>
          </a>
        <?php endforeach; ?>
      <?php endif; ?>
    </nav>

    <!-- Usuário -->
    <div class="border-t border-gray-700 p-4">
      <div class="flex items-center gap-2 mb-2 sidebar-bottom-reports">
        <a href="<?= APP_URL ?>/reports" class="sidebar-link text-xs w-full <?= str_contains($_SERVER['REQUEST_URI'],'/reports')?'active':'' ?>" title="Relatórios">
          📊 <span class="sidebar-label">Relatórios</span>
        </a>
      </div>
      <div class="flex items-center gap-3 sidebar-user-row">
        <?php $u = $_SESSION['user'] ?? []; ?>
        <?php if (!empty($u['avatar'])): ?>
          <img src="<?= APP_URL ?>/uploads/<?= htmlspecialchars($u['avatar']) ?>" class="w-8 h-8 rounded-full object-cover"/>
        <?php else: ?>
          <div class="w-8 h-8 rounded-full bg-indigo-500 flex items-center justify-center text-sm font-bold">
            <?= mb_strtoupper(mb_substr($u['name'] ?? 'U', 0, 1)) ?>
          </div>
        <?php endif; ?>
        <div class="flex-1 min-w-0 sidebar-user-info">
          <a href="<?= APP_URL ?>/profile" class="text-sm font-medium truncate hover:text-indigo-300 block"><?= htmlspecialchars($u['name'] ?? '') ?></a>
          <p class="text-xs text-gray-400 truncate"><?= htmlspecialchars($u['email'] ?? '') ?></p>
        </div>
        <a href="<?= APP_URL ?>/logout" class="text-gray-400 hover:text-white" title="Sair">
          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/></svg>
        </a>
      </div>
    </div>
  </aside>

  <!-- Conteúdo principal -->
  <div class="flex-1 flex flex-col min-w-0 overflow-hidden">
    <!-- TopBar -->
    <header class="h-14 bg-white border-b border-gray-200 flex items-center px-6 gap-4 shrink-0">
      <button id="sidebarToggle" title="Recolher menu">
        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/></svg>
      </button>
      <h1 class="text-lg font-semibold text-gray-900 flex-1"><?= htmlspecialchars($pageTitle ?? '') ?></h1>

      <!-- Notificações -->
      <div class="relative" id="notifWrapper">
        <button id="notifBtn" class="relative text-gray-500 hover:text-gray-900">
          <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/></svg>
          <span id="notifBadge" class="hidden absolute -top-1 -right-1 w-4 h-4 bg-red-500 rounded-full text-white text-xs flex items-center justify-center">0</span>
        </button>
        <div id="notifDropdown" class="hidden absolute right-0 mt-2 w-80 bg-white rounded-xl shadow-lg border border-gray-200 z-50 overflow-hidden">
          <div class="flex items-center justify-between px-4 py-3 border-b border-gray-100">
            <span class="font-semibold text-sm">Notificações</span>
            <button id="markAllReadBtn" class="text-xs text-indigo-600 hover:underline">Marcar todas como lidas</button>
          </div>
          <div id="notifList" class="max-h-72 overflow-y-auto divide-y divide-gray-50"></div>
        </div>
      </div>
    </header>

    <!-- Area de conteúdo -->
    <main class="flex-1 overflow-auto">
      <?php require __DIR__ . '/../' . ($content ?? 'dashboard/index') . '.php'; ?>
    </main>
  </div>
</div>

<!-- Modal global -->
<div id="modal" class="hidden fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-4">
  <div id="modalContent" class="bg-white rounded-2xl shadow-2xl w-full max-w-2xl max-h-[90vh] overflow-y-auto"></div>
</div>

<!-- Modal largo (ferramentas: SIPOC, etc.) -->
<div id="modalWide" class="hidden fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-4">
  <div id="modalWideContent" class="bg-white rounded-2xl shadow-2xl w-full max-h-[92vh] overflow-y-auto" style="max-width:min(1100px,96vw)"></div>
</div>

<!-- Toast -->
<div id="toastContainer" class="fixed bottom-6 right-6 z-50 flex flex-col gap-2 pointer-events-none"></div>

<script>
  const APP_URL  = <?= json_encode(rtrim(APP_URL, '/')) ?>;
  const WS_HOST  = <?= json_encode(WS_HOST ?? '127.0.0.1') ?>;
  const WS_PORT  = <?= (int)(WS_PORT ?? 8080) ?>;
  const WS_UID   = <?= (int)($_SESSION['user_id'] ?? 0) ?>;
  const WS_TOKEN = <?= json_encode(hash_hmac('sha256', 'ws:' . ($_SESSION['user_id'] ?? 0) . ':' . floor(time() / 3600), SECRET_KEY)) ?>;
</script>
<script src="<?= APP_URL ?>/js/app.js"></script>
</body>
</html>
