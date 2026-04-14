/**
 * Workday — JavaScript principal
 * Gerencia: BoardView, KanbanDnD, ItemDetail, Comentários,
 * Notificações, Automações, Calendário e WebSocket
 */

'use strict';

// ============================================================
// App Core
// ============================================================
const WorkdayApp = (() => {
  const csrf = () => document.querySelector('meta[name="csrf-token"]')?.content ?? '';

  async function api(method, url, body, opts = {}) {
    const isFormData = body instanceof FormData;
    const res = await fetch(url, {
      method,
      headers: {
        'X-Requested-With': 'XMLHttpRequest',
        'X-CSRF-Token': csrf(),
        ...(isFormData ? {} : { 'Content-Type': 'application/json' }),
      },
      body: body
        ? isFormData ? body : JSON.stringify(body)
        : undefined,
      ...opts,
    });
    if (!res.ok) {
      const err = await res.json().catch(() => ({ error: res.statusText }));
      throw new Error(err.error ?? 'Erro na requisição');
    }
    return res.json();
  }

  function openModal(html) {
    const modal = document.getElementById('modal');
    const content = document.getElementById('modalContent');
    content.innerHTML = html;
    modal.classList.remove('hidden');
    modal.addEventListener('click', e => {
      if (e.target === modal) closeModal();
    }, { once: true });
  }

  function openWideModal(html) {
    const modal = document.getElementById('modalWide');
    const content = document.getElementById('modalWideContent');
    content.innerHTML = html;
    modal.classList.remove('hidden');
    modal.addEventListener('click', e => {
      if (e.target === modal) closeWideModal();
    }, { once: true });
  }

  function closeModal() {
    document.getElementById('modal')?.classList.add('hidden');
  }

  function closeWideModal() {
    document.getElementById('modalWide')?.classList.add('hidden');
  }

  function toast(msg, type = 'default') {
    const c = document.getElementById('toastContainer');
    const t = document.createElement('div');
    t.className = `toast ${type}`;
    t.innerHTML = `
      <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        ${type === 'success'
          ? '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>'
          : type === 'error'
            ? '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>'
            : '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M12 2a10 10 0 100 20A10 10 0 0012 2z"/>'}
      </svg>
      <span>${msg}</span>`;
    c.appendChild(t);
    setTimeout(() => t.remove(), 3500);
  }

  function formatDate(d) {
    if (!d) return '—';
    const dt = new Date(d + 'T00:00:00');
    return dt.toLocaleDateString('pt-BR', { day: '2-digit', month: '2-digit' });
  }

  function priorityLabel(p) {
    const map = { none: 'Nenhuma', low: 'Baixa', medium: 'Média', high: 'Alta', urgent: 'Urgente' };
    return map[p] ?? p;
  }

  function priorityColor(p) {
    const map = { none: '#94a3b8', low: '#22c55e', medium: '#f59e0b', high: '#ef4444', urgent: '#7c3aed' };
    return map[p] ?? '#94a3b8';
  }

  function avatarHtml(name, avatar) {
    if (avatar) {
      return `<img src="${APP_URL}/uploads/${avatar}" class="av" title="${name}"/>`;
    }
    return `<div class="av" title="${name}" style="background:#6366f1">${name.charAt(0).toUpperCase()}</div>`;
  }

  return { api, openModal, openWideModal, closeModal, closeWideModal, toast, formatDate, priorityLabel, priorityColor, avatarHtml, csrf };
})();

