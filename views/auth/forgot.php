<!DOCTYPE html>
<html lang="pt-BR" class="h-full bg-gray-50">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Recuperar senha — Workday</title>
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
    </div>

    <div class="bg-white rounded-2xl shadow-2xl p-8">

      <?php if (!empty($success)): ?>
        <div class="mb-4 p-3 bg-green-50 border border-green-200 text-green-700 rounded-lg text-sm">
          <?= htmlspecialchars($success) ?>
        </div>
      <?php endif; ?>

      <h2 class="text-xl font-semibold text-gray-900 mb-2">Recuperar senha</h2>
      <p class="text-sm text-gray-500 mb-6">Digite seu e-mail para receber as instruções.</p>

      <form method="POST" action="<?= APP_URL ?>/forgot-password" class="space-y-4">
        <input type="hidden" name="_csrf" value="<?= $_SESSION['csrf_token'] ?>"/>
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">E-mail</label>
          <input type="email" name="email" required class="form-input" placeholder="seu@email.com"/>
        </div>
        <button type="submit" class="btn-primary w-full">Enviar instruções</button>
      </form>

      <p class="text-center text-sm text-gray-500 mt-6">
        <a href="<?= APP_URL ?>/login" class="text-indigo-600 font-medium hover:underline">← Voltar ao login</a>
      </p>
    </div>
  </div>
</body>
</html>
