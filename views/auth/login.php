<!DOCTYPE html>
<html lang="pt-BR" class="h-full bg-gray-50">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title><?= htmlspecialchars($pageTitle ?? 'Entrar') ?> — Workday</title>
  <link rel="preconnect" href="https://fonts.googleapis.com"/>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet"/>
  <link rel="stylesheet" href="<?= APP_URL ?>/css/app.css"/>
</head>
<body class="h-full font-inter bg-gradient-to-br from-gray-900 to-indigo-950 flex items-center justify-center p-4">
  <div class="w-full max-w-md">
    <div class="text-center mb-8">
      <div class="inline-flex items-center justify-center w-12 h-12 rounded-2xl bg-indigo-500 mb-4">
        <span class="text-white font-bold text-xl">W</span>
      </div>
      <h1 class="text-2xl font-bold text-white">Workday</h1>
      <p class="text-gray-400 text-sm mt-1">Gestão de projetos simplificada</p>
    </div>

    <div class="bg-white rounded-2xl shadow-2xl p-8">

      <?php if (!empty($error)): ?>
        <div class="mb-4 p-3 bg-red-50 border border-red-200 text-red-700 rounded-lg text-sm">
          <?= htmlspecialchars($error) ?>
        </div>
      <?php endif; ?>

      <h2 class="text-xl font-semibold text-gray-900 mb-6">Entrar na conta</h2>

      <form method="POST" action="<?= APP_URL ?>/login" class="space-y-4">
        <input type="hidden" name="_csrf" value="<?= $_SESSION['csrf_token'] ?>"/>

        <div>
          <label for="email" class="block text-sm font-medium text-gray-700 mb-1">E-mail</label>
          <input type="email" id="email" name="email" required autocomplete="email"
                 placeholder="seu@email.com"
                 class="form-input"/>
        </div>

        <div>
          <label for="password" class="block text-sm font-medium text-gray-700 mb-1">Senha</label>
          <div class="relative">
            <input type="password" id="password" name="password" required autocomplete="current-password"
                   placeholder="••••••••"
                   class="form-input pr-10"/>
            <button type="button" onclick="togglePass()" class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600">
              <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
            </button>
          </div>
        </div>

        <div class="flex items-center justify-between">
          <label class="flex items-center gap-2 text-sm text-gray-600">
            <input type="checkbox" class="rounded border-gray-300 text-indigo-600"/>
            Lembrar de mim
          </label>
          <a href="<?= APP_URL ?>/forgot-password" class="text-sm text-indigo-600 hover:underline">Esqueci a senha</a>
        </div>

        <button type="submit" class="btn-primary w-full">Entrar</button>
      </form>

      <p class="text-center text-sm text-gray-500 mt-6">
        Não tem conta?
        <a href="<?= APP_URL ?>/register" class="text-indigo-600 font-medium hover:underline">Criar conta gratuita</a>
      </p>
    </div>
  </div>

  <script>
    function togglePass() {
      const inp = document.getElementById('password');
      inp.type  = inp.type === 'password' ? 'text' : 'password';
    }
  </script>
</body>
</html>
