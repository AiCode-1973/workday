<!-- Relatórios -->

<div class="p-6 max-w-6xl mx-auto">

  <div class="flex items-center justify-between mb-6">
    <h1 class="text-2xl font-bold text-gray-900">Relatórios</h1>
    <div class="flex gap-2">
      <?php foreach ($_SESSION['boards'] ?? [] as $b): ?>
      <a href="<?= APP_URL ?>/reports/export-csv?board_id=<?= $b['id'] ?>"
         class="btn-secondary text-sm">
        ⬇ CSV — <?= htmlspecialchars($b['name']) ?>
      </a>
      <?php endforeach; ?>
    </div>
  </div>

  <!-- Stats -->
  <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-8">
    <?php
    $cards = [
      ['label'=>'Quadros ativos',   'value'=>$stats['total_boards'], 'color'=>'text-indigo-600'],
      ['label'=>'Tarefas abertas',  'value'=>$stats['open_items'],   'color'=>'text-blue-600'],
      ['label'=>'Concluídas',       'value'=>$stats['done_items'],   'color'=>'text-green-600'],
      ['label'=>'Atrasadas',        'value'=>$stats['overdue'],      'color'=>'text-red-600'],
    ];
    foreach ($cards as $c): ?>
    <div class="card text-center">
      <div class="text-3xl font-bold <?= $c['color'] ?>"><?= $c['value'] ?></div>
      <div class="text-gray-500 text-sm mt-1"><?= $c['label'] ?></div>
    </div>
    <?php endforeach; ?>
  </div>

  <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">

    <!-- Por prioridade -->
    <div class="card">
      <h2 class="font-semibold text-gray-800 mb-4">Tarefas por prioridade</h2>
      <?php
      $priorityLabels = ['urgent'=>'Urgente','high'=>'Alta','medium'=>'Média','low'=>'Baixa','none'=>'Sem prioridade'];
      $priorityColors = ['urgent'=>'bg-red-500','high'=>'bg-orange-500','medium'=>'bg-yellow-400','low'=>'bg-blue-400','none'=>'bg-gray-300'];
      $maxPriority    = max(1, max(array_column($byPriority, 'total')));
      foreach ($byPriority as $p):
        $pct = round(($p['total'] / $maxPriority) * 100);
        $label = $priorityLabels[$p['priority']] ?? $p['priority'];
        $color = $priorityColors[$p['priority']] ?? 'bg-gray-400';
      ?>
      <div class="mb-3">
        <div class="flex justify-between text-sm mb-1">
          <span><?= $label ?></span>
          <span class="font-medium"><?= $p['total'] ?></span>
        </div>
        <div class="h-2 bg-gray-100 rounded-full overflow-hidden">
          <div class="h-full <?= $color ?> rounded-full" style="width:<?= $pct ?>%"></div>
        </div>
      </div>
      <?php endforeach; ?>
      <?php if (!$byPriority): ?><p class="text-gray-400 text-sm">Nenhum dado</p><?php endif; ?>
    </div>

    <!-- Por membro -->
    <div class="card">
      <h2 class="font-semibold text-gray-800 mb-4">Tarefas por membro</h2>
      <div class="overflow-x-auto">
        <table class="w-full text-sm">
          <thead>
            <tr class="text-gray-400 border-b">
              <th class="text-left pb-2">Membro</th>
              <th class="text-right pb-2">Tarefas</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($byMember as $m): ?>
            <tr class="border-b last:border-0">
              <td class="py-2 font-medium"><?= htmlspecialchars($m['name']) ?></td>
              <td class="py-2 text-right text-indigo-600 font-semibold"><?= $m['total'] ?></td>
            </tr>
            <?php endforeach; ?>
            <?php if (!$byMember): ?><tr><td colspan="2" class="py-4 text-gray-400 text-center">Nenhum dado</td></tr><?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>

  </div>

  <!-- Tarefas criadas por dia -->
  <div class="card mb-8">
    <h2 class="font-semibold text-gray-800 mb-4">Tarefas criadas nos últimos 30 dias</h2>
    <?php if ($byDay):
      $maxDay = max(1, max(array_column($byDay, 'total')));
      $chartH = 120;
    ?>
    <div class="flex items-end gap-1" style="height:<?= $chartH ?>px;">
      <?php foreach ($byDay as $d):
        $h   = max(4, round(($d['total'] / $maxDay) * $chartH));
        $day = date('d/M', strtotime($d['day']));
      ?>
      <div class="flex-1 flex flex-col items-center gap-1 group relative">
        <div class="absolute bottom-full mb-1 bg-gray-800 text-white text-xs rounded px-2 py-1 opacity-0 group-hover:opacity-100 whitespace-nowrap z-10 pointer-events-none">
          <?= $day ?>: <?= $d['total'] ?>
        </div>
        <div class="w-full bg-indigo-400 rounded-sm hover:bg-indigo-600 transition-colors" style="height:<?= $h ?>px;"></div>
      </div>
      <?php endforeach; ?>
    </div>
    <?php else: ?>
    <p class="text-gray-400 text-sm">Nenhuma tarefa criada nos últimos 30 dias.</p>
    <?php endif; ?>
  </div>

</div>

