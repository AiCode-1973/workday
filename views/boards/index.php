<!-- Boards index -->
<div class="p-6">
  <div class="flex items-center justify-between mb-6">
    <h2 class="text-xl font-bold text-gray-900">Todos os Quadros</h2>
    <button onclick="WorkdayBoards.openNewBoardModal()" class="btn-primary">
      <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
      Novo Quadro
    </button>
  </div>

  <?php if (empty($boards)): ?>
    <div class="text-center py-20">
      <div class="w-16 h-16 bg-indigo-100 rounded-2xl mx-auto flex items-center justify-center mb-4">
        <svg class="w-8 h-8 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2"/></svg>
      </div>
      <h3 class="text-lg font-semibold text-gray-900 mb-2">Nenhum quadro ainda</h3>
      <p class="text-gray-500 mb-6">Crie seu primeiro quadro para começar a organizar tarefas</p>
      <button onclick="WorkdayBoards.openNewBoardModal()" class="btn-primary">Criar primeiro quadro</button>
    </div>
  <?php else: ?>
    <?php
      // Agrupar por portfolio
      $byPortfolio = [];
      foreach ($boards as $b) {
          $key = $b['portfolio_id'] ? $b['portfolio_name'] : '__no_portfolio';
          $byPortfolio[$key][] = $b;
      }
    ?>
    <?php foreach ($byPortfolio as $portName => $portBoards): ?>
      <div class="mb-8">
        <?php if ($portName !== '__no_portfolio'): ?>
          <h3 class="text-sm font-semibold uppercase tracking-wider text-gray-400 mb-3 flex items-center gap-2">
            <span class="w-2 h-2 rounded-full" style="background:<?= htmlspecialchars($portBoards[0]['portfolio_color'] ?? '#94a3b8') ?>"></span>
            <?= htmlspecialchars($portName) ?>
          </h3>
        <?php endif; ?>

        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4">
          <?php foreach ($portBoards as $b): ?>
            <?php
              $total = (int)($b['total_items'] ?? 0);
              $done  = (int)($b['done_items'] ?? 0);
              $pct   = $total > 0 ? round($done / $total * 100) : 0;
            ?>
            <a href="<?= APP_URL ?>/boards/<?= $b['id'] ?>" class="group card hover:border-indigo-300 hover:shadow-md transition p-5 flex flex-col gap-3 border border-gray-200">
              <div class="flex items-start justify-between">
                <div class="w-10 h-10 rounded-xl flex items-center justify-center text-white font-bold text-sm"
                     style="background:<?= htmlspecialchars($b['color']) ?>">
                  <?= mb_strtoupper(mb_substr($b['name'], 0, 1)) ?>
                </div>
                <span class="text-xs text-gray-400 capitalize"><?= htmlspecialchars($b['default_view']) ?></span>
              </div>
              <div>
                <h4 class="font-semibold text-gray-900 group-hover:text-indigo-600 transition"><?= htmlspecialchars($b['name']) ?></h4>
                <?php if ($b['description']): ?>
                  <p class="text-xs text-gray-500 mt-1 line-clamp-2"><?= htmlspecialchars($b['description']) ?></p>
                <?php endif; ?>
              </div>
              <div class="mt-auto">
                <div class="flex justify-between text-xs text-gray-400 mb-1">
                  <span><?= $done ?>/<?= $total ?> tarefas</span>
                  <span><?= $pct ?>%</span>
                </div>
                <div class="w-full bg-gray-100 rounded-full h-1.5">
                  <div class="h-1.5 rounded-full transition-all" style="width:<?= $pct ?>%;background:<?= htmlspecialchars($b['color']) ?>"></div>
                </div>
              </div>
            </a>
          <?php endforeach; ?>

          <!-- Card novo quadro neste portfolio -->
          <button onclick="WorkdayBoards.openNewBoardModal(<?= $portBoards[0]['portfolio_id'] ?? 'null' ?>)"
                  class="card border-2 border-dashed border-gray-200 hover:border-indigo-300 flex items-center justify-center gap-2 text-gray-400 hover:text-indigo-500 transition p-5 min-h-[120px]">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
            <span class="text-sm font-medium">Novo quadro</span>
          </button>
        </div>
      </div>
    <?php endforeach; ?>
  <?php endif; ?>
</div>

