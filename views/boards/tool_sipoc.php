<?php
/**
 * View: Ferramenta SIPOC do quadro
 * Acesso: GET /boards/{boardId}/tool
 */

$boardId    = $board['id'];
$boardName  = htmlspecialchars($board['name']);
$boardColor = htmlspecialchars($board['color'] ?? '#6366f1');

// Dados existentes ou estrutura em branco (5 linhas)
$processTitle = htmlspecialchars($toolData['process_title'] ?? '');
$rows = $toolData['rows'] ?? array_fill(0, 5, ['', '', '', '', '']);

$cols = [
    ['key' => 0, 'label' => 'FORNECEDORES', 'sub' => 'quem fornece entradas?',         'bg' => '#2d3748', 'letter' => 'S'],
    ['key' => 1, 'label' => 'ENTRADA',       'sub' => 'o que é fornecido?',              'bg' => '#4a5568', 'letter' => 'I'],
    ['key' => 2, 'label' => 'PROCESSO',      'sub' => 'etapas que convertem in → out',   'bg' => '#2d3748', 'letter' => 'P'],
    ['key' => 3, 'label' => 'SAÍDA',         'sub' => 'resultado do processo',           'bg' => '#4a5568', 'letter' => 'O'],
    ['key' => 4, 'label' => 'CLIENTE',       'sub' => 'quem recebe a saída?',            'bg' => '#718096', 'letter' => 'C'],
];
?>

<!-- SIPOC Wrapper -->
<div class="sipoc-root" id="sipocApp">

  <!-- ── Header ─────────────────────────────────────────────────────── -->
  <div class="sipoc-header">
    <div class="sipoc-header-left">
      <a href="<?= APP_URL ?>/boards/<?= $boardId ?>" class="sipoc-back-btn" title="Voltar ao quadro">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
        </svg>
        <?= $boardName ?>
      </a>
      <span class="sipoc-badge">SIPOC</span>
    </div>
    <div class="sipoc-header-actions">
      <button id="sipocPrintBtn" class="btn-secondary text-sm" onclick="window.print()">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
            d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/>
        </svg>
        Imprimir
      </button>
      <button id="sipocSaveBtn" class="btn-primary text-sm" onclick="SipocEditor.save()">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
            d="M8 7H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-3m-1 4l-3 3m0 0l-3-3m3 3V4"/>
        </svg>
        Salvar
      </button>
    </div>
  </div>

  <!-- ── Diagrama ────────────────────────────────────────────────────── -->
  <div class="sipoc-scroll">
    <div class="sipoc-diagram" id="sipocDiagram">

      <!-- Título do diagrama -->
      <div class="sipoc-diagram-title">MODELO DE DIAGRAMA SIPOC</div>

      <!-- Letras S I P O C -->
      <div class="sipoc-letters-row">
        <?php foreach ($cols as $col): ?>
          <div class="sipoc-letter-cell" style="background:<?= $col['bg'] ?>">
            <span class="sipoc-letter"><?= $col['letter'] ?></span>
          </div>
        <?php endforeach; ?>
      </div>

      <!-- Cabeçalhos com labels e sub-labels -->
      <div class="sipoc-header-row">
        <?php foreach ($cols as $col): ?>
          <div class="sipoc-header-cell" style="background:<?= $col['bg'] ?>">
            <span class="sipoc-col-label"><?= $col['label'] ?></span>
            <span class="sipoc-col-sub"><?= htmlspecialchars($col['sub']) ?></span>
          </div>
        <?php endforeach; ?>
      </div>

      <!-- Título do processo -->
      <div class="sipoc-process-title-row">
        <span class="sipoc-process-title-label">TÍTULO DO PROCESSO:</span>
        <input type="text"
               id="sipocProcessTitle"
               class="sipoc-process-title-input"
               placeholder="Digite o título do processo..."
               value="<?= $processTitle ?>"/>
      </div>

      <!-- Cabeçalhos da tabela de dados -->
      <div class="sipoc-table-header">
        <?php foreach ($cols as $col): ?>
          <div class="sipoc-th"><?= $col['label'] ?></div>
        <?php endforeach; ?>
      </div>

      <!-- Linhas de dados -->
      <div id="sipocRows">
        <?php foreach ($rows as $ri => $row): ?>
          <div class="sipoc-row" data-row="<?= $ri ?>">
            <?php for ($ci = 0; $ci < 5; $ci++): ?>
              <div class="sipoc-cell <?= $ci % 2 !== 0 ? 'sipoc-cell-alt' : '' ?>">
                <textarea
                  class="sipoc-cell-input"
                  data-row="<?= $ri ?>"
                  data-col="<?= $ci ?>"
                  placeholder=""
                  rows="2"
                ><?= htmlspecialchars($row[$ci] ?? '') ?></textarea>
              </div>
            <?php endfor; ?>
            <button class="sipoc-row-del" onclick="SipocEditor.removeRow(<?= $ri ?>)" title="Remover linha">
              <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
              </svg>
            </button>
          </div>
        <?php endforeach; ?>
      </div>

      <!-- Botão adicionar linha -->
      <div class="sipoc-add-row">
        <button onclick="SipocEditor.addRow()" class="sipoc-add-row-btn">
          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
          </svg>
          Adicionar linha
        </button>
      </div>

    </div><!-- /.sipoc-diagram -->
  </div><!-- /.sipoc-scroll -->

