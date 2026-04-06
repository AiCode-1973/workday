<!-- Board: <?= htmlspecialchars($board['name']) ?> -->
<div class="flex flex-col h-full" id="boardApp" data-board-id="<?= $board['id'] ?>">

  <!-- Toolbar -->
  <div class="shrink-0 bg-white border-b border-gray-200 px-6 py-3 flex items-center gap-3 flex-wrap">
    <!-- View switcher -->
    <div class="flex items-center bg-gray-100 rounded-lg p-1 gap-1">
      <?php foreach (['kanban'=>'Kanban','list'=>'Lista','calendar'=>'Calendário','table'=>'Tabela'] as $v => $label): ?>
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

  </div>
</div>

<!-- Templates de modal -->
<template id="newItemModalTpl">
  <div class="p-6 space-y-4">
    <h3 class="text-lg font-semibold text-gray-900">Novo Item</h3>
    <form id="newItemForm">
      <input type="hidden" name="group_id" id="newItemGroupId"/>
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
