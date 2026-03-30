<?php
$user     = $_SESSION['user'] ?? [];
$userName = explode(' ', $user['name'] ?? 'UsuÃ¡rio')[0];
$hour     = (int)date('H');
$greeting = $hour < 12 ? 'Bom dia' : ($hour < 18 ? 'Boa tarde' : 'Boa noite');

$totalBoards  = $stats['totalBoards']  ?? 0;
$openItems    = $stats['openItems']    ?? 0;
$totalMembers = $stats['totalMembers'] ?? 0;
$doneItems    = $stats['doneItems']    ?? 0;

$actionLabels = [
    'item.created'   => 'criou',
    'item.updated'   => 'atualizou',
    'item.moved'     => 'moveu',
    'item.archived'  => 'arquivou',
    'item.deleted'   => 'excluiu',
    'comment.added'  => 'comentou em',
    'board.created'  => 'criou o quadro',
    'board.updated'  => 'atualizou o quadro',
];
$ptMonths = ['janeiro','fevereiro','março','abril','maio','junho','julho','agosto','setembro','outubro','novembro','dezembro'];
$todayPt  = date('d') . ' de ' . $ptMonths[(int)date('m') - 1] . ' de ' . date('Y');
?>

<!-- Dashboard principal -->
<div class="dash-root">

  <!-- â”€â”€ Header de boas-vindas â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€ -->
  <div class="dash-hero">
    <div class="dash-hero-text">
      <h1 class="dash-hero-title"><?= $greeting ?>, <?= htmlspecialchars($userName) ?> ðŸ‘‹</h1>
      <p class="dash-hero-sub">Aqui está o resumo do seu workspace hoje, <?= $todayPt ?></p>
    </div>
    <a href="<?= APP_URL ?>/boards" class="btn-primary">
      <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
      Novo quadro
    </a>
  </div>

  <!-- â”€â”€ 4 stat cards â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€ -->
  <div class="dash-stats">

    <div class="stat-card stat-indigo">
      <div class="stat-icon">
        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
      </div>
      <div class="stat-body">
        <span class="stat-label">Quadros ativos</span>
        <span class="stat-value"><?= $totalBoards ?></span>
      </div>
    </div>

    <div class="stat-card stat-amber">
      <div class="stat-icon">
        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
      </div>
      <div class="stat-body">
        <span class="stat-label">Tarefas abertas</span>
        <span class="stat-value"><?= $openItems ?></span>
      </div>
    </div>

    <div class="stat-card stat-green">
      <div class="stat-icon">
        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
      </div>
      <div class="stat-body">
        <span class="stat-label">ConcluÃ­das</span>
        <span class="stat-value"><?= $doneItems ?></span>
      </div>
    </div>

    <div class="stat-card stat-violet">
      <div class="stat-icon">
        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0"/></svg>
      </div>
      <div class="stat-body">
        <span class="stat-label">Membros</span>
        <span class="stat-value"><?= $totalMembers ?></span>
      </div>
    </div>

  </div>

  <!-- â”€â”€ Corpo principal â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€ -->
  <div class="dash-body">

    <!-- Coluna esquerda -->
    <div class="dash-col-main">

      <!-- Tarefas atrasadas -->
      <?php if (!empty($overdueItems)): ?>
      <div class="dash-card">
        <div class="dash-card-header">
          <span class="dash-card-title">
            <span class="status-dot bg-red-500"></span>
            Atrasadas
            <span class="dash-count-badge red"><?= count($overdueItems) ?></span>
          </span>
        </div>
        <div class="task-list">
          <?php foreach ($overdueItems as $item): ?>
            <a href="<?= APP_URL ?>/boards/<?= (int)$item['id'] ?>" class="task-row">
              <span class="priority-dot priority-<?= $item['priority'] ?>"></span>
              <div class="task-info">
                <p class="task-title"><?= htmlspecialchars($item['title']) ?></p>
                <p class="task-board"><?= htmlspecialchars($item['board_name']) ?></p>
              </div>
              <span class="task-date overdue"><?= date('d/m', strtotime($item['due_date'])) ?></span>
            </a>
          <?php endforeach; ?>
        </div>
      </div>
      <?php endif; ?>

      <!-- PrÃ³ximos 7 dias -->
      <div class="dash-card">
        <div class="dash-card-header">
          <span class="dash-card-title">
            <span class="status-dot bg-indigo-500"></span>
            PrÃ³ximos 7 dias
          </span>
        </div>
        <?php if (empty($upcomingItems)): ?>
          <div class="dash-empty">
            <svg class="w-8 h-8 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
            <p>Nenhuma tarefa nos prÃ³ximos dias</p>
          </div>
        <?php else: ?>
          <div class="task-list">
            <?php foreach ($upcomingItems as $item): ?>
              <div class="task-row" style="cursor:default">
                <span class="priority-dot priority-<?= $item['priority'] ?>"></span>
                <div class="task-info">
                  <p class="task-title"><?= htmlspecialchars($item['title']) ?></p>
                  <p class="task-board"><?= htmlspecialchars($item['board_name']) ?></p>
                </div>
                <span class="badge" style="background:<?= htmlspecialchars($item['group_color']) ?>22;color:<?= htmlspecialchars($item['group_color']) ?>">
                  <?= htmlspecialchars($item['group_name']) ?>
                </span>
                <span class="task-date"><?= date('d/m', strtotime($item['due_date'])) ?></span>
              </div>
            <?php endforeach; ?>
          </div>
        <?php endif; ?>
      </div>

      <!-- Quadros recentes -->
      <div class="dash-card">
        <div class="dash-card-header">
          <span class="dash-card-title">Quadros recentes</span>
          <a href="<?= APP_URL ?>/boards" class="dash-link">Ver todos â†’</a>
        </div>
        <?php if (empty($boards)): ?>
          <div class="dash-empty">
            <svg class="w-8 h-8 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2"/></svg>
            <p>Nenhum quadro criado ainda</p>
          </div>
        <?php else: ?>
          <div class="boards-grid">
            <?php foreach (array_slice($boards, 0, 6) as $b):
              $total = (int)($b['total_items'] ?? 0);
              $done  = (int)($b['done_items']  ?? 0);
              $pct   = $total > 0 ? round($done / $total * 100) : 0;
            ?>
              <a href="<?= APP_URL ?>/boards/<?= (int)$b['id'] ?>" class="board-card">
                <div class="board-card-top">
                  <span class="board-dot" style="background:<?= htmlspecialchars($b['color']) ?>"></span>
                  <span class="board-name"><?= htmlspecialchars($b['name']) ?></span>
                  <span class="board-pct"><?= $pct ?>%</span>
                </div>
                <div class="board-progress-track">
                  <div class="board-progress-fill" style="width:<?= $pct ?>%;background:<?= htmlspecialchars($b['color']) ?>"></div>
                </div>
                <p class="board-meta"><?= $done ?>/<?= $total ?> tarefas concluÃ­das</p>
              </a>
            <?php endforeach; ?>
          </div>
        <?php endif; ?>
      </div>

    </div><!-- /dash-col-main -->

    <!-- Coluna direita: atividades -->
    <div class="dash-col-side">
      <div class="dash-card dash-card-full">
        <div class="dash-card-header">
          <span class="dash-card-title">Atividade recente</span>
        </div>
        <?php if (empty($activities)): ?>
          <div class="dash-empty">
            <svg class="w-8 h-8 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
            <p>Nenhuma atividade ainda</p>
          </div>
        <?php else: ?>
          <div class="activity-list">
            <?php foreach ($activities as $act):
              $verb = $actionLabels[$act['action']] ?? $act['action'];
            ?>
              <div class="activity-row">
                <div class="activity-avatar">
                  <?php if (!empty($act['user_avatar'])): ?>
                    <img src="<?= APP_URL ?>/uploads/<?= htmlspecialchars($act['user_avatar']) ?>" class="w-7 h-7 rounded-full object-cover"/>
                  <?php else: ?>
                    <div class="act-initials"><?= mb_strtoupper(mb_substr($act['user_name'] ?? 'U', 0, 1)) ?></div>
                  <?php endif; ?>
                  <span class="activity-line"></span>
                </div>
                <div class="activity-body">
                  <p class="activity-text">
                    <strong><?= htmlspecialchars($act['user_name']) ?></strong>
                    <?= htmlspecialchars($verb) ?>
                    <?php if ($act['item_title']): ?>
                      <span class="activity-item"><?= htmlspecialchars(mb_substr($act['item_title'], 0, 35)) ?><?= mb_strlen($act['item_title']) > 35 ? 'â€¦' : '' ?></span>
                    <?php elseif ($act['board_name']): ?>
                      <span class="activity-item"><?= htmlspecialchars($act['board_name']) ?></span>
                    <?php endif; ?>
                  </p>
                  <p class="activity-time"><?= date('d/m H:i', strtotime($act['created_at'])) ?></p>
                </div>
              </div>
            <?php endforeach; ?>
          </div>
        <?php endif; ?>
      </div>
    </div>

  </div><!-- /dash-body -->
</div>