// ============================================================
// Board principal (views)
// ============================================================
const WorkdayBoard = (() => {
  let STATE = { board: null, items: [], members: [], currentView: 'kanban' };

  function init() {
    const el = document.getElementById('boardData');
    if (!el) return;
    const data = JSON.parse(el.textContent);
    STATE.board   = data.board;
    STATE.items   = data.items;
    STATE.members = data.members;
    STATE.currentView = document.getElementById('viewKanban')?.classList.contains('hidden') === false ? 'kanban' : 'list';

    renderCurrentView();
    initDragDrop();
    initNotifications();
    initWS();
  }

  function renderCurrentView() {
    if (STATE.currentView === 'kanban') renderKanban();
    if (STATE.currentView === 'list')   renderList();
    if (STATE.currentView === 'table')  renderTable();
    if (STATE.currentView === 'calendar') renderCalendar();
  }

  function addItem(item) {
    STATE.items.push(item);
    renderCurrentView();
  }

  function switchView(view) {
    ['kanban','list','table','calendar'].forEach(v => {
      document.getElementById(`view${v.charAt(0).toUpperCase()+v.slice(1)}`)?.classList.toggle('hidden', v !== view);
    });
    document.querySelectorAll('.view-btn').forEach(btn => {
      btn.classList.toggle('bg-white', btn.dataset.view === view);
      btn.classList.toggle('shadow', btn.dataset.view === view);
      btn.classList.toggle('text-indigo-600', btn.dataset.view === view);
      btn.classList.toggle('text-gray-500', btn.dataset.view !== view);
    });
    STATE.currentView = view;
    renderCurrentView();
  }

  // ── Kanban ──────────────────────────────────────────────

  function renderKanban() {
    const groups = STATE.board.groups;
    groups.forEach(g => {
      const container = document.getElementById(`group-${g.id}`);
      if (!container) return;
      const groupItems = STATE.items
        .filter(i => i.group_id == g.id)
        .sort((a, b) => (a.position ?? 0) - (b.position ?? 0));
      container.innerHTML = groupItems.map(renderKanbanCard).join('');

      // Atualiza contador
      const col   = container.closest('.kanban-col');
      const count = col?.querySelector('.item-count');
      if (count) count.textContent = `(${groupItems.length})`;

      // Event listeners nos cards
      container.querySelectorAll('.kanban-card').forEach(card => {
        card.addEventListener('click',       () => openItemDetail(+card.dataset.id));
        card.addEventListener('dragstart',   e  => onDragStart(e, card));
        card.addEventListener('dragend',     e  => onDragEnd(e, card));
      });
    });
  }

  function renderKanbanCard(item) {
    const avatars = (item.assignees || []).map(a => WorkdayApp.avatarHtml(a.name, a.avatar)).join('');
    const overdue = item.due_date && new Date(item.due_date) < new Date() && !item.done_at;
    const sipocBadge = item.tool_type === 'sipoc'
      ? '<span class="sipoc-item-badge mt-1">SIPOC</span>'
      : '';
    return `
      <div class="kanban-card" draggable="true" data-id="${item.id}" data-group="${item.group_id}">
        <div class="flex items-start justify-between gap-2 mb-2">
          <p class="font-medium text-gray-900 text-sm leading-snug flex-1">${escHtml(item.title)}</p>
          <div class="flex flex-col items-end gap-1 shrink-0">
            <span class="priority-dot priority-${item.priority}"></span>
            ${sipocBadge}
          </div>
        </div>
        ${item.description ? `<p class="text-xs text-gray-400 line-clamp-2 mb-2">${escHtml(item.description)}</p>` : ''}
        <div class="flex items-center justify-between mt-2">
          <div class="avatar-stack">${avatars}</div>
          <div class="flex items-center gap-2 text-xs text-gray-400">
            ${item.comment_count > 0 ? `<span title="Comentários">💬 ${item.comment_count}</span>` : ''}
            ${item.subtask_count > 0 ? `<span title="Subtarefas">✓ ${item.subtask_count}</span>` : ''}
            ${item.due_date ? `<span class="${overdue ? 'text-red-500 font-medium' : ''}">${WorkdayApp.formatDate(item.due_date)}</span>` : ''}
          </div>
        </div>
      </div>`;
  }

  // ── List ─────────────────────────────────────────────────

  function renderList() {
    STATE.board.groups.forEach(g => {
      const container = document.getElementById(`list-group-${g.id}`);
      if (!container) return;
      const groupItems = STATE.items.filter(i => i.group_id == g.id);

      container.innerHTML = `
        <div class="list-item list-header">
          <div></div>
          <div>Título</div><div>Status</div><div>Prioridade</div><div>Responsável</div><div>Prazo</div>
        </div>
        ${groupItems.map(renderListRow).join('')}
      `;
      container.querySelectorAll('.list-item[data-id]').forEach(row => {
        row.addEventListener('click', () => openItemDetail(+row.dataset.id));
      });

      const count = document.querySelector(`[data-group-id="${g.id}"] .item-count`);
      if (count) count.textContent = `(${groupItems.length})`;
    });
  }

  function renderListRow(item) {
    const avatars = (item.assignees || []).map(a => WorkdayApp.avatarHtml(a.name, a.avatar)).join('');
    return `
      <div class="list-item" data-id="${item.id}">
        <div class="flex items-center justify-center">
          <input type="checkbox" ${item.done_at ? 'checked' : ''} class="rounded border-gray-300 text-indigo-600 cursor-pointer"
                 onclick="event.stopPropagation();WorkdayBoard.toggleDone(${item.id},this.checked)"/>
        </div>
        <div class="item-title truncate">${escHtml(item.title)}</div>
        <div><span class="badge" style="background:${item.group_color}20;color:${item.group_color}">${escHtml(item.group_name)}</span></div>
        <div><span class="badge priority-badge-${item.priority}">${WorkdayApp.priorityLabel(item.priority)}</span></div>
        <div><div class="avatar-stack">${avatars}</div></div>
        <div class="text-xs text-gray-500">${WorkdayApp.formatDate(item.due_date)}</div>
      </div>`;
  }

  // ── Table ────────────────────────────────────────────────

  function renderTable() {
    const tbody = document.getElementById('tableBody');
    if (!tbody) return;
    tbody.innerHTML = STATE.items.map(item => {
      const avatars = (item.assignees || []).map(a => WorkdayApp.avatarHtml(a.name, a.avatar)).join('');
      return `
        <tr data-id="${item.id}">
          <td>
            <input type="checkbox" ${item.done_at ? 'checked' : ''} class="rounded border-gray-300 text-indigo-600"
                   onclick="event.stopPropagation();WorkdayBoard.toggleDone(${item.id},this.checked)"/>
          </td>
          <td class="title-cell" onclick="WorkdayBoard.openItemDetail(${item.id})">${escHtml(item.title)}</td>
          <td><span class="badge" style="background:${item.group_color}20;color:${item.group_color}">${escHtml(item.group_name)}</span></td>
          <td><span class="badge priority-badge-${item.priority}">${WorkdayApp.priorityLabel(item.priority)}</span></td>
          <td><div class="avatar-stack">${avatars}</div></td>
          <td class="text-xs text-gray-500">${WorkdayApp.formatDate(item.due_date)}</td>
        </tr>`;
    }).join('');
  }

  // ── Calendar ─────────────────────────────────────────────

  function renderCalendar() {
    const container = document.getElementById('calendarContainer');
    if (!container) return;

    const now     = new Date();
    const year    = now.getFullYear();
    const month   = now.getMonth();
    const first   = new Date(year, month, 1);
    const last    = new Date(year, month + 1, 0);
    const startDay = first.getDay(); // 0=dom

    const days = ['Dom','Seg','Ter','Qua','Qui','Sex','Sáb'];
    let html = `<div class="flex items-center justify-between mb-4">
      <h3 class="font-bold text-gray-900">${first.toLocaleString('pt-BR',{month:'long',year:'numeric'})}</h3>
    </div>
    <div class="cal-header">${days.map(d=>`<span>${d}</span>`).join('')}</div>
    <div class="cal-grid">`;

    // Células vazias antes do primeiro dia
    for (let i = 0; i < startDay; i++) html += `<div class="cal-cell other-month"></div>`;

    for (let d = 1; d <= last.getDate(); d++) {
      const dateStr  = `${year}-${String(month+1).padStart(2,'0')}-${String(d).padStart(2,'0')}`;
      const isToday  = d === now.getDate();
      const dayItems = STATE.items.filter(i => i.due_date === dateStr);

      html += `<div class="cal-cell${isToday?' today':''}">
        <div class="cal-day-num ${isToday?'cal-today-num':''}">${d}</div>
        ${dayItems.map(i=>`<div class="cal-item" onclick="WorkdayBoard.openItemDetail(${i.id})" title="${escHtml(i.title)}"
            style="background:${i.group_color || '#6366f1'}">${escHtml(i.title)}</div>`).join('')}
      </div>`;
    }

    html += `</div>`;
    container.innerHTML = html;
  }

  // ── Drag & Drop ──────────────────────────────────────────

  let draggedId = null;
  let placeholder = null;

  function initDragDrop() {
    document.addEventListener('dragover', onDragOver);
    document.addEventListener('drop',     onDrop);
    document.querySelectorAll('.kanban-items').forEach(zone => {
      zone.addEventListener('dragenter', onDragEnter);
      zone.addEventListener('dragleave', onDragLeave);
    });
  }

  function onDragStart(e, card) {
    draggedId = +card.dataset.id;
    card.classList.add('dragging');
    e.dataTransfer.effectAllowed = 'move';

    placeholder = document.createElement('div');
    placeholder.className = 'kanban-card drag-placeholder';
  }

  function onDragEnd(e, card) {
    card.classList.remove('dragging');
    placeholder?.remove();
    document.querySelectorAll('.kanban-items').forEach(z => z.classList.remove('drag-over'));
    document.querySelectorAll('.kanban-col').forEach(z => z.classList.remove('drag-over'));
  }

  function onDragEnter(e) {
    e.currentTarget.classList.add('drag-over');
    e.currentTarget.closest('.kanban-col')?.classList.add('drag-over');
  }
  function onDragLeave(e) {
    if (!e.currentTarget.contains(e.relatedTarget)) {
      e.currentTarget.classList.remove('drag-over');
      e.currentTarget.closest('.kanban-col')?.classList.remove('drag-over');
    }
  }

  function onDragOver(e) {
    const zone = e.target.closest('.kanban-items');
    if (!zone) return;
    e.preventDefault();
    e.dataTransfer.dropEffect = 'move';

    const cards = [...zone.querySelectorAll('.kanban-card:not(.dragging):not(.drag-placeholder)')];
    const after = getDragAfterElement(zone, e.clientY);
    if (after) {
      zone.insertBefore(placeholder, after);
    } else {
      zone.appendChild(placeholder);
    }
  }

  async function onDrop(e) {
    const zone = e.target.closest('.kanban-items');
    if (!zone || !draggedId) return;
    e.preventDefault();

    const groupId        = +zone.id.replace('group-', '');
    const placeholderIdx = placeholder ? [...zone.children].indexOf(placeholder) : zone.children.length;
    const position       = Math.max(0, placeholderIdx);

    placeholder?.remove();

    // ── Optimistic update: atualiza o STATE e re-renderiza ANTES da rede ──
    const item        = STATE.items.find(i => i.id === draggedId);
    const prevGroupId = item?.group_id;
    const prevPos     = item?.position;
    if (item) {
      item.group_id = groupId;
      item.position = position;
      const grp = STATE.board.groups.find(g => g.id === groupId);
      if (grp) { item.group_name = grp.name; item.group_color = grp.color; }
    }
    const capturedId = draggedId;
    draggedId = null;
    renderKanban(); // card aparece imediatamente na nova coluna

    try {
      await WorkdayApp.api('POST', `${APP_URL}/items/${capturedId}/move`, { group_id: groupId, position });
    } catch (err) {
      // Reverte o card para a posição original em caso de erro
      if (item) { item.group_id = prevGroupId; item.position = prevPos; }
      renderKanban();
      WorkdayApp.toast(err.message, 'error');
    }
  }

  function getDragAfterElement(container, y) {
    const draggableEls = [...container.querySelectorAll('.kanban-card:not(.dragging):not(.drag-placeholder)')];
    return draggableEls.reduce((closest, child) => {
      const box     = child.getBoundingClientRect();
      const offset  = y - box.top - box.height / 2;
      if (offset < 0 && offset > (closest.offset ?? -Infinity)) {
        return { offset, element: child };
      }
      return closest;
    }, {}).element;
  }

  // ── Item CRUD ─────────────────────────────────────────────

  function openNewItemModal(groupId) {
    const tpl = document.getElementById('newItemModalTpl');
    WorkdayApp.openModal(tpl.content.firstElementChild.outerHTML);

    if (groupId) {
      document.getElementById('newItemGroupId').value = groupId;
    } else {
      // Preenche com primeiro grupo
      const sel    = document.createElement('select');
      sel.name     = 'group_id';
      sel.id       = 'newItemGroupId';
      sel.className = 'form-input text-sm';
      STATE.board.groups.forEach(g => {
        const opt  = document.createElement('option');
        opt.value  = g.id;
        opt.textContent = g.name;
        sel.appendChild(opt);
      });
      const old = document.getElementById('newItemGroupId');
      old?.replaceWith(sel);
    }

    // Assignees
    const assigneeDiv = document.getElementById('assigneeSelect');
    STATE.members.forEach(m => {
      const lbl   = document.createElement('label');
      lbl.className = 'flex items-center gap-1 text-xs cursor-pointer select-none';
      lbl.innerHTML = `
        <input type="checkbox" name="assignees[]" value="${m.id}" class="rounded border-gray-300 text-indigo-600"/>
        ${WorkdayApp.avatarHtml(m.name, m.avatar)}
        <span>${escHtml(m.name)}</span>`;
      assigneeDiv?.appendChild(lbl);
    });

    document.getElementById('newItemForm')?.addEventListener('submit', async e => {
      e.preventDefault();
      const fd     = new FormData(e.target);
      const title  = fd.get('name') || fd.get('title');
      const gid    = fd.get('group_id') || groupId;
      const assignees = fd.getAll('assignees[]').map(Number);
      try {
        const res = await WorkdayApp.api('POST', `${APP_URL}/boards/${STATE.board.id}/items`, {
          title: e.target.querySelector('[name="title"]').value,
          group_id: +gid,
          priority: fd.get('priority') || 'none',
          due_date: fd.get('due_date') || null,
          description: fd.get('description') || null,
          assignees,
        });
        STATE.items.push({
          id: res.id,
          board_id: STATE.board.id,
          group_id: +gid,
          title: e.target.querySelector('[name="title"]').value,
          priority: fd.get('priority') || 'none',
          due_date: fd.get('due_date') || null,
          group_name: STATE.board.groups.find(g => g.id == gid)?.name ?? '',
          group_color: STATE.board.groups.find(g => g.id == gid)?.color ?? '#94a3b8',
          assignees: STATE.members.filter(m => assignees.includes(m.id)),
          comment_count: 0, subtask_count: 0, attachment_count: 0,
          position: 999,
        });
        renderCurrentView();
        WorkdayApp.closeModal();
        WorkdayApp.toast('Item criado', 'success');
      } catch (err) {
        WorkdayApp.toast(err.message, 'error');
      }
    });
  }

  function openItemDetail(id) {
    WorkdayItemDetail.open(id, STATE);
  }

  async function toggleDone(id, done) {
    const item  = STATE.items.find(i => i.id === id);
    if (!item) return;
    const doneGroup = STATE.board.groups.find(g => g.is_done);
    if (!doneGroup) { WorkdayApp.toast('Configure um status "Concluído" primeiro', 'warning'); return; }

    try {
      await WorkdayApp.api('PUT', `${APP_URL}/items/${id}`, {
        group_id: done ? doneGroup.id : item.group_id,
      });
      if (done) { item.group_id = doneGroup.id; item.group_name = doneGroup.name; item.group_color = doneGroup.color; item.done_at = new Date().toISOString(); }
      else item.done_at = null;
      renderCurrentView();
    } catch (err) {
      WorkdayApp.toast(err.message, 'error');
    }
  }

  function filter() {
    const priority = document.getElementById('filterPriority')?.value;
    // Recarrega via API com filtros
    const url = new URL(window.location.href);
    if (priority) url.searchParams.set('priority', priority);
    else url.searchParams.delete('priority');
    window.location.href = url.toString();
  }

  function quickAdd(groupId) {
    const zone = document.getElementById(`group-${groupId}`);
    if (!zone) return;
    const row = document.createElement('div');
    row.className = 'quick-add-row';
    row.innerHTML = `<input type="text" class="quick-add-input" placeholder="Nome do item… (Enter para salvar)" autofocus/>`;
    zone.appendChild(row);
    const inp = row.querySelector('input');
    inp.focus();
    inp.addEventListener('keydown', async e => {
      if (e.key === 'Enter') {
        const title = inp.value.trim();
        if (!title) { row.remove(); return; }
        try {
          const res = await WorkdayApp.api('POST', `${APP_URL}/boards/${STATE.board.id}/items`, {
            title, group_id: groupId, priority: 'none',
          });
          STATE.items.push({ id: res.id, board_id: STATE.board.id, group_id: groupId, title,
            priority: 'none', group_name: STATE.board.groups.find(g=>g.id==groupId)?.name??'',
            group_color: STATE.board.groups.find(g=>g.id==groupId)?.color??'#94a3b8',
            assignees: [], comment_count: 0, subtask_count: 0, position: 999 });
          row.remove();
          renderCurrentView();
          WorkdayApp.toast('Item adicionado', 'success');
        } catch (err) { WorkdayApp.toast(err.message, 'error'); }
      }
      if (e.key === 'Escape') row.remove();
    });
  }

  function openNewGroupModal() {
    WorkdayApp.openModal(`
      <div class="p-6">
        <h3 class="font-semibold text-gray-900 text-lg mb-4">Nova coluna</h3>
        <form id="newGroupForm" class="space-y-4">
          <div><label class="block text-sm font-medium text-gray-700 mb-1">Nome</label>
            <input type="text" name="name" required class="form-input" placeholder="Ex: Em revisão" autofocus/></div>
          <div><label class="block text-sm font-medium text-gray-700 mb-1">Cor</label>
            <input type="color" name="color" value="#94a3b8" class="h-10 w-full rounded-lg border border-gray-300 p-1"/></div>
          <div class="flex items-center gap-2">
            <input type="checkbox" name="is_done" id="isDone" class="rounded border-gray-300 text-indigo-600"/>
            <label for="isDone" class="text-sm text-gray-700">Marcar itens como concluídos ao mover para esta coluna</label>
          </div>
          <div class="flex justify-end gap-3">
            <button type="button" onclick="WorkdayApp.closeModal()" class="btn-secondary">Cancelar</button>
            <button type="submit" class="btn-primary">Criar</button>
          </div>
        </form>
      </div>`);

    document.getElementById('newGroupForm').addEventListener('submit', async e => {
      e.preventDefault();
      const fd = new FormData(e.target);
      try {
        const res = await WorkdayApp.api('POST', `${APP_URL}/boards/${STATE.board.id}/groups`, {
          name: fd.get('name'), color: fd.get('color'), is_done: fd.has('is_done') ? 1 : 0,
        });
        STATE.board.groups.push({ id: res.id, name: fd.get('name'), color: fd.get('color'), is_done: fd.has('is_done') ? 1 : 0, position: 999 });
        WorkdayApp.closeModal();
        WorkdayApp.toast('Coluna criada', 'success');
        window.location.reload();
      } catch (err) { WorkdayApp.toast(err.message, 'error'); }
    });
  }

  function openAutomationsPanel() {
    WorkdayAutomations.openPanel(STATE.board.id, STATE.board.groups, STATE.members);
  }

  // ── Notificações ──────────────────────────────────────────

  function initNotifications() {
    const btn     = document.getElementById('notifBtn');
    const dropdown= document.getElementById('notifDropdown');
    if (!btn) return;

    btn.addEventListener('click', e => {
      e.stopPropagation();
      dropdown.classList.toggle('hidden');
      if (!dropdown.classList.contains('hidden')) loadNotifications();
    });
    document.addEventListener('click', () => dropdown.classList.add('hidden'));

    document.getElementById('markAllReadBtn')?.addEventListener('click', async () => {
      await WorkdayApp.api('POST', `${APP_URL}/notifications/read-all`, {});
      document.getElementById('notifBadge').classList.add('hidden');
      loadNotifications();
    });

    // Poll a cada 30s
    setInterval(checkUnreadNotifs, 30000);
    checkUnreadNotifs();
  }

  async function checkUnreadNotifs() {
    try {
      const data = await WorkdayApp.api('GET', `${APP_URL}/notifications`);
      const badge = document.getElementById('notifBadge');
      if (data.unread > 0) {
        badge.textContent = data.unread > 9 ? '9+' : data.unread;
        badge.classList.remove('hidden');
      } else {
        badge.classList.add('hidden');
      }
    } catch {}
  }

  async function loadNotifications() {
    const list = document.getElementById('notifList');
    try {
      const data = await WorkdayApp.api('GET', `${APP_URL}/notifications`);
      list.innerHTML = data.data.length === 0
        ? '<p class="text-sm text-gray-400 text-center py-6">Nenhuma notificação</p>'
        : data.data.map(n => `
          <div class="flex gap-3 p-3 hover:bg-gray-50 cursor-pointer ${n.read_at ? 'opacity-60' : ''}"
               onclick="WorkdayNotif.markRead(${n.id});if('${n.link}')window.location='${APP_URL}${n.link}'">
            <div class="w-2 h-2 rounded-full mt-2 shrink-0 ${n.read_at ? 'bg-gray-200' : 'bg-indigo-500'}"></div>
            <div>
              <p class="text-sm font-medium text-gray-900">${escHtml(n.title)}</p>
              ${n.body ? `<p class="text-xs text-gray-500">${escHtml(n.body)}</p>` : ''}
              <p class="text-xs text-gray-400 mt-0.5">${new Date(n.created_at).toLocaleString('pt-BR')}</p>
            </div>
          </div>`).join('');
    } catch { list.innerHTML = '<p class="text-sm text-red-400 p-3">Erro ao carregar</p>'; }
  }

  // ── WebSocket ─────────────────────────────────────────────

  function initWS() {
    if (!window.WebSocket) return;
    try {
      const wsProto = location.protocol === 'https:' ? 'wss' : 'ws';
      const ws = new WebSocket(`${wsProto}://${window.location.hostname}:${WS_PORT || 8080}?uid=${encodeURIComponent(WS_UID || '')}&token=${encodeURIComponent(WS_TOKEN || '')}`);
      ws.onmessage = e => {
        const msg = JSON.parse(e.data);
        if (msg.board_id != STATE.board.id) return;
        if (msg.type === 'item.created' || msg.type === 'item.updated' || msg.type === 'item.moved') {
          // Recarga leve dos itens
          WorkdayApp.api('GET', `${APP_URL}/boards/${STATE.board.id}/items`).then(items => {
            STATE.items = items;
            renderCurrentView();
          }).catch(() => {});
        }
        if (msg.type === 'notification') {
          checkUnreadNotifs();
        }
      };
      ws.onerror = () => {};
    } catch {}
  }

  return { init, switchView, filter, quickAdd, openNewItemModal, openNewGroupModal, openItemDetail, toggleDone, openAutomationsPanel, renderCurrentView, addItem };
})();

