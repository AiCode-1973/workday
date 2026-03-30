<!-- Perfil -->

<div class="p-6 max-w-3xl mx-auto">
  <h1 class="text-2xl font-bold text-gray-900 mb-6">Meu perfil</h1>

  <?php if (!empty($flash['success'])): ?>
  <div class="mb-4 p-3 bg-green-50 border border-green-200 text-green-700 rounded-lg text-sm">
    <?= htmlspecialchars($flash['success']) ?>
  </div>
  <?php endif; ?>

  <!-- Perfil -->
  <div class="card mb-6">
    <h2 class="font-semibold text-gray-800 mb-4">Informações pessoais</h2>
    <form id="profileForm" enctype="multipart/form-data" class="space-y-4">
      <input type="hidden" name="_csrf" value="<?= $_SESSION['csrf_token'] ?>"/>

      <!-- Avatar -->
      <div class="flex items-center gap-4">
        <div id="avatarPreview" class="w-16 h-16 rounded-full bg-indigo-100 flex items-center justify-center overflow-hidden text-indigo-600 font-bold text-xl">
          <?php if ($profile['avatar']): ?>
          <img src="<?= APP_URL ?>/uploads/<?= htmlspecialchars($profile['avatar']) ?>" class="w-full h-full object-cover" id="avatarImg"/>
          <?php else: ?>
          <span id="avatarInitial"><?= mb_strtoupper(mb_substr($profile['name'], 0, 1)) ?></span>
          <?php endif; ?>
        </div>
        <div>
          <label class="btn-secondary cursor-pointer text-sm">
            Alterar foto
            <input type="file" name="avatar" id="avatarInput" class="hidden" accept="image/*"/>
          </label>
          <p class="text-xs text-gray-400 mt-1">JPG, PNG, GIF ou WebP · máx 2MB</p>
        </div>
      </div>

      <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">Nome</label>
        <input type="text" name="name" class="form-input" value="<?= htmlspecialchars($profile['name']) ?>" required/>
      </div>
      <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">E-mail</label>
        <input type="email" name="email" class="form-input" value="<?= htmlspecialchars($profile['email']) ?>" required/>
      </div>

      <button type="submit" class="btn-primary">Salvar alterações</button>
    </form>
  </div>

  <!-- Senha -->
  <div class="card mb-6">
    <h2 class="font-semibold text-gray-800 mb-4">Alterar senha</h2>
    <form id="passwordForm" class="space-y-4">
      <input type="hidden" name="_csrf" value="<?= $_SESSION['csrf_token'] ?>"/>
      <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">Senha atual</label>
        <input type="password" name="current_password" class="form-input" required/>
      </div>
      <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">Nova senha</label>
        <input type="password" name="new_password" class="form-input" minlength="8" required/>
      </div>
      <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">Confirmar nova senha</label>
        <input type="password" name="confirm_password" class="form-input" required/>
      </div>
      <button type="submit" class="btn-primary">Alterar senha</button>
    </form>
  </div>

  <!-- Tokens API -->
  <div class="card">
    <div class="flex items-center justify-between mb-4">
      <h2 class="font-semibold text-gray-800">Tokens de API</h2>
      <button onclick="createApiToken()" class="btn-secondary text-sm">+ Novo token</button>
    </div>
    <div id="tokenList">
      <?php if ($apiTokens): ?>
      <?php foreach ($apiTokens as $t): ?>
      <div class="flex items-center justify-between py-2 border-b last:border-0" id="token-<?= $t['id'] ?>">
        <div>
          <div class="font-medium text-sm"><?= htmlspecialchars($t['name']) ?></div>
          <div class="text-xs text-gray-400">
            Criado em <?= date('d/m/Y', strtotime($t['created_at'])) ?>
            <?= $t['last_used_at'] ? ' · Último uso: ' . date('d/m/Y', strtotime($t['last_used_at'])) : '' ?>
          </div>
        </div>
        <button onclick="revokeToken(<?= $t['id'] ?>)" class="text-red-500 text-sm hover:underline">Revogar</button>
      </div>
      <?php endforeach; ?>
      <?php else: ?>
      <p class="text-gray-400 text-sm" id="noTokens">Nenhum token criado ainda.</p>
      <?php endif; ?>
    </div>
  </div>
</div>

<script>
// Avatar preview
document.getElementById('avatarInput')?.addEventListener('change', e => {
  const f = e.target.files[0];
  if (!f) return;
  const r = new FileReader();
  r.onload = ev => {
    const p = document.getElementById('avatarPreview');
    p.innerHTML = `<img src="${ev.target.result}" class="w-full h-full object-cover"/>`;
  };
  r.readAsDataURL(f);
});

// Atualizar perfil
document.getElementById('profileForm')?.addEventListener('submit', async e => {
  e.preventDefault();
  const fd = new FormData(e.target);
  try {
    const res = await fetch(`${APP_URL}/profile`, { method: 'POST', headers: { 'X-CSRF-Token': document.querySelector('meta[name="csrf-token"]').content }, body: fd });
    const data = await res.json();
    if (data.error) { WorkdayApp.toast(data.error, 'error'); return; }
    WorkdayApp.toast('Perfil atualizado!', 'success');
  } catch { WorkdayApp.toast('Erro ao salvar perfil.', 'error'); }
});

// Alterar senha
document.getElementById('passwordForm')?.addEventListener('submit', async e => {
  e.preventDefault();
  const fd = new FormData(e.target);
  const body = Object.fromEntries(fd.entries());
  try {
    const data = await WorkdayApp.api('POST', `${APP_URL}/profile/password`, body);
    WorkdayApp.toast('Senha alterada com sucesso!', 'success');
    e.target.reset();
  } catch (err) { WorkdayApp.toast(err.message, 'error'); }
});

// Criar token
async function createApiToken() {
  const name = prompt('Nome do token (ex: Integração Zapier):');
  if (!name) return;
  try {
    const data = await WorkdayApp.api('POST', `${APP_URL}/profile/tokens`, { name });
    document.getElementById('noTokens')?.remove();
    const div = document.createElement('div');
    div.className = 'py-3 border-b';
    div.innerHTML = `<p class="text-sm font-medium mb-1">${escHtml(data.name)}</p>
      <p class="text-xs text-gray-500 mb-2">Copie agora — não será exibido novamente:</p>
      <code class="block bg-gray-100 rounded p-2 text-xs break-all select-all">${escHtml(data.token)}</code>`;
    document.getElementById('tokenList').prepend(div);
    WorkdayApp.toast('Token criado! Copie agora.', 'success');
  } catch (err) { WorkdayApp.toast(err.message, 'error'); }
}

// Revogar token
async function revokeToken(id) {
  if (!confirm('Revogar este token?')) return;
  try {
    await WorkdayApp.api('DELETE', `${APP_URL}/profile/tokens?id=${id}`, null);
    document.getElementById('token-' + id)?.remove();
    WorkdayApp.toast('Token revogado.', 'success');
  } catch (err) { WorkdayApp.toast(err.message, 'error'); }
}
</script>

