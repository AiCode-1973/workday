<!DOCTYPE html>
<html lang="pt-BR" class="h-full bg-gray-50">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Criar Conta — Workday</title>
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
    </div>

    <div class="bg-white rounded-2xl shadow-2xl p-8">
      <?php if (!empty($errors)): ?>
        <div class="mb-4 p-3 bg-red-50 border border-red-200 text-red-700 rounded-lg text-sm space-y-1">
          <?php foreach ($errors as $e): ?><p><?= htmlspecialchars($e) ?></p><?php endforeach; ?>
        </div>
      <?php endif; ?>

      <h2 class="text-xl font-semibold text-gray-900 mb-6">Criar conta gratuita</h2>

      <form method="POST" action="<?= APP_URL ?>/register" class="space-y-4">
        <input type="hidden" name="_csrf" value="<?= $_SESSION['csrf_token'] ?>"/>

        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Nome completo</label>
          <input type="text" name="name" required autocomplete="name"
                 value="<?= htmlspecialchars($old['name'] ?? '') ?>"
                 placeholder="Seu nome" class="form-input"/>
        </div>

        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">E-mail</label>
          <input type="email" name="email" required
                 value="<?= htmlspecialchars($old['email'] ?? '') ?>"
                 placeholder="seu@email.com" class="form-input"/>
        </div>

        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Senha</label>
          <input type="password" name="password" required placeholder="Mín. 8 caracteres" class="form-input"/>
        </div>

        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Confirmar senha</label>
          <input type="password" name="password_confirm" required placeholder="Repita a senha" class="form-input"/>
        </div>

        <button type="submit" class="btn-primary w-full">Criar conta</button>
      </form>

      <p class="text-center text-sm text-gray-500 mt-6">
        Já tem conta? <a href="<?= APP_URL ?>/login" class="text-indigo-600 font-medium hover:underline">Entrar</a>
      </p>
    </div>
  </div>
</body>
</html>