// ============================================================
// Item Detail (modal)
// ============================================================
const WorkdayItemDetail = (() => {
  let currentItem = null;
  let boardState  = null;

  async function open(id, state) {
    boardState = state;
    const tpl = document.getElementById('itemDetailTpl');
    WorkdayApp.openModal(tpl.content.firstElementChild.outerHTML);

    try {
      const item = await WorkdayApp.api('GET', `${APP_URL}/items/${id}`);
      currentItem = item;
      populate(item, state);
    } catch (err) {
      WorkdayApp.toast(err.message, 'error');
    }
  }

  function populate(item, state) {
    // Título
    const titleInp = document.getElementById('detailTitle');
    if (titleInp) {
      titleInp.value = item.title;
      titleInp.addEventListener('blur', () => saveField('title', titleInp.value));
    }

    // Breadcrumb
    const bc = document.getElementById('detailBreadcrumb');
    if (bc) bc.textContent = `${item.board_name} / ${item.group_name}`;

    // Description
    const desc = document.getElementById('detailDescription');
    if (desc) {
      desc.value = item.description || '';
      desc.addEventListener('blur', () => saveField('description', desc.value));
    }

    // Status
    const groupSel = document.getElementById('detailGroup');
    if (groupSel) {
      state.board.groups.forEach(g => {
        const opt    = document.createElement('option');
        opt.value    = g.id;
        opt.textContent = g.name;
        if (g.id == item.group_id) opt.selected = true;
        groupSel.appendChild(opt);
      });
      groupSel.addEventListener('change', () => saveField('group_id', +groupSel.value));
    }

    // Prioridade
    const prioSel = document.getElementById('detailPriority');
    if (prioSel) {
      prioSel.value = item.priority;
      prioSel.addEventListener('change', () => saveField('priority', prioSel.value));
    }

    // Prazo
    const dueInp = document.getElementById('detailDueDate');
    if (dueInp) {
      dueInp.value = item.due_date || '';
      dueInp.addEventListener('change', () => saveField('due_date', dueInp.value || null));
    }

    // Assignees
    const assignDiv = document.getElementById('detailAssignees');
    if (assignDiv) {
      assignDiv.innerHTML = (item.assignees || []).map(a =>
        `<div class="av" title="${escHtml(a.name)}">${a.name.charAt(0).toUpperCase()}</div>`).join('');
    }
    const addAssignee = document.getElementById('addAssigneeSelect');
    if (addAssignee) {
      state.members.forEach(m => {
        if (!(item.assignees || []).find(a => a.id == m.id)) {
          const opt = document.createElement('option');
          opt.value = m.id; opt.textContent = m.name;
          addAssignee.appendChild(opt);
        }
      });
      addAssignee.addEventListener('change', async () => {
        const uid = +addAssignee.value;
        if (!uid) return;
        const cur = (item.assignees || []).map(a => a.id);
        await saveField('assignees', [...cur, uid]);
        addAssignee.value = '';
        const member = state.members.find(m => m.id === uid);
        if (member) {
          item.assignees = [...(item.assignees || []), member];
          assignDiv.innerHTML += `<div class="av" title="${escHtml(member.name)}">${member.name.charAt(0).toUpperCase()}</div>`;
        }
      });
    }

    // Subtarefas
    renderSubtasks(item.subtasks || []);

    // Comentários
    loadComments();

    // Anexos
    renderAttachments();

    // File upload
    const fileInput = document.getElementById('fileInput');
    if (fileInput) {
      fileInput.addEventListener('change', () => {
        const formData = new FormData();
        [...fileInput.files].forEach(f => formData.append('file', f));
        WorkdayApp.api('POST', `${APP_URL}/items/${item.id}/upload`, formData)
          .then(() => { WorkdayApp.toast('Arquivo enviado', 'success'); renderAttachments(); })
          .catch(err => WorkdayApp.toast(err.message, 'error'));
      });
    }

    // SIPOC
    if (item.tool_type === 'sipoc') {
      const sec = document.getElementById('detailSipocSection');
      if (sec) {
        sec.classList.remove('hidden');
        const toolContent = item.tool_content || { process_title: item.title, rows: [] };
        // Mostra título do processo na linha do acordeão
        const titleEl = document.getElementById('detailSipocAccordionTitle');
        if (titleEl) titleEl.textContent = toolContent.process_title || 'Diagrama SIPOC';
        renderSipocDetail(toolContent);
      }
    }
  }

  function toggleSipocAccordion() { /* mantido para compatibilidade */ }

  function openSipocWide() {
    if (!currentItem || !currentItem.tool_content) return;
    const toolContent = currentItem.tool_content;
    const cols = [
      {bg:'#2d3748',letter:'S',label:'FORNECEDORES',sub:'quem fornece entradas?'},
      {bg:'#4a5568',letter:'I',label:'ENTRADA',sub:'o que é fornecido?'},
      {bg:'#2d3748',letter:'P',label:'PROCESSO',sub:'etapas que convertem in → out'},
      {bg:'#4a5568',letter:'O',label:'SAÍDA',sub:'resultado do processo'},
      {bg:'#718096',letter:'C',label:'CLIENTE',sub:'quem recebe a saída?'},
    ];
    const rows = toolContent.rows && toolContent.rows.length ? toolContent.rows : Array(3).fill(['','','','','']);
    const rowsHtml = rows.map((row, ri) => `
      <div class="sipoc-row" data-row="${ri}">
        ${[0,1,2,3,4].map(ci =>
          `<div class="sipoc-cell${ci%2?' sipoc-cell-alt':''}"><textarea class="sipoc-cell-input" data-row="${ri}" data-col="${ci}" rows="2">${escHtml(row[ci]||'')}</textarea></div>`
        ).join('')}
        <button class="sipoc-row-del" onclick="WorkdayItemDetail.removeSipocRow(${ri})" title="Remover linha">
          <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
        </button>
      </div>`).join('');

    WorkdayApp.openWideModal(`
      <div class="p-6 space-y-4" style="width:min(1100px,96vw);max-height:92vh;overflow-y:auto">
        <div class="flex items-center justify-between shrink-0">
          <div class="flex items-center gap-2">
            <span class="sipoc-item-badge">SIPOC</span>
            <h3 class="text-lg font-semibold text-gray-900" id="wideModalSipocTitle">${escHtml(toolContent.process_title || currentItem.title)}</h3>
          </div>
          <button onclick="WorkdayApp.closeWideModal()" class="text-gray-400 hover:text-gray-700 text-2xl leading-none font-light">&times;</button>
        </div>
        <div class="sipoc-process-title-row" style="border-radius:8px;border:1px solid #fde68a">
          <span class="sipoc-process-title-label">TÍTULO DO PROCESSO:</span>
          <input type="text" id="wideSipocProcessTitle" class="sipoc-process-title-input" value="${escHtml(toolContent.process_title||'')}" placeholder="Título do processo..."/>
        </div>
        <div class="sipoc-diagram overflow-x-auto">
          <div class="sipoc-letters-row">${cols.map(c=>`<div class="sipoc-letter-cell" style="background:${c.bg}"><span class="sipoc-letter">${c.letter}</span></div>`).join('')}</div>
          <div class="sipoc-header-row">${cols.map(c=>`<div class="sipoc-header-cell" style="background:${c.bg}"><span class="sipoc-col-label">${c.label}</span><span class="sipoc-col-sub">${c.sub}</span></div>`).join('')}</div>
          <div class="sipoc-table-header">${cols.map(c=>`<div class="sipoc-th">${c.label}</div>`).join('')}</div>
          <div id="wideSipocRows">${rowsHtml}</div>
          <div class="sipoc-add-row">
            <button onclick="WorkdayItemDetail.addSipocWideRow()" class="sipoc-add-row-btn">
              <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
              Adicionar linha
            </button>
          </div>
        </div>
        <div class="flex justify-end gap-3 pt-2">
          <button onclick="WorkdayApp.closeWideModal()" class="btn-secondary">Fechar</button>
          <button id="saveSipocWideBtn" onclick="WorkdayItemDetail.saveSipocWide()" class="btn-primary">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-3m-1 4l-3 3m0 0l-3-3m3 3V4"/></svg>
            Salvar
          </button>
        </div>
      </div>`);
  }

  function addSipocWideRow() {
    const container = document.getElementById('wideSipocRows');
    if (!container) return;
    const idx = container.querySelectorAll('.sipoc-row').length;
    const div = document.createElement('div');
    div.className = 'sipoc-row';
    div.dataset.row = idx;
    let inner = '';
    for (let ci = 0; ci < 5; ci++) {
      inner += `<div class="sipoc-cell${ci%2?' sipoc-cell-alt':''}"><textarea class="sipoc-cell-input" data-row="${idx}" data-col="${ci}" rows="2"></textarea></div>`;
    }
    inner += `<button class="sipoc-row-del" onclick="WorkdayItemDetail.removeSipocWideRow(${idx})" title="Remover linha"><svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg></button>`;
    div.innerHTML = inner;
    container.appendChild(div);
    div.querySelector('textarea').focus();
  }

  function removeSipocWideRow(idx) {
    const rowEl = document.querySelector(`#wideSipocRows .sipoc-row[data-row="${idx}"]`);
    if (!rowEl) return;
    if (document.querySelectorAll('#wideSipocRows .sipoc-row').length <= 1) {
      WorkdayApp.toast('O diagrama precisa ter ao menos uma linha.', 'error'); return;
    }
    rowEl.remove();
    document.querySelectorAll('#wideSipocRows .sipoc-row').forEach((r, i) => {
      r.dataset.row = i;
      r.querySelector('.sipoc-row-del').setAttribute('onclick', `WorkdayItemDetail.removeSipocWideRow(${i})`);
    });
  }

  async function saveSipocWide() {
    if (!currentItem) return;
    const rows = [];
    document.querySelectorAll('#wideSipocRows .sipoc-row').forEach(rowEl => {
      const cells = [];
      rowEl.querySelectorAll('.sipoc-cell-input').forEach(ta => cells.push(ta.value));
      rows.push(cells);
    });
    const processTitle = document.getElementById('wideSipocProcessTitle')?.value.trim() || currentItem.title;
    const btn = document.getElementById('saveSipocWideBtn');
    if (btn) { btn.disabled = true; btn.textContent = 'Salvando...'; }
    try {
      await WorkdayApp.api('POST', `${APP_URL}/items/${currentItem.id}/tool-data`, {
        tool_type: 'sipoc',
        content: { process_title: processTitle, rows },
      });
      // Atualiza cache local
      currentItem.tool_content = { process_title: processTitle, rows };
      const titleEl = document.getElementById('detailSipocAccordionTitle');
      if (titleEl) titleEl.textContent = processTitle || 'Diagrama SIPOC';
      WorkdayApp.closeWideModal();
      WorkdayApp.toast('SIPOC salvo!', 'success');
    } catch (err) {
      WorkdayApp.toast(err.message, 'error');
    } finally {
      if (btn) { btn.disabled = false; btn.textContent = 'Salvar'; }
    }
  }

  function renderSubtasks(subtasks) {
    const list = document.getElementById('subtaskList');
    if (!list) return;
    list.innerHTML = subtasks.map(s => `
      <div class="flex items-center gap-2 text-sm" data-subtask="${s.id}">
        <input type="checkbox" ${s.done_at ? 'checked' : ''} class="rounded border-gray-300 text-indigo-600"
               onchange="WorkdayBoard.toggleDone(${s.id},this.checked)"/>
        <span class="${s.done_at ? 'line-through text-gray-400' : 'text-gray-700'}">${escHtml(s.title)}</span>
      </div>`).join('');
  }

  async function addSubtask() {
    const inp   = document.getElementById('newSubtask');
    const title = inp?.value.trim();
    if (!title || !currentItem) return;
    try {
      await WorkdayApp.api('POST', `${APP_URL}/boards/${currentItem.board_id}/items`, {
        title, group_id: currentItem.group_id, parent_id: currentItem.id, priority: 'none',
      });
      inp.value = '';
      currentItem.subtasks = currentItem.subtasks || [];
      currentItem.subtasks.push({ title, done_at: null });
      renderSubtasks(currentItem.subtasks);
      WorkdayApp.toast('Subtarefa adicionada', 'success');
    } catch (err) { WorkdayApp.toast(err.message, 'error'); }
  }

  async function loadComments() {
    if (!currentItem) return;
    try {
      const comments = await WorkdayApp.api('GET', `${APP_URL}/items/${currentItem.id}/comments`);
      const list = document.getElementById('commentList');
      if (!list) return;
      list.innerHTML = comments.map(c => `
        <div class="flex gap-3">
          <div class="w-7 h-7 rounded-full bg-indigo-100 text-indigo-700 flex items-center justify-center text-xs font-bold shrink-0">
            ${c.user_name.charAt(0).toUpperCase()}
          </div>
          <div class="flex-1">
            <span class="text-sm font-medium text-gray-900">${escHtml(c.user_name)}</span>
            <span class="text-xs text-gray-400 ml-1">${new Date(c.created_at).toLocaleString('pt-BR')}</span>
            <p class="text-sm text-gray-700 mt-1">${escHtml(c.body)}</p>
            ${c.replies?.length ? `<div class="mt-2 border-l-2 border-gray-100 pl-3 space-y-2">
              ${c.replies.map(r=>`<div><span class="text-xs font-medium text-gray-600">${escHtml(r.user_name)}</span>
                <p class="text-xs text-gray-600">${escHtml(r.body)}</p></div>`).join('')}
            </div>` : ''}
          </div>
        </div>`).join('');
    } catch {}
  }

  async function addComment() {
    const inp  = document.getElementById('newComment');
    const body = inp?.value.trim();
    if (!body || !currentItem) return;
    try {
      await WorkdayApp.api('POST', `${APP_URL}/items/${currentItem.id}/comments`, { body });
      inp.value = '';
      await loadComments();
    } catch (err) { WorkdayApp.toast(err.message, 'error'); }
  }

  function renderAttachments() {
    const list = document.getElementById('attachmentList');
    if (!list || !currentItem) return;
    WorkdayApp.api('GET', `${APP_URL}/items/${currentItem.id}`).then(item => {
      // Reusa os dados do item (sem endpoint separado para attachments por enquanto)
      list.innerHTML = '<p class="text-xs text-gray-400">—</p>';
    }).catch(() => {});
  }

  async function archiveItem() {
    if (!currentItem) return;
    try {
      await WorkdayApp.api('POST', `${APP_URL}/items/${currentItem.id}/archive`, {});
      if (boardState) {
        boardState.items = boardState.items.filter(i => i.id !== currentItem.id);
        WorkdayBoard.renderCurrentView?.();
      }
      WorkdayApp.closeModal();
      WorkdayApp.toast('Item arquivado', 'success');
    } catch (err) { WorkdayApp.toast(err.message, 'error'); }
  }

  async function saveField(field, value) {
    if (!currentItem) return;
    try {
      await WorkdayApp.api('PUT', `${APP_URL}/items/${currentItem.id}`, { [field]: value });
      currentItem[field] = value;
      // Atualiza state do board
      if (boardState) {
        const i = boardState.items.find(x => x.id === currentItem.id);
        if (i) {
          i[field] = value;
          if (field === 'group_id') {
            const grp = boardState.board.groups.find(g => g.id == value);
            if (grp) { i.group_name = grp.name; i.group_color = grp.color; }
          }
        }
        WorkdayBoard.renderCurrentView?.();
      }
    } catch (err) { WorkdayApp.toast(err.message, 'error'); }
  }

  return { open, addSubtask, addComment, archiveItem, addSipocRow, removeSipocRow, saveSipoc, toggleSipocAccordion, openSipocWide, addSipocWideRow, removeSipocWideRow, saveSipocWide };
})();

