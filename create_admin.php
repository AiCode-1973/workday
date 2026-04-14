<?php
/**
 * Criador de administrador via CLI
 * Uso: php create_admin.php
 *
 * NUNCA exponha este arquivo via web — ele só funciona via linha de comando.
 */

// Bloqueia execução via web
if (php_sapi_name() !== 'cli') {
    http_response_code(403);
    exit("Acesso negado.\n");
}

// Carrega configurações e banco
define('BASE_PATH', __DIR__);
require BASE_PATH . '/config/config.php';
require BASE_PATH . '/config/database.php';

// ── Helpers de leitura ────────────────────────────────────────────────────

function prompt(string $label, bool $required = true): string {
    do {
        echo $label;
        $value = trim(fgets(STDIN));
        if ($required && $value === '') {
            echo "  !! Campo obrigatório.\n";
        }
    } while ($required && $value === '');
    return $value;
}

function promptSecret(string $label): string {
    // Windows não tem stty; lê normalmente (senha aparece)
    if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
        echo $label . ' (a senha ficará visível no terminal): ';
        return trim(fgets(STDIN));
    }
    // Unix: esconde a digitação
    echo $label;
    system('stty -echo');
    $value = trim(fgets(STDIN));
    system('stty echo');
    echo "\n";
    return $value;
}

// ── Entrada do usuário ────────────────────────────────────────────────────

echo "\n========================================\n";
echo "  Workday — Criar Administrador\n";
echo "========================================\n\n";

$name  = prompt("Nome completo: ");
$email = prompt("E-mail: ");

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    exit("  !! E-mail inválido.\n\n");
}

$password = promptSecret("Senha (mín. 8 caracteres): ");

if (strlen($password) < 8) {
    exit("  !! Senha muito curta (mínimo 8 caracteres).\n\n");
}

$confirm = promptSecret("Confirme a senha: ");

if ($password !== $confirm) {
    exit("  !! As senhas não conferem.\n\n");
}

// ── Verificações no banco ─────────────────────────────────────────────────

try {
    $db = Database::getInstance();
} catch (Exception $e) {
    exit("  !! Erro ao conectar ao banco de dados: " . $e->getMessage() . "\n\n");
}

$stmt = $db->prepare("SELECT id FROM users WHERE email = ?");
$stmt->execute([$email]);
if ($stmt->fetch()) {
    exit("  !! Já existe um usuário com esse e-mail.\n\n");
}

// ── Criação do usuário ────────────────────────────────────────────────────

$hash = password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);

$db->prepare("
    INSERT INTO users (name, email, password, role, is_active, email_verified, timezone, created_at, updated_at)
    VALUES (?, ?, ?, 'admin', 1, 1, 'America/Sao_Paulo', NOW(), NOW())
")->execute([$name, $email, $hash]);

$userId = (int) $db->lastInsertId();

// Cria workspace pessoal
$slug = strtolower(preg_replace('/[^a-z0-9]+/i', '-', $name)) . '-' . $userId;
$db->prepare("INSERT INTO workspaces (name, slug, owner_id, created_at, updated_at) VALUES (?, ?, ?, NOW(), NOW())")
   ->execute(["Workspace de {$name}", $slug, $userId]);
$wsId = (int) $db->lastInsertId();

$db->prepare("INSERT INTO workspace_members (workspace_id, user_id, role, joined_at) VALUES (?, ?, 'admin', NOW())")
   ->execute([$wsId, $userId]);

// ── Resultado ─────────────────────────────────────────────────────────────

echo "\n  Administrador criado com sucesso!\n";
echo "  ----------------------------------\n";
echo "  ID      : {$userId}\n";
echo "  Nome    : {$name}\n";
echo "  E-mail  : {$email}\n";
echo "  Papel   : admin\n";
echo "  URL     : " . (defined('APP_URL') ? APP_URL : 'http://localhost/workday') . "/login\n";
echo "\n";
