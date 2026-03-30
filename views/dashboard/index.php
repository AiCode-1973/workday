<!-- Dashboard -->
<div class="p-6 space-y-6">

  <!-- Stats cards -->
  <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
    <div class="card flex items-center gap-4">
      <div class="w-12 h-12 rounded-xl bg-indigo-100 flex items-center justify-center">
        <svg class="w-6 h-6 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2"/></svg>
      </div>
      <div>
        <p class="text-sm text-gray-500">Quadros ativos</p>
        <p class="text-2xl font-bold text-gray-900"><?= $stats['totalBoards'] ?></p>
      </div>
    </div>
    <div class="card flex items-center gap-4">
      <div class="w-12 h-12 rounded-xl bg-amber-100 flex items-center justify-center">
        <svg class="w-6 h-6 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
      </div>
      <div>
        <p class="text-sm text-gray-500">Tarefas abertas</p>
        <p class="text-2xl font-bold text-gray-900"><?= $stats['openItems'] ?></p>
      </div>
    </div>
    <div class="card flex items-center gap-4">
      <div class="w-12 h-12 rounded-xl bg-green-100 flex items-center justify-center">
        <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0"/></svg>
      </div>
      <div>
        <p class="text-sm text-gray-500">Membros</p>
        <p class="text-2xl font-bold text-gray-900"><?= $stats['totalMembers'] ?></p>
      </div>
    </div>
  </div>

  <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

    <!-- Minhas tarefas -->
    <div class="lg:col-span-2 space-y-4">

      <!-- Atrasadas -->
      <?php if (!empty($overdueItems)): ?>
      <div class="card">
        <h2 class="font-semibold text-gray-900 mb-3 flex items-center gap-2">
          <span class="w-2 h-2 rounded-full bg-red-500"></span> Atrasadas (<?= count($overdueItems) ?>)
        </h2>
        <div class="space-y-2">
          <?php foreach ($overdueItems as $item): ?>
            <a href="<?= APP_URL ?>/boards/<?= $item['id'] ?>" class="flex items-center gap-3 p-3 hover:bg-gray-50 rounded-lg transition group">
              <span class="priority-dot priority-<?= $item['priority'] ?>"></span>
              <div class="flex-1 min-w-0">
                <p class="text-sm font-medium text-gray-900 truncate"><?= htmlspecialchars($item['title']) ?></p>
                <p class="text-xs text-gray-400"><?= htmlspecialchars($item['board_name']) ?></p>
              </div>
              <span class="text-xs text-red-500 font-medium whitespace-nowrap"><?= date('d/m', strtotime($item['due_date'])) ?></span>
            </a>
          <?php endforeach; ?>
        </div>
      </div>
      <?php endif; ?>

      <!-- Próximos 7 dias -->
      <div class="card">
        <h2 class="font-semibold text-gray-900 mb-3 flex items-center gap-2">
          <span class="w-2 h-2 rounded-full bg-indigo-500"></span> Próximos 7 dias
        </h2>
        <?php if (empty($upcomingItems)): ?>
          <p class="text-sm text-gray-400 text-center py-4">Nenhuma tarefa nos próximos dias</p>
        <?php else: ?>
          <div class="space-y-2">
            <?php foreach ($upcomingItems as $item): ?>
              <div class="flex items-center gap-3 p-3 hover:bg-gray-50 rounded-lg">
                <span class="priority-dot priority-<?= $item['priority'] ?>"></span>
                <div class="flex-1 min-w-0">
                  <p class="text-sm font-medium text-gray-900 truncate"><?= htmlspecialchars($item['title']) ?></p>
                  <p class="text-xs text-gray-400"><?= htmlspecialchars($item['board_name']) ?></p>
                </div>
                <span class="badge" style="background:<?= htmlspecialchars($item['group_color']) ?>20;color:<?= htmlspecialchars($item['group_color']) ?>">
                  <?= htmlspecialchars($item['group_name']) ?>
                </span>
              </div>
            <?php endforeach; ?>
          </div>
        <?php endif; ?>
      </div>

      <!-- Boards rápidos -->
      <div class="card">
        <div class="flex items-center justify-between mb-3">
          <h2 class="font-semibold text-gray-900">Quadros recentes</h2>
          <a href="<?= APP_URL ?>/boards" class="text-sm text-indigo-600 hover:underline">Ver todos</a>
        </div>
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
          <?php foreach (array_slice($boards, 0, 6) as $b): ?>
            <?php
              $total = (int)($b['total_items'] ?? 0);
              $done  = (int)($b['done_items'] ?? 0);
              $pct   = $total > 0 ? round($done / $total * 100) : 0;
            ?>
            <a href="<?= APP_URL ?>/boards/<?= $b['id'] ?>" class="flex flex-col gap-2 p-4 rounded-xl border border-gray-200 hover:border-indigo-300 hover:shadow-sm transition">
              <div class="flex items-center gap-2">
                <span class="w-3 h-3 rounded-full" style="background:<?= htmlspecialchars($b['color']) ?>"></span>
                <span class="font-medium text-sm text-gray-900 truncate"><?= htmlspecialchars($b['name']) ?></span>
              </div>
              <div class="w-full bg-gray-100 rounded-full h-1.5">
                <div class="h-1.5 rounded-full bg-indigo-500 transition-all" style="width:<?= $pct ?>%"></div>
              </div>
              <p class="text-xs text-gray-400"><?= $done ?>/<?= $total ?> tarefas</p>
            </a>
          <?php endforeach; ?>
        </div>
      </div>

    </div>

    <!-- Atividades recentes -->
    <div class="card h-fit">
      <h2 class="font-semibold text-gray-900 mb-3">Atividades recentes</h2>
      <?php if (empty($activities)): ?>
        <p class="text-sm text-gray-400 text-center py-6">Nenhuma atividade ainda</p>
      <?php else: ?>
        <div class="space-y-3">
          <?php foreach ($activities as $act): ?>
            <div class="flex gap-3 text-sm">
              <div class="w-7 h-7 rounded-full bg-indigo-100 text-indigo-700 flex items-center justify-center text-xs font-bold shrink-0">
                <?= mb_strtoupper(mb_substr($act['user_name'], 0, 1)) ?>
              </div>
              <div>
                <span class="font-medium text-gray-700"><?= htmlspecialchars($act['user_name']) ?></span>
                <span class="text-gray-500"> <?= htmlspecialchars($act['action']) ?> </span>
                <?php if ($act['item_title']): ?>
                  <span class="font-medium text-gray-700"><?= htmlspecialchars(mb_substr($act['item_title'], 0, 30)) ?></span>
                <?php endif; ?>
                <p class="text-xs text-gray-400 mt-0.5"><?= date('d/m H:i', strtotime($act['created_at'])) ?></p>
              </div>
            </div>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>
    </div>

  </div>
</div>