// ============================================================
// Automações panel
// ============================================================
const WorkdayAutomations = (() => {

  async function openPanel(boardId, groups, members) {
    const automations = await WorkdayApp.api('GET', `${APP_URL}/boards/${boardId}/automations`).catch(() => []);

    WorkdayApp.openModal(`
      <div class="p-6">
        <div class="flex items-center justify-between mb-4">
          <h3 class="text-lg font-semibold text-gray-900">Automações</h3>
          <button onclick="WorkdayAutomations.openBuilder(${boardId})" class="btn-primary text-sm py-1.5">+ Nova</button>
        </div>
        ${automations.length === 0 ? '<p class="text-sm text-gray-400 text-center py-8">Nenhuma automação configurada</p>' : ''}
        <div class="space-y-3">
          ${automations.map(a => `
            <div class="automation-card ${a.is_active ? '' : 'inactive'}">
              <div class="flex items-center justify-between">
                <span class="font-medium text-sm text-gray-900">${escHtml(a.name)}</span>
                <div class="flex items-center gap-2">
                  <label class="flex items-center gap-1 text-xs text-gray-500 cursor-pointer">
                    <input type="checkbox" ${a.is_active ? 'checked' : ''}
                      onchange="WorkdayAutomations.toggle(${boardId}, ${a.id}, this.checked)"
                      class="rounded border-gray-300 text-indigo-600"/>
                    Ativo
                  </label>
                  <button onclick="WorkdayAutomations.remove(${boardId}, ${a.id}, this)"
                          class="text-xs text-red-400 hover:text-red-600">Remover</button>
                </div>
              </div>
              <p class="text-xs text-gray-400 mt-1">Gatilho: ${escHtml(JSON.stringify(JSON.parse(a.trigger||'{}')))}</p>
            </div>`).join('')}
        </div>
      </div>`);
  }

  function openBuilder(boardId) {
    WorkdayApp.openModal(`
      <div class="p-6 space-y-4">
        <h3 class="text-lg font-semibold text-gray-900">Nova Automação</h3>
        <p class="text-sm text-gray-500">Defina a regra "Se → Então" para este quadro.</p>
        <form id="automationForm" class="space-y-4">
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Nome da automação</label>
            <input type="text" name="name" required class="form-input" placeholder="Ex: Notificar quando concluído"/>
          </div>
          <div class="bg-indigo-50 rounded-xl p-4 space-y-3">
            <p class="text-sm font-semibold text-indigo-700">SE (Gatilho)</p>
            <select name="trigger_event" class="form-input text-sm">
              <option value="status_changed">Status mudar</option>
              <option value="item.created">Item criado</option>
              <option value="due_date_approaching">Prazo se aproximar</option>
            </select>
          </div>
          <div class="bg-green-50 rounded-xl p-4 space-y-3">
            <p class="text-sm font-semibold text-green-700">ENTÃO (Ação)</p>
            <select name="action_type" class="form-input text-sm">
              <option value="notify_user">Notificar responsáveis</option>
              <option value="set_priority">Definir prioridade</option>
              <option value="move_item">Mover para coluna</option>
            </select>
          </div>
          <div class="flex justify-end gap-3">
            <button type="button" onclick="WorkdayApp.closeModal()" class="btn-secondary">Cancelar</button>
            <button type="submit" class="btn-primary">Salvar automação</button>
          </div>
        </form>
      </div>`);

    document.getElementById('automationForm').addEventListener('submit', async e => {
      e.preventDefault();
      const fd = new FormData(e.target);
      try {
        await WorkdayApp.api('POST', `${APP_URL}/boards/${boardId}/automations`, {
          name: fd.get('name'),
          trigger: { event: fd.get('trigger_event') },
          actions: [{ type: fd.get('action_type'), user_ids: [] }],
        });
        WorkdayApp.closeModal();
        WorkdayApp.toast('Automação criada', 'success');
      } catch (err) { WorkdayApp.toast(err.message, 'error'); }
    });
  }

  async function toggle(boardId, id, active) {
    await WorkdayApp.api('PUT', `${APP_URL}/boards/${boardId}/automations/${id}`, { is_active: active ? 1 : 0 })
      .catch(err => WorkdayApp.toast(err.message, 'error'));
  }

  async function remove(boardId, id, btn) {
    if (!confirm('Remover esta automação?')) return;
    try {
      await WorkdayApp.api('DELETE', `${APP_URL}/boards/${boardId}/automations/${id}`, null);
      btn.closest('.automation-card').remove();
      WorkdayApp.toast('Automação removida', 'success');
    } catch (err) { WorkdayApp.toast(err.message, 'error'); }
  }

  return { openPanel, openBuilder, toggle, remove };
})();

