<!-- Board: <?= htmlspecialchars($board['name']) ?> -->
<div class="flex flex-col h-full" id="boardApp" data-board-id="<?= $board['id'] ?>">

  <!-- Toolbar -->
  <div class="shrink-0 bg-white border-b border-gray-200 px-6 py-3 flex items-center gap-3 flex-wrap">
    <!-- View switcher -->
    <div class="flex items-center bg-gray-100 rounded-lg p-1 gap-1">
      <?php
        $views = ['kanban'=>'Kanban','list'=>'Lista','calendar'=>'Calendário','table'=>'Tabela'];
      ?>
      <?php foreach ($views as $v => $label): ?>
        <button data-view="<?= $v ?>"
                class="view-btn px-3 py-1.5 text-sm rounded-md font-medium transition <?= $view === $v ? 'bg-white shadow text-indigo-600' : 'text-gray-500 hover:text-gray-700' ?>"
                onclick="WorkdayBoard.switchView('<?= $v ?>')">  
          <?= $label ?>
        </button>
      <?php endforeach; ?>
    </div>

    <div class="flex-1"></div>

    <!-- Filtros -->
    <div class="flex items-center gap-2">
      <select id="filterPriority" onchange="WorkdayBoard.filter()" class="form-input py-1.5 text-sm">
        <option value="">Todas prioridades</option>
        <option value="urgent">Urgente</option>
        <option value="high">Alta</option>
        <option value="medium">Média</option>
        <option value="low">Baixa</option>
      </select>
      <button onclick="WorkdayBoard.openAutomationsPanel()" class="btn-secondary text-sm py-1.5">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
        Automações
      </button>

      <!-- Ferramentas dropdown -->
      <div class="relative" id="ferramentasDropdown">
        <button onclick="WorkdayTools.toggleDropdown()" class="btn-secondary text-sm py-1.5 flex items-center gap-1">
          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
          Ferramentas
          <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
        </button>
        <div id="ferramentasMenu" class="hidden absolute right-0 mt-1 bg-white rounded-xl shadow-lg border border-gray-100 z-50 overflow-hidden" style="width:190px">
          <div class="px-2 py-1.5">
            <p class="text-[9px] font-semibold text-gray-400 uppercase tracking-widest px-2 mb-1">Ferramentas</p>
            <button onclick="WorkdayTools.openSipocModal()" class="w-full text-left rounded-lg px-2 py-2 flex items-center gap-2.5 transition group hover:bg-indigo-50 focus:outline-none">
              <span class="shrink-0 w-7 h-7 rounded-md flex items-center justify-center font-black text-sm text-white" style="background:linear-gradient(135deg,#6366f1,#818cf8)">S</span>
              <span class="flex flex-col min-w-0">
                <span class="font-semibold text-sm text-gray-800 group-hover:text-indigo-700 leading-tight">SIPOC</span>
                <span class="text-[10px] text-gray-400 leading-none">Diagrama de processo</span>
              </span>
            </button>
          </div>
        </div>
      </div>
      <button onclick="WorkdayBoard.openNewItemModal()" class="btn-primary text-sm py-1.5">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
        Novo item
      </button>
    </div>
  </div>

  <!-- Board data (hidden, read by JS) -->
  <script id="boardData" type="application/json">
    <?= json_encode([
        'board'   => $board,
        'items'   => $items,
        'members' => $members,
    ], JSON_UNESCAPED_UNICODE) ?>
  </script>

  <!-- Views container -->
  <div id="viewContainer" class="flex-1 overflow-hidden">

    <!-- KANBAN VIEW -->
    <div id="viewKanban" class="view-panel h-full overflow-x-auto <?= $view !== 'kanban' ? 'hidden' : '' ?>">
      <div class="flex gap-4 p-6 h-full" id="kanbanBoard">
        <?php foreach ($board['groups'] as $group): ?>
          <div class="kanban-col flex-shrink-0 w-72 bg-gray-50 rounded-xl flex flex-col"
               data-group-id="<?= $group['id'] ?>" data-group-done="<?= $group['is_done'] ?>">

            <!-- Column header -->
            <div class="flex items-center justify-between px-4 py-3 rounded-t-xl"
                 style="border-top: 3px solid <?= htmlspecialchars($group['color']) ?>">
              <div class="flex items-center gap-2">
                <span class="w-2.5 h-2.5 rounded-full" style="background:<?= htmlspecialchars($group['color']) ?>"></span>
                <span class="font-semibold text-sm text-gray-700 group-name"><?= htmlspecialchars($group['name']) ?></span>
                <span class="text-xs text-gray-400 item-count">(0)</span>
              </div>
              <button onclick="WorkdayBoard.openNewItemModal(<?= $group['id'] ?>)" class="text-gray-400 hover:text-indigo-500">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
              </button>
            </div>

            <!-- Items drop zone -->
            <div class="kanban-items flex-1 overflow-y-auto px-3 pb-3 space-y-2 min-h-[80px]"
                 id="group-<?= $group['id'] ?>">
              <!-- Preenchido pelo JS -->
            </div>

            <!-- Quick add -->
            <div class="px-3 pb-3">
              <button onclick="WorkdayBoard.quickAdd(<?= $group['id'] ?>)"
                      class="w-full flex items-center gap-2 text-sm text-gray-400 hover:text-indigo-500 hover:bg-indigo-50 rounded-lg py-2 px-2 transition">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                Adicionar item
              </button>
            </div>
          </div>
        <?php endforeach; ?>

        <!-- Adicionar coluna -->
        <div class="flex-shrink-0 w-48 flex items-start pt-2">
          <button onclick="WorkdayBoard.openNewGroupModal()" class="w-full flex items-center gap-2 text-sm text-gray-400 hover:text-indigo-500 bg-gray-100 hover:bg-indigo-50 rounded-xl py-3 px-4 transition">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
            Nova coluna
          </button>
        </div>
      </div>
    </div>

    <!-- LIST VIEW -->
    <div id="viewList" class="view-panel h-full overflow-auto <?= $view !== 'list' ? 'hidden' : '' ?>">
      <div class="p-6">
        <?php foreach ($board['groups'] as $group): ?>
          <div class="mb-6" data-group-id="<?= $group['id'] ?>">
            <div class="flex items-center gap-3 mb-3">
              <span class="w-3 h-3 rounded-full" style="background:<?= htmlspecialchars($group['color']) ?>"></span>
              <h3 class="font-semibold text-gray-800 group-name"><?= htmlspecialchars($group['name']) ?></h3>
              <span class="text-xs text-gray-400 item-count">(0)</span>
            </div>
            <div class="bg-white rounded-xl border border-gray-200 overflow-hidden list-items" id="list-group-<?= $group['id'] ?>">
              <!-- preenchido pelo JS -->
            </div>
            <button onclick="WorkdayBoard.quickAdd(<?= $group['id'] ?>)"
                    class="mt-2 flex items-center gap-1 text-sm text-gray-400 hover:text-indigo-500 ml-6 transition">
              <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
              Adicionar
            </button>
          </div>
        <?php endforeach; ?>
      </div>
    </div>

    <!-- TABLE VIEW -->
    <div id="viewTable" class="view-panel h-full overflow-auto <?= $view !== 'table' ? 'hidden' : '' ?>">
      <div class="p-6">
        <table class="w-full text-sm" id="tableView">
          <thead class="bg-gray-50 text-left sticky top-0">
            <tr>
              <th class="px-4 py-3 font-semibold text-gray-600 w-8"></th>
              <th class="px-4 py-3 font-semibold text-gray-600">Título</th>
              <th class="px-4 py-3 font-semibold text-gray-600">Status</th>
              <th class="px-4 py-3 font-semibold text-gray-600">Prioridade</th>
              <th class="px-4 py-3 font-semibold text-gray-600">Responsável</th>
              <th class="px-4 py-3 font-semibold text-gray-600">Prazo</th>
              <?php foreach ($board['fields'] as $f): ?>
                <th class="px-4 py-3 font-semibold text-gray-600"><?= htmlspecialchars($f['name']) ?></th>
              <?php endforeach; ?>
            </tr>
          </thead>
          <tbody id="tableBody" class="divide-y divide-gray-100 bg-white">
            <!-- preenchido pelo JS -->
          </tbody>
        </table>
      </div>
    </div>

    <!-- CALENDAR VIEW -->
    <div id="viewCalendar" class="view-panel h-full overflow-auto <?= $view !== 'calendar' ? 'hidden' : '' ?>">
      <div id="calendarContainer" class="p-6"></div>
    </div>

    <!-- SIPOC VIEW removida — agora via modal Ferramentas -->
    <?php if (false): ?>
    <!-- SIPOC VIEW (desabilitado) -->
    <?php
      $sipocRows  = $toolData['rows'] ?? array_fill(0, 5, ['','','','','']);
      $sipocTitle = htmlspecialchars($toolData['process_title'] ?? '');
      $sipocCols  = [
          ['bg'=>'#2d3748','letter'=>'S','label'=>'FORNECEDORES','sub'=>'quem fornece entradas?'],
          ['bg'=>'#4a5568','letter'=>'I','label'=>'ENTRADA',      'sub'=>'o que é fornecido?'],
          ['bg'=>'#2d3748','letter'=>'P','label'=>'PROCESSO',     'sub'=>'etapas que convertem in → out'],
          ['bg'=>'#4a5568','letter'=>'O','label'=>'SAÍDA',        'sub'=>'resultado do processo'],
          ['bg'=>'#718096','letter'=>'C','label'=>'CLIENTE',      'sub'=>'quem recebe a saída?'],
      ];
    ?>
    <div id="viewSipoc" class="view-panel h-full overflow-auto <?= $view !== 'sipoc' ? 'hidden' : '' ?>">
      <div class="sipoc-inline-wrapper">

        <!-- Ações topo -->
        <div class="sipoc-inline-bar">
          <span class="sipoc-badge">SIPOC</span>
          <div style="flex:1"></div>
          <button class="btn-secondary text-sm" onclick="window.print()">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/></svg>
            Imprimir
          </button>
          <button class="btn-primary text-sm" onclick="SipocEditor.save()">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-3m-1 4l-3 3m0 0l-3-3m3 3V4"/></svg>
            Salvar
          </button>
        </div>

        <!-- Diagrama -->
        <div class="sipoc-diagram" id="sipocDiagram">
          <div class="sipoc-diagram-title">MODELO DE DIAGRAMA SIPOC</div>

          <!-- Letras -->
          <div class="sipoc-letters-row">
            <?php foreach ($sipocCols as $col): ?>
              <div class="sipoc-letter-cell" style="background:<?= $col['bg'] ?>">
                <span class="sipoc-letter"><?= $col['letter'] ?></span>
              </div>
            <?php endforeach; ?>
          </div>

          <!-- Cabeçalhos -->
          <div class="sipoc-header-row">
            <?php foreach ($sipocCols as $col): ?>
              <div class="sipoc-header-cell" style="background:<?= $col['bg'] ?>">
                <span class="sipoc-col-label"><?= $col['label'] ?></span>
                <span class="sipoc-col-sub"><?= htmlspecialchars($col['sub']) ?></span>
              </div>
            <?php endforeach; ?>
          </div>

          <!-- Título do processo -->
          <div class="sipoc-process-title-row">
            <span class="sipoc-process-title-label">TÍTULO DO PROCESSO:</span>
            <input type="text" id="sipocProcessTitle" class="sipoc-process-title-input"
                   placeholder="Digite o título do processo..." value="<?= $sipocTitle ?>"/>
          </div>

          <!-- Cabeçalho da tabela -->
          <div class="sipoc-table-header">
            <?php foreach ($sipocCols as $col): ?>
              <div class="sipoc-th"><?= $col['label'] ?></div>
            <?php endforeach; ?>
          </div>

          <!-- Linhas -->
          <div id="sipocRows">
            <?php foreach ($sipocRows as $ri => $row): ?>
              <div class="sipoc-row" data-row="<?= $ri ?>">
                <?php for ($ci = 0; $ci < 5; $ci++): ?>
                  <div class="sipoc-cell <?= $ci % 2 !== 0 ? 'sipoc-cell-alt' : '' ?>">
                    <textarea class="sipoc-cell-input" data-row="<?= $ri ?>" data-col="<?= $ci ?>" rows="2"
                    ><?= htmlspecialchars($row[$ci] ?? '') ?></textarea>
                  </div>
                <?php endfor; ?>
                <button class="sipoc-row-del" onclick="SipocEditor.removeRow(<?= $ri ?>)" title="Remover linha">
                  <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
              </div>
            <?php endforeach; ?>
          </div>

          <!-- Adicionar linha -->
          <div class="sipoc-add-row">
            <button onclick="SipocEditor.addRow()" class="sipoc-add-row-btn">
              <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
              Adicionar linha
            </button>
          </div>
        </div><!-- /.sipoc-diagram -->
      </div><!-- /.sipoc-inline-wrapper -->
    </div><!-- #viewSipoc -->

    <!-- Scripts e estilos SIPOC embutidos -->
    <script>
    const SipocEditor = (() => {
      const BOARD_ID = <?= $board['id'] ?>;
      const API_URL  = '<?= APP_URL ?>/boards/' + BOARD_ID + '/tool';
      const csrf     = () => document.querySelector('meta[name="csrf-token"]')?.content ?? '';

      function getRows() {
        const rows = [];
        document.querySelectorAll('#sipocRows .sipoc-row').forEach(rowEl => {
          const cells = [];
          rowEl.querySelectorAll('.sipoc-cell-input').forEach(ta => cells.push(ta.value));
          rows.push(cells);
        });
        return rows;
      }

      function reindexRows() {
        document.querySelectorAll('#sipocRows .sipoc-row').forEach((rowEl, i) => {
          rowEl.dataset.row = i;
          rowEl.querySelector('.sipoc-row-del').setAttribute('onclick', `SipocEditor.removeRow(${i})`);
        });
      }

      function addRow() {
        const container = document.getElementById('sipocRows');
        const idx = container.querySelectorAll('.sipoc-row').length;
        const div = document.createElement('div');
        div.className = 'sipoc-row';
        div.dataset.row = idx;
        let inner = '';
        for (let ci = 0; ci < 5; ci++) {
          inner += `<div class="sipoc-cell${ci % 2 !== 0 ? ' sipoc-cell-alt' : ''}">
            <textarea class="sipoc-cell-input" data-row="${idx}" data-col="${ci}" rows="2"></textarea></div>`;
        }
        inner += `<button class="sipoc-row-del" onclick="SipocEditor.removeRow(${idx})" title="Remover linha">
          <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg></button>`;
        div.innerHTML = inner;
        container.appendChild(div);
        div.querySelector('textarea').focus();
      }

      function removeRow(idx) {
        const rowEl = document.querySelector(`#sipocRows .sipoc-row[data-row="${idx}"]`);
        if (!rowEl) return;
        if (document.querySelectorAll('#sipocRows .sipoc-row').length <= 1) {
          WorkdayApp.toast('O diagrama precisa ter ao menos uma linha.', 'error'); return;
        }
        rowEl.remove();
        reindexRows();
      }

      async function save() {
        const btn = document.querySelector('#viewSipoc .btn-primary');
        if (btn) { btn.disabled = true; btn.textContent = 'Salvando...'; }
        try {
          const payload = {
            process_title: document.getElementById('sipocProcessTitle').value.trim(),
            rows: getRows(),
          };
          const res = await fetch(API_URL, {
            method: 'POST',
            headers: { 'Content-Type':'application/json', 'X-CSRF-Token': csrf(), 'X-Requested-With':'XMLHttpRequest' },
            body: JSON.stringify(payload),
          });
          const json = await res.json();
          if (!res.ok) throw new Error(json.error ?? 'Erro ao salvar');
          WorkdayApp.toast('SIPOC salvo com sucesso!', 'success');
        } catch (e) {
          WorkdayApp.toast(e.message, 'error');
        } finally {
          if (btn) { btn.disabled = false; btn.innerHTML = `<svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-3m-1 4l-3 3m0 0l-3-3m3 3V4"/></svg> Salvar`; }
        }
      }

      document.addEventListener('keydown', e => {
        if ((e.ctrlKey || e.metaKey) && e.key === 's' && document.getElementById('viewSipoc') && !document.getElementById('viewSipoc').classList.contains('hidden')) {
          e.preventDefault(); save();
        }
      });

      return { addRow, removeRow, save };
    })();
    </script>

    <style>
    .sipoc-inline-wrapper { padding: 20px 24px; max-width: 1200px; margin: 0 auto; }
    .sipoc-inline-bar {
      display:flex; align-items:center; gap:8px; margin-bottom:16px;
    }
    /* Reutiliza os estilos da view standalone */
    .sipoc-diagram { background:#fff; border-radius:12px; box-shadow:0 1px 4px rgba(0,0,0,.08); overflow:hidden; }
    .sipoc-diagram-title { font-size:15px;font-weight:700;letter-spacing:.06em;color:#6366f1;padding:16px 20px 12px;border-bottom:1px solid #f1f5f9; }
    .sipoc-letters-row { display:grid; grid-template-columns:repeat(5,1fr); }
    .sipoc-letter-cell { display:flex;align-items:center;justify-content:center;padding:20px 8px; }
    .sipoc-letter { font-size:52px;font-weight:900;color:#fff;line-height:1; }
    .sipoc-header-row { display:grid;grid-template-columns:repeat(5,1fr); }
    .sipoc-header-cell { display:flex;flex-direction:column;align-items:center;justify-content:center;padding:12px 8px;text-align:center;gap:4px; }
    .sipoc-col-label { font-size:11px;font-weight:700;letter-spacing:.1em;color:#fff; }
    .sipoc-col-sub { font-size:10px;color:rgba(255,255,255,.7);line-height:1.3; }
    .sipoc-process-title-row { display:flex;align-items:center;gap:10px;padding:10px 16px;background:#fffbeb;border-top:1px solid #fde68a;border-bottom:1px solid #fde68a; }
    .sipoc-process-title-label { font-size:11px;font-weight:700;color:#92400e;letter-spacing:.06em;white-space:nowrap; }
    .sipoc-process-title-input { flex:1;border:none;background:transparent;font-size:13px;font-weight:500;color:#1e293b;outline:none;padding:0; }
    .sipoc-process-title-input::placeholder { color:#d1d5db; }
    .sipoc-table-header { display:grid;grid-template-columns:repeat(5,1fr);background:#1e293b; }
    .sipoc-th { padding:8px 12px;font-size:10px;font-weight:700;letter-spacing:.1em;color:#e2e8f0;text-align:center;border-left:1px solid rgba(255,255,255,.08); }
    .sipoc-th:first-child { border-left:none; }
    .sipoc-row { display:grid;grid-template-columns:repeat(5,1fr) 28px;border-bottom:1px solid #e2e8f0; }
    .sipoc-row:last-child { border-bottom:none; }
    .sipoc-cell { padding:4px;background:#fff;border-left:1px solid #e2e8f0; }
    .sipoc-cell:first-child { border-left:none; }
    .sipoc-cell-alt { background:#f8fafc; }
    .sipoc-cell-input { width:100%;resize:none;border:none;background:transparent;font-size:12.5px;color:#1e293b;padding:6px 8px;outline:none;line-height:1.5;font-family:inherit; }
    .sipoc-cell-input:focus { background:#eff6ff;border-radius:4px; }
    .sipoc-row-del { display:flex;align-items:center;justify-content:center;background:none;border:none;color:#cbd5e1;cursor:pointer;transition:color .15s; }
    .sipoc-row-del:hover { color:#ef4444; }
    .sipoc-add-row { padding:10px 14px;border-top:1px solid #e2e8f0;background:#f8fafc; }
    .sipoc-add-row-btn { display:flex;align-items:center;gap:6px;font-size:12.5px;font-weight:500;color:#6366f1;background:none;border:none;cursor:pointer;padding:4px 6px;border-radius:6px;transition:background .15s; }
    .sipoc-add-row-btn:hover { background:#eef2ff; }
    .sipoc-badge { font-size:11px;font-weight:700;letter-spacing:.08em;background:#6366f1;color:#fff;padding:3px 10px;border-radius:999px; }
    </style>
    <?php endif; /* fim bloco desabilitado */ ?>

  </div><!-- /#viewContainer -->
</div><!-- /#boardApp -->

<!-- Estilos SIPOC (usados nos modais de ferramenta) -->
<style>
.sipoc-diagram { background:#fff; border-radius:12px; box-shadow:0 1px 4px rgba(0,0,0,.08); overflow:hidden; }
.sipoc-diagram-title { font-size:15px;font-weight:700;letter-spacing:.06em;color:#6366f1;padding:16px 20px 12px;border-bottom:1px solid #f1f5f9; }
.sipoc-letters-row { display:grid; grid-template-columns:repeat(5,1fr); }
.sipoc-letter-cell { display:flex;align-items:center;justify-content:center;padding:20px 8px; }
.sipoc-letter { font-size:52px;font-weight:900;color:#fff;line-height:1; }
.sipoc-header-row { display:grid;grid-template-columns:repeat(5,1fr); }
.sipoc-header-cell { display:flex;flex-direction:column;align-items:center;justify-content:center;padding:12px 8px;text-align:center;gap:4px; }
.sipoc-col-label { font-size:11px;font-weight:700;letter-spacing:.1em;color:#fff; }
.sipoc-col-sub { font-size:10px;color:rgba(255,255,255,.7);line-height:1.3; }
.sipoc-process-title-row { display:flex;align-items:center;gap:10px;padding:10px 16px;background:#fffbeb;border-top:1px solid #fde68a;border-bottom:1px solid #fde68a; }
.sipoc-process-title-label { font-size:11px;font-weight:700;color:#92400e;letter-spacing:.06em;white-space:nowrap; }
.sipoc-process-title-input { flex:1;border:none;background:transparent;font-size:13px;font-weight:500;color:#1e293b;outline:none;padding:0; }
.sipoc-process-title-input::placeholder { color:#d1d5db; }
.sipoc-table-header { display:grid;grid-template-columns:repeat(5,1fr);background:#1e293b; }
.sipoc-th { padding:8px 12px;font-size:10px;font-weight:700;letter-spacing:.1em;color:#e2e8f0;text-align:center;border-left:1px solid rgba(255,255,255,.08); }
.sipoc-th:first-child { border-left:none; }
.sipoc-row { display:grid;grid-template-columns:repeat(5,1fr) 28px;border-bottom:1px solid #e2e8f0; }
.sipoc-row:last-child { border-bottom:none; }
.sipoc-cell { padding:4px;background:#fff;border-left:1px solid #e2e8f0; }
.sipoc-cell:first-child { border-left:none; }
.sipoc-cell-alt { background:#f8fafc; }
.sipoc-cell-input { width:100%;resize:none;border:none;background:transparent;font-size:12.5px;color:#1e293b;padding:6px 8px;outline:none;line-height:1.5;font-family:inherit; }
.sipoc-cell-input:focus { background:#eff6ff;border-radius:4px; }
.sipoc-row-del { display:flex;align-items:center;justify-content:center;background:none;border:none;color:#cbd5e1;cursor:pointer;transition:color .15s; }
.sipoc-row-del:hover { color:#ef4444; }
.sipoc-add-row { padding:10px 14px;border-top:1px solid #e2e8f0;background:#f8fafc; }
.sipoc-add-row-btn { display:flex;align-items:center;gap:6px;font-size:12.5px;font-weight:500;color:#6366f1;background:none;border:none;cursor:pointer;padding:4px 6px;border-radius:6px;transition:background .15s; }
.sipoc-add-row-btn:hover { background:#eef2ff; }
.sipoc-item-badge { display:inline-flex;align-items:center;gap:4px;background:#eef2ff;color:#6366f1;font-size:10px;font-weight:700;letter-spacing:.06em;padding:2px 7px;border-radius:999px; }
</style>

<!-- Templates de modal -->
<template id="newItemModalTpl">
  <div class="p-6 space-y-4">
    <h3 class="text-lg font-semibold text-gray-900">Novo Item</h3>
    <form id="newItemForm">
      <div id="newItemGroupWrapper">
        <input type="hidden" name="group_id" id="newItemGroupId"/>
      </div>
      <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">Título*</label>
        <input type="text" name="title" required class="form-input" placeholder="Descreva a tarefa" autofocus/>
      </div>
      <div class="grid grid-cols-2 gap-4 mt-4">
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Prioridade</label>
          <select name="priority" class="form-input">
            <option value="none">Nenhuma</option>
            <option value="low">Baixa</option>
            <option value="medium">Média</option>
            <option value="high">Alta</option>
            <option value="urgent">Urgente</option>
          </select>
        </div>
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Prazo</label>
          <input type="date" name="due_date" class="form-input"/>
        </div>
      </div>
      <div class="mt-4">
        <label class="block text-sm font-medium text-gray-700 mb-1">Responsáveis</label>
        <div id="assigneeSelect" class="flex flex-wrap gap-2"></div>
      </div>
      <div class="mt-4">
        <label class="block text-sm font-medium text-gray-700 mb-1">Descrição</label>
        <textarea name="description" rows="3" class="form-input" placeholder="Detalhes opcionais..."></textarea>
      </div>
      <div class="flex justify-end gap-3 mt-6">
        <button type="button" onclick="WorkdayApp.closeModal()" class="btn-secondary">Cancelar</button>
        <button type="submit" class="btn-primary">Criar item</button>
      </div>
    </form>
  </div>
</template>

<template id="itemDetailTpl">
  <div class="flex flex-col max-h-[90vh]">
    <!-- Cabeçalho com botão fechar -->
    <div class="flex items-center justify-between px-6 py-3 border-b border-gray-100 shrink-0">
      <span class="font-semibold text-gray-700 text-sm">Detalhes do item</span>
      <button onclick="WorkdayApp.closeModal()" class="text-gray-400 hover:text-gray-700 text-2xl leading-none font-light" title="Fechar">&times;</button>
    </div>
    <!-- Corpo -->
    <div class="flex flex-col md:flex-row flex-1 overflow-hidden">
    <!-- Main -->
    <div class="flex-1 overflow-y-auto p-6 border-r border-gray-100">
      <input id="detailTitle" class="text-xl font-bold text-gray-900 w-full border-none outline-none bg-transparent" data-field="title"/>
      <div id="detailBreadcrumb" class="text-sm text-gray-400 mt-1 mb-4"></div>
      <textarea id="detailDescription" rows="4" placeholder="Adicionar descrição..."
        class="w-full text-sm text-gray-600 border border-gray-200 rounded-lg p-3 focus:ring-2 focus:ring-indigo-300 focus:border-transparent resize-none" data-field="description"></textarea>

      <!-- Subtarefas -->
      <div class="mt-6">
        <h4 class="font-semibold text-sm text-gray-700 mb-3">Subtarefas</h4>
        <div id="subtaskList" class="space-y-1"></div>
        <div class="flex gap-2 mt-2">
          <input type="text" id="newSubtask" placeholder="Add subtarefa…" class="form-input flex-1 text-sm py-1.5"/>
          <button onclick="WorkdayItemDetail.addSubtask()" class="btn-primary text-sm py-1.5 px-3">+</button>
        </div>
      </div>

      <!-- Comentários -->
      <div class="mt-6">
        <h4 class="font-semibold text-sm text-gray-700 mb-3">Comentários</h4>
        <div id="commentList" class="space-y-3 mb-3"></div>
        <div class="flex gap-2">
          <textarea id="newComment" rows="2" placeholder="Adicionar comentário... (@mencionar)" class="form-input flex-1 text-sm"></textarea>
          <button onclick="WorkdayItemDetail.addComment()" class="btn-primary self-end text-sm py-2 px-3">Enviar</button>
        </div>
      </div>

      <!-- Diagrama SIPOC (visível apenas em itens SIPOC) -->
      <div id="detailSipocSection" class="mt-6 hidden">
        <button onclick="WorkdayItemDetail.openSipocWide()"
                class="w-full flex items-center gap-3 px-3 py-2.5 rounded-xl border border-indigo-100 bg-indigo-50 hover:bg-indigo-100 transition text-left group">
          <span class="sipoc-item-badge shrink-0">SIPOC</span>
          <span class="flex-1 text-sm font-semibold text-indigo-700" id="detailSipocAccordionTitle">Diagrama SIPOC</span>
          <span class="text-[11px] text-indigo-400 group-hover:text-indigo-600 transition">Abrir diagrama →</span>
        </button>
      </div>
    </div>

    <!-- Sidebar detalhes -->
    <div class="md:w-56 p-4 space-y-4 overflow-y-auto shrink-0 bg-gray-50 rounded-br-2xl">
      <div>
        <label class="detail-label">Status</label>
        <select id="detailGroup" class="form-input text-sm"></select>
      </div>
      <div>
        <label class="detail-label">Prioridade</label>
        <select id="detailPriority" class="form-input text-sm">
          <option value="none">Nenhuma</option>
          <option value="low">Baixa</option>
          <option value="medium">Média</option>
          <option value="high">Alta</option>
          <option value="urgent">Urgente</option>
        </select>
      </div>
      <div>
        <label class="detail-label">Prazo</label>
        <input type="date" id="detailDueDate" class="form-input text-sm"/>
      </div>
      <div>
        <label class="detail-label">Responsáveis</label>
        <div id="detailAssignees" class="flex flex-wrap gap-1 mt-1"></div>
        <select id="addAssigneeSelect" class="form-input text-sm mt-1"><option value="">+ Adicionar</option></select>
      </div>
      <div>
        <label class="detail-label">Anexos</label>
        <div id="attachmentList" class="space-y-1 mt-1"></div>
        <input type="file" id="fileInput" class="hidden" multiple/>
        <button onclick="document.getElementById('fileInput').click()" class="btn-secondary text-xs mt-1 w-full">Upload arquivo</button>
      </div>
      <div>
        <label class="detail-label">Histórico</label>
        <div id="activityLog" class="text-xs text-gray-500 space-y-1 max-h-32 overflow-y-auto mt-1"></div>
      </div>
      <button onclick="WorkdayItemDetail.archiveItem()" class="btn-secondary text-xs w-full text-red-500 hover:bg-red-50">Arquivar</button>
      <button onclick="WorkdayApp.closeModal()" class="btn-primary text-xs w-full mt-1">Salvar e Fechar</button>
    </div>
    </div><!-- fim corpo -->
  </div>
</template>