</div><!-- /.sipoc-root -->

<!-- ── Scripts SIPOC ──────────────────────────────────────────────────── -->
<script>
const SipocEditor = (() => {
  const BOARD_ID  = <?= $boardId ?>;
  const API_URL   = '<?= APP_URL ?>/boards/' + BOARD_ID + '/tool';
  const csrf      = () => document.querySelector('meta[name="csrf-token"]')?.content ?? '';

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
      rowEl.querySelectorAll('.sipoc-cell-input').forEach((ta, ci) => {
        ta.dataset.row = i;
      });
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
        <textarea class="sipoc-cell-input" data-row="${idx}" data-col="${ci}" rows="2" placeholder=""></textarea>
      </div>`;
    }
    inner += `<button class="sipoc-row-del" onclick="SipocEditor.removeRow(${idx})" title="Remover linha">
      <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
      </svg>
    </button>`;
    div.innerHTML = inner;
    container.appendChild(div);
    div.querySelector('textarea').focus();
  }

  function removeRow(idx) {
    const rowEl = document.querySelector(`#sipocRows .sipoc-row[data-row="${idx}"]`);
    if (!rowEl) return;
    const total = document.querySelectorAll('#sipocRows .sipoc-row').length;
    if (total <= 1) { WorkdayApp.toast('O diagrama precisa ter ao menos uma linha.', 'error'); return; }
    rowEl.remove();
    reindexRows();
  }

  async function save() {
    const btn = document.getElementById('sipocSaveBtn');
    btn.disabled = true;
    btn.textContent = 'Salvando...';
    try {
      const payload = {
        process_title: document.getElementById('sipocProcessTitle').value.trim(),
        rows: getRows(),
      };
      const res = await fetch(API_URL, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'X-CSRF-Token': csrf(),
          'X-Requested-With': 'XMLHttpRequest',
        },
        body: JSON.stringify(payload),
      });
      const json = await res.json();
      if (!res.ok) throw new Error(json.error ?? 'Erro ao salvar');
      WorkdayApp.toast('SIPOC salvo com sucesso!', 'success');
    } catch (e) {
      WorkdayApp.toast(e.message, 'error');
    } finally {
      btn.disabled = false;
      btn.innerHTML = `<svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
          d="M8 7H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-3m-1 4l-3 3m0 0l-3-3m3 3V4"/>
      </svg> Salvar`;
    }
  }

  // Ctrl+S para salvar
  document.addEventListener('keydown', e => {
    if ((e.ctrlKey || e.metaKey) && e.key === 's') { e.preventDefault(); save(); }
  });

  return { addRow, removeRow, save };
})();
</script>

<!-- ── Estilos SIPOC ───────────────────────────────────────────────────── -->
<style>
/* ---- Layout geral ---- */
.sipoc-root {
  display: flex;
  flex-direction: column;
  height: 100%;
  background: #f8fafc;
}