// ============================================================
// Notificações
// ============================================================
const WorkdayNotif = (() => {
  async function markRead(id) {
    await WorkdayApp.api('POST', `${APP_URL}/notifications/${id}/read`, {}).catch(() => {});
  }
  return { markRead };
})();

// ============================================================
// Ferramentas (SIPOC, etc.)
// ============================================================
const WorkdayTools = (() => {
  const SIPOC_COLS = [
    {bg:'#2d3748',letter:'S',label:'FORNECEDORES',sub:'quem fornece entradas?'},
    {bg:'#4a5568',letter:'I',label:'ENTRADA',sub:'o que é fornecido?'},
    {bg:'#2d3748',letter:'P',label:'PROCESSO',sub:'etapas que convertem in → out'},
    {bg:'#4a5568',letter:'O',label:'SAÍDA',sub:'resultado do processo'},
    {bg:'#718096',letter:'C',label:'CLIENTE',sub:'quem recebe a saída?'},
  ];

  function toggleDropdown() {
    const menu = document.getElementById('ferramentasMenu');
    if (!menu) return;
    const hidden = menu.classList.toggle('hidden');
    if (!hidden) {
      // Fecha ao clicar fora
      setTimeout(() => {
        document.addEventListener('click', function handler(e) {
          if (!document.getElementById('ferramentasDropdown')?.contains(e.target)) {
            menu.classList.add('hidden');
          }
          document.removeEventListener('click', handler);
        });
      }, 0);
    }
  }

  function openSipocModal() {
    document.getElementById('ferramentasMenu')?.classList.add('hidden');

    const boardEl  = document.getElementById('boardApp');
    const boardId  = boardEl ? parseInt(boardEl.dataset.boardId) : null;
    if (!boardId) { WorkdayApp.toast('Quadro não encontrado', 'error'); return; }

    // Obtém grupos via API (já lidos no STATE)
    const boardDataEl = document.getElementById('boardData');
    const boardData   = boardDataEl ? JSON.parse(boardDataEl.textContent) : null;
    const groups      = boardData?.board?.groups || [];

    const groupOptions = groups.map(g =>
      `<option value="${g.id}">${escHtml(g.name)}</option>`
    ).join('');

    const rowsHtml = [0,1,2,3,4].map(ri => `
      <div class="sipoc-row" data-row="${ri}">
        ${[0,1,2,3,4].map(ci =>
          `<div class="sipoc-cell${ci%2?' sipoc-cell-alt':''}">
            <textarea class="sipoc-cell-input" data-row="${ri}" data-col="${ci}" rows="2"></textarea>
          </div>`).join('')}
        <button class="sipoc-row-del" onclick="WorkdayTools.removeSipocModalRow(${ri})" title="Remover linha">
          <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
          </svg>
        </button>
      </div>`).join('');

    WorkdayApp.openWideModal(`
      <div class="p-6 space-y-4" style="width:min(1100px,96vw);max-height:92vh;overflow-y:auto">
        <div class="flex items-center justify-between shrink-0">
          <div class="flex items-center gap-2">
            <span class="sipoc-item-badge">SIPOC</span>
            <h3 class="text-lg font-semibold text-gray-900">Novo diagrama SIPOC</h3>
          </div>
          <button onclick="WorkdayApp.closeWideModal()" class="text-gray-400 hover:text-gray-700 text-2xl leading-none font-light">&times;</button>
        </div>

        <div class="grid grid-cols-2 gap-4">
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Adicionar ao grupo</label>
            <select id="sipocModalGroupId" class="form-input">${groupOptions}</select>
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Título do processo *</label>
            <input type="text" id="sipocModalTitle" class="form-input" placeholder="Ex: Processo de onboarding"/>
          </div>
        </div>

        <div class="sipoc-diagram overflow-x-auto">
          <div class="sipoc-letters-row">
            ${SIPOC_COLS.map(c=>`<div class="sipoc-letter-cell" style="background:${c.bg}"><span class="sipoc-letter">${c.letter}</span></div>`).join('')}
          </div>
          <div class="sipoc-header-row">
            ${SIPOC_COLS.map(c=>`<div class="sipoc-header-cell" style="background:${c.bg}"><span class="sipoc-col-label">${c.label}</span><span class="sipoc-col-sub">${c.sub}</span></div>`).join('')}
          </div>
          <div class="sipoc-table-header">
            ${SIPOC_COLS.map(c=>`<div class="sipoc-th">${c.label}</div>`).join('')}
          </div>
          <div id="sipocModalRows">${rowsHtml}</div>
          <div class="sipoc-add-row">
            <button onclick="WorkdayTools.addSipocModalRow()" class="sipoc-add-row-btn">
              <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
              Adicionar linha
            </button>
          </div>
        </div>

        <div class="flex justify-end gap-3 pt-2">
          <button onclick="WorkdayApp.closeWideModal()" class="btn-secondary">Cancelar</button>
          <button id="createSipocItemBtn" onclick="WorkdayTools.saveSipocAsItem(${boardId})" class="btn-primary">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
            Criar item SIPOC
          </button>
        </div>
      </div>`);
  }

  function addSipocModalRow() {
    const container = document.getElementById('sipocModalRows');
    if (!container) return;
    const idx = container.querySelectorAll('.sipoc-row').length;
    const div = document.createElement('div');
    div.className = 'sipoc-row';
    div.dataset.row = idx;
    let inner = '';
    for (let ci = 0; ci < 5; ci++) {
      inner += `<div class="sipoc-cell${ci%2?' sipoc-cell-alt':''}"><textarea class="sipoc-cell-input" data-row="${idx}" data-col="${ci}" rows="2"></textarea></div>`;
    }
    inner += `<button class="sipoc-row-del" onclick="WorkdayTools.removeSipocModalRow(${idx})" title="Remover linha"><svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg></button>`;
    div.innerHTML = inner;
    container.appendChild(div);
    div.querySelector('textarea').focus();
  }

  function removeSipocModalRow(idx) {
    const rowEl = document.querySelector(`#sipocModalRows .sipoc-row[data-row="${idx}"]`);
    if (!rowEl) return;
    if (document.querySelectorAll('#sipocModalRows .sipoc-row').length <= 1) {
      WorkdayApp.toast('O diagrama precisa ter ao menos uma linha.', 'error'); return;
    }
    rowEl.remove();
    document.querySelectorAll('#sipocModalRows .sipoc-row').forEach((r, i) => {
      r.dataset.row = i;
      r.querySelector('.sipoc-row-del').setAttribute('onclick', `WorkdayTools.removeSipocModalRow(${i})`);
    });
  }

  function getModalRows() {
    const rows = [];
    document.querySelectorAll('#sipocModalRows .sipoc-row').forEach(rowEl => {
      const cells = [];
      rowEl.querySelectorAll('.sipoc-cell-input').forEach(ta => cells.push(ta.value));
      rows.push(cells);
    });
    return rows;
  }

  async function saveSipocAsItem(boardId) {
    const title   = document.getElementById('sipocModalTitle')?.value.trim();
    const groupId = document.getElementById('sipocModalGroupId')?.value;
    if (!title)   { WorkdayApp.toast('Informe o título do processo', 'error'); return; }
    if (!groupId) { WorkdayApp.toast('Selecione um grupo', 'error'); return; }

    const btn = document.getElementById('createSipocItemBtn');
    if (btn) { btn.disabled = true; btn.textContent = 'Criando...'; }

    try {
      // 1. Cria o item
      const res = await WorkdayApp.api('POST', `${APP_URL}/boards/${boardId}/items`, {
        title:    title,
        group_id: parseInt(groupId),
        priority: 'none',
      });

      // 2. Salva os dados SIPOC
      await WorkdayApp.api('POST', `${APP_URL}/items/${res.id}/tool-data`, {
        tool_type: 'sipoc',
        content: { process_title: title, rows: getModalRows() },
      });

      // 3. Atualiza o estado do board
      const boardData = document.getElementById('boardData')
        ? JSON.parse(document.getElementById('boardData').textContent)
        : null;
      const grp = boardData?.board?.groups?.find(g => g.id == groupId);
      WorkdayBoard.addItem({
        id:             res.id,
        board_id:       boardId,
        group_id:       parseInt(groupId),
        title:          title,
        priority:       'none',
        position:       9999,
        tool_type:      'sipoc',
        group_name:     grp?.name  ?? '',
        group_color:    grp?.color ?? '#94a3b8',
        assignees:      [],
        comment_count:  0,
        subtask_count:  0,
        attachment_count: 0,
      });

      WorkdayApp.closeWideModal();
      WorkdayApp.toast('Item SIPOC criado!', 'success');
    } catch (err) {
      WorkdayApp.toast(err.message, 'error');
    } finally {
      if (btn) { btn.disabled = false; btn.textContent = 'Criar item SIPOC'; }
    }
  }

  return { toggleDropdown, openSipocModal, addSipocModalRow, removeSipocModalRow, saveSipocAsItem };
})();

