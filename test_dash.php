<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

define('ROOT', __DIR__);

// Autoloader
spl_autoload_register(function(string $class): void {
    $dirs = [ROOT.'/app/Controllers/', ROOT.'/app/Models/', ROOT.'/app/Services/', ROOT.'/config/'];
    foreach ($dirs as $d) {
        if (file_exists($d.$class.'.php')) { require_once $d.$class.'.php'; return; }
    }
});

require_once ROOT.'/config/config.php';
require_once ROOT.'/config/database.php';

try {
    $db = Database::getInstance();
    echo "Conexao OK\n";

    // Testa tabelas
    $tables = $db->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
    echo "Tabelas: " . implode(', ', $tables) . "\n";

    // Testa sessão simulada
    $wsId = 1;
    $userId = 1;

    $s = $db->prepare("SELECT COUNT(*) FROM boards WHERE workspace_id = ? AND archived_at IS NULL");
    $s->execute([$wsId]);
    echo "Boards: " . $s->fetchColumn() . "\n";

    $s = $db->prepare("SELECT COUNT(*) FROM workspace_members WHERE workspace_id = ?");
    $s->execute([$wsId]);
    echo "Members: " . $s->fetchColumn() . "\n";

    // Testa query de atividades (que usa workspace_id via JOIN)
    $s = $db->prepare("
        SELECT al.*, u.name AS user_name, u.avatar AS user_avatar,
               b.name AS board_name, i.title AS item_title
        FROM activity_logs al
        JOIN users u ON u.id = al.user_id
        LEFT JOIN boards b ON b.id = al.board_id
        LEFT JOIN items i ON i.id = al.item_id
        WHERE b.workspace_id = ?
        ORDER BY al.created_at DESC LIMIT 5
    ");
    $s->execute([$wsId]);
    $acts = $s->fetchAll();
    echo "Atividades: " . count($acts) . "\n";

    echo "TUDO OK\n";
} catch(Throwable $e) {
    echo "ERRO: " . $e->getMessage() . "\n";
    echo $e->getTraceAsString() . "\n";
}