/* ---- Header ---- */
.sipoc-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 12px 24px;
  background: #fff;
  border-bottom: 1px solid #e2e8f0;
  flex-shrink: 0;
}
.sipoc-header-left  { display: flex; align-items: center; gap: 12px; }
.sipoc-header-actions { display: flex; align-items: center; gap: 8px; }
.sipoc-back-btn {
  display: flex; align-items: center; gap: 6px;
  font-size: 14px; font-weight: 500; color: #4b5563;
  text-decoration: none;
  padding: 6px 10px; border-radius: 8px;
  border: 1px solid #e5e7eb;
  background: #fff;
  transition: background .15s, color .15s;
}
.sipoc-back-btn:hover { background: #f3f4f6; color: #111827; }
.sipoc-badge {
  font-size: 11px; font-weight: 700; letter-spacing: .08em;
  background: #6366f1; color: #fff;
  padding: 3px 10px; border-radius: 999px;
}

/* ---- Área com scroll ---- */
.sipoc-scroll {
  flex: 1;
  overflow: auto;
  padding: 24px;
}

/* ---- Diagrama ---- */
.sipoc-diagram {
  max-width: 1200px;
  margin: 0 auto;
  background: #fff;
  border-radius: 12px;
  box-shadow: 0 1px 4px rgba(0,0,0,.08);
  overflow: hidden;
}
.sipoc-diagram-title {
  font-size: 15px;
  font-weight: 700;
  letter-spacing: .06em;
  color: #6366f1;
  padding: 16px 20px 12px;
  border-bottom: 1px solid #f1f5f9;
}

/* ---- Linha de letras (S I P O C) ---- */
.sipoc-letters-row {
  display: grid;
  grid-template-columns: repeat(5, 1fr);
}
.sipoc-letter-cell {
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 20px 8px;
}
.sipoc-letter {
  font-size: 52px;
  font-weight: 900;
  color: #fff;
  line-height: 1;
}

/* ---- Linha de cabeçalhos col ---- */
.sipoc-header-row {
  display: grid;
  grid-template-columns: repeat(5, 1fr);
}
.sipoc-header-cell {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  padding: 12px 8px;
  text-align: center;
  gap: 4px;
}
.sipoc-col-label {
  font-size: 11px;
  font-weight: 700;
  letter-spacing: .1em;
  color: #fff;
}
.sipoc-col-sub {
  font-size: 10px;
  color: rgba(255,255,255,.7);
  line-height: 1.3;
}

/* ---- Título do processo ---- */
.sipoc-process-title-row {
  display: flex;
  align-items: center;
  gap: 10px;
  padding: 10px 16px;
  background: #fffbeb;
  border-top: 1px solid #fde68a;
  border-bottom: 1px solid #fde68a;
}
.sipoc-process-title-label {
  font-size: 11px;
  font-weight: 700;
  color: #92400e;
  letter-spacing: .06em;
  white-space: nowrap;
}
.sipoc-process-title-input {
  flex: 1;
  border: none;
  background: transparent;
  font-size: 13px;
  font-weight: 500;
  color: #1e293b;
  outline: none;
  padding: 0;
}
.sipoc-process-title-input::placeholder { color: #d1d5db; }

/* ---- Cabeçalho da tabela de dados ---- */
.sipoc-table-header {
  display: grid;
  grid-template-columns: repeat(5, 1fr);
  background: #1e293b;
}
.sipoc-th {
  padding: 8px 12px;
  font-size: 10px;
  font-weight: 700;
  letter-spacing: .1em;
  color: #e2e8f0;
  text-align: center;
  border-left: 1px solid rgba(255,255,255,.08);
}
.sipoc-th:first-child { border-left: none; }

/* ---- Linhas de dados ---- */
.sipoc-row {
  display: grid;
  grid-template-columns: repeat(5, 1fr) 28px;
  border-bottom: 1px solid #e2e8f0;
}
.sipoc-row:last-child { border-bottom: none; }
.sipoc-cell {
  padding: 4px;
  background: #fff;
  border-left: 1px solid #e2e8f0;
}
.sipoc-cell:first-child { border-left: none; }
.sipoc-cell-alt { background: #f8fafc; }
.sipoc-cell-input {
  width: 100%;
  resize: none;
  border: none;
  background: transparent;
  font-size: 12.5px;
  color: #1e293b;
  padding: 6px 8px;
  outline: none;
  line-height: 1.5;
  font-family: inherit;
}
.sipoc-cell-input:focus {
  background: #eff6ff;
  border-radius: 4px;
}
.sipoc-row-del {
  display: flex;
  align-items: center;
  justify-content: center;
  background: none;
  border: none;
  color: #cbd5e1;
  cursor: pointer;
  transition: color .15s;
}
.sipoc-row-del:hover { color: #ef4444; }

/* ---- Adicionar linha ---- */
.sipoc-add-row {
  padding: 10px 14px;
  border-top: 1px solid #e2e8f0;
  background: #f8fafc;
}
.sipoc-add-row-btn {
  display: flex;
  align-items: center;
  gap: 6px;
  font-size: 12.5px;
  font-weight: 500;
  color: #6366f1;
  background: none;
  border: none;
  cursor: pointer;
  padding: 4px 6px;
  border-radius: 6px;
  transition: background .15s;
}
.sipoc-add-row-btn:hover { background: #eef2ff; }

/* ---- Print ---- */
@media print {
  .sipoc-header-actions,
  .sipoc-add-row,
  .sipoc-row-del,
  #sidebar, header { display: none !important; }
  .sipoc-scroll { overflow: visible; padding: 0; }
  .sipoc-diagram { box-shadow: none; }
  .sipoc-cell-input { background: transparent !important; }
}
</style>