// ============================================================
// Boards list page
// ============================================================
const WorkdayBoards = (() => {
  function openNewBoardModal(portfolioId = null) {
    const tpl = document.getElementById('newBoardModalTpl');
    WorkdayApp.openModal(tpl.content.firstElementChild.outerHTML);
    document.getElementById('newBoardForm').addEventListener('submit', async e => {
      e.preventDefault();
      const fd = new FormData(e.target);
      try {
        const res = await WorkdayApp.api('POST', `${APP_URL}/boards`, {
          name:         fd.get('name'),
          description:  fd.get('description') || null,
          color:        fd.get('color') || '#6366f1',
          default_view: fd.get('default_view') || 'kanban',
          portfolio_id: portfolioId,
        });
        WorkdayApp.closeModal();
        WorkdayApp.toast('Quadro criado!', 'success');
        setTimeout(() => window.location.href = `${APP_URL}/boards/${res.id}`, 600);
      } catch (err) { WorkdayApp.toast(err.message, 'error'); }
    });
  }
  return { openNewBoardModal };
})();

// ============================================================
// Utils
// ============================================================
function escHtml(str) {
  if (!str) return '';
  return String(str).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;').replace(/'/g,'&#039;');
}

// ============================================================
// Init  (APP_URL / WS_HOST / WS_PORT injetados pelo PHP no layout)
// ============================================================

document.addEventListener('DOMContentLoaded', () => {
  WorkdayBoard.init();

  // Sidebar toggle — desktop e mobile, com estado persistido
  const sidebar       = document.getElementById('sidebar');
  const toggleBtn     = document.getElementById('sidebarToggle');
  const STORAGE_KEY   = 'sidebar_collapsed';

  const iconExpand = `<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/></svg>`;
  const iconCollapse = `<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/></svg>`;

  function setSidebarState(collapsed) {
    if (!sidebar || !toggleBtn) return;
    if (collapsed) {
      sidebar.classList.add('collapsed');
      toggleBtn.title = 'Expandir menu';
    } else {
      sidebar.classList.remove('collapsed');
      toggleBtn.title = 'Recolher menu';
    }
    localStorage.setItem(STORAGE_KEY, collapsed ? '1' : '0');
  }

  // Restaura estado salvo
  setSidebarState(localStorage.getItem(STORAGE_KEY) === '1');

  toggleBtn?.addEventListener('click', () => {
    setSidebarState(!sidebar.classList.contains('collapsed'));
  });
});