<!-- Modal novo board — 2 passos -->
<template id="newBoardModalTpl">
  <div class="p-6 w-full" style="max-width:520px">

    <!-- Indicador de passo -->
    <div class="flex items-center gap-2 mb-5">
      <div class="nb-step-dot nb-step-active" id="nbDot1">1</div>
      <div class="nb-step-line"></div>
      <div class="nb-step-dot" id="nbDot2">2</div>
      <span class="text-xs text-gray-400 ml-2" id="nbStepLabel">Informações do quadro</span>
    </div>

    <!-- PASSO 1: dados do quadro -->
    <div id="nbStep1">
      <h3 class="text-lg font-semibold text-gray-900 mb-4">Criar novo quadro</h3>
      <div class="space-y-4">
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Nome *</label>
          <input type="text" id="nbName" required class="form-input" placeholder="Meu projeto"/>
        </div>
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Descrição</label>
          <textarea id="nbDesc" rows="2" class="form-input" placeholder="Descrição opcional"></textarea>
        </div>
        <div class="grid grid-cols-2 gap-4">
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Cor</label>
            <input type="color" id="nbColor" value="#6366f1" class="h-10 w-full rounded-lg border border-gray-300 cursor-pointer p-1"/>
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Visualização padrão</label>
            <select id="nbView" class="form-input">
              <option value="kanban">Kanban</option>
              <option value="list">Lista</option>
              <option value="calendar">Calendário</option>
              <option value="table">Tabela</option>
            </select>
          </div>
        </div>
      </div>
      <div class="flex justify-end gap-3 pt-4">
        <button type="button" onclick="WorkdayApp.closeModal()" class="btn-secondary">Cancelar</button>
        <button type="button" id="nbNextBtn" class="btn-primary">
          Próximo: Ferramentas
          <svg class="w-4 h-4 ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
        </button>
      </div>
    </div>

    <!-- PASSO 2: escolha da ferramenta -->
    <div id="nbStep2" style="display:none">
      <h3 class="text-lg font-semibold text-gray-900 mb-1">Ferramentas</h3>
      <p class="text-sm text-gray-500 mb-4">Escolha uma ferramenta para associar a este quadro (opcional).</p>

      <div class="grid grid-cols-2 gap-3" id="nbToolGrid">

        <!-- Nenhuma -->
        <label class="nb-tool-card nb-tool-selected" data-tool="none">
          <input type="radio" name="nbTool" value="none" checked class="sr-only"/>
          <div class="nb-tool-icon" style="background:#f1f5f9">
            <svg class="w-6 h-6 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"/></svg>
          </div>
          <span class="nb-tool-name">Nenhuma</span>
          <span class="nb-tool-sub">Quadro simples</span>
        </label>

        <!-- SIPOC -->
        <label class="nb-tool-card" data-tool="sipoc">
          <input type="radio" name="nbTool" value="sipoc" class="sr-only"/>
          <div class="nb-tool-icon" style="background:#eef2ff">
            <svg class="w-6 h-6" style="color:#6366f1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3 10h18M3 14h18M10 3v18M14 3v18"/></svg>
          </div>
          <span class="nb-tool-name">SIPOC</span>
          <span class="nb-tool-sub">Diagrama de processo</span>
        </label>

      </div>

      <div class="flex justify-between gap-3 pt-5">
        <button type="button" id="nbBackBtn" class="btn-secondary">
          <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
          Voltar
        </button>
        <button type="button" id="nbCreateBtn" class="btn-primary">Criar quadro</button>
      </div>
    </div>

  </div>
</template>

<style>
.nb-step-dot {
  width:24px;height:24px;border-radius:50%;
  display:flex;align-items:center;justify-content:center;
  font-size:11px;font-weight:700;
  background:#e5e7eb;color:#6b7280;
}
.nb-step-dot.nb-step-active { background:#6366f1;color:#fff; }
.nb-step-line { flex:1;height:2px;background:#e5e7eb;max-width:40px; }
.nb-tool-card {
  display:flex;flex-direction:column;align-items:center;
  gap:8px;padding:16px 12px;
  border:2px solid #e5e7eb;border-radius:12px;
  cursor:pointer;transition:border-color .15s,box-shadow .15s;
  text-align:center;
}
.nb-tool-card:hover { border-color:#a5b4fc; }
.nb-tool-card.nb-tool-selected { border-color:#6366f1;box-shadow:0 0 0 3px rgba(99,102,241,.15); }
.nb-tool-icon {
  width:48px;height:48px;border-radius:12px;
  display:flex;align-items:center;justify-content:center;
}
.nb-tool-name { font-size:13px;font-weight:600;color:#111827; }
.nb-tool-sub  { font-size:11px;color:#6b7280; }
</style>
