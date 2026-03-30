<?php

class ReportController extends BaseController
{
    public function index(): void
    {
        $user = $this->requireAuth();
        $db   = Database::getInstance();

        // Stats gerais do workspace do usuário
        $wsId = $_SESSION['workspace_id'];

        $stats = [
            'total_boards' => $db->query("SELECT COUNT(*) FROM boards WHERE workspace_id = {$wsId} AND archived_at IS NULL")->fetchColumn(),
            'open_items'   => $db->query("SELECT COUNT(*) FROM items i JOIN boards b ON b.id = i.board_id WHERE b.workspace_id = {$wsId} AND i.status != 'done' AND i.archived_at IS NULL")->fetchColumn(),
            'done_items'   => $db->query("SELECT COUNT(*) FROM items i JOIN boards b ON b.id = i.board_id WHERE b.workspace_id = {$wsId} AND i.status = 'done' AND i.archived_at IS NULL")->fetchColumn(),
            'overdue'      => $db->query("SELECT COUNT(*) FROM items i JOIN boards b ON b.id = i.board_id WHERE b.workspace_id = {$wsId} AND i.due_date < NOW() AND i.status != 'done' AND i.archived_at IS NULL")->fetchColumn(),
        ];

        // Itens por prioridade
        $byPriority = $db->query(
            "SELECT i.priority, COUNT(*) AS total
             FROM items i JOIN boards b ON b.id = i.board_id
             WHERE b.workspace_id = {$wsId} AND i.archived_at IS NULL
             GROUP BY i.priority ORDER BY FIELD(i.priority,'urgent','high','medium','low','none')"
        )->fetchAll();

        // Itens por membro
        $byMember = $db->query(
            "SELECT u.name, u.email, COUNT(DISTINCT ia.item_id) AS total
             FROM users u
             JOIN item_assignees ia ON ia.user_id = u.id
             JOIN items i ON i.id = ia.item_id
             JOIN boards b ON b.id = i.board_id
             WHERE b.workspace_id = {$wsId} AND i.archived_at IS NULL
             GROUP BY u.id ORDER BY total DESC LIMIT 20"
        )->fetchAll();

        // Itens criados por dia (últimos 30 dias)
        $byDay = $db->query(
            "SELECT DATE(i.created_at) AS day, COUNT(*) AS total
             FROM items i JOIN boards b ON b.id = i.board_id
             WHERE b.workspace_id = {$wsId}
               AND i.created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
             GROUP BY DATE(i.created_at) ORDER BY day ASC"
        )->fetchAll();

        $this->view('layouts/app', [
            'content'    => 'reports/index',
            'stats'      => $stats,
            'byPriority' => $byPriority,
            'byMember'   => $byMember,
            'byDay'      => $byDay,
            'pageTitle'  => 'Relatórios',
        ]);
    }

    /** Export CSV de itens de um quadro */
    public function exportCsv(): void
    {
        $this->requireAuth();
        $boardId = (int) ($_GET['board_id'] ?? 0);
        if (!$boardId) { http_response_code(400); echo 'board_id obrigatório'; return; }

        $boardModel = new BoardModel();
        $userId     = $_SESSION['user_id'];
        if (!$boardModel->canAccess($boardId, $userId)) { http_response_code(403); echo 'Acesso negado'; return; }

        $db    = Database::getInstance();
        $items = $db->prepare(
            "SELECT i.id, i.title, i.status, i.priority, i.due_date, i.created_at,
                    bg.name AS group_name,
                    GROUP_CONCAT(DISTINCT u.name ORDER BY u.name SEPARATOR ', ') AS assignees
             FROM items i
             LEFT JOIN board_groups bg ON bg.id = i.group_id
             LEFT JOIN item_assignees ia ON ia.item_id = i.id
             LEFT JOIN users u ON u.id = ia.user_id
             WHERE i.board_id = ? AND i.archived_at IS NULL
             GROUP BY i.id
             ORDER BY bg.position, i.position"
        );
        $items->execute([$boardId]);
        $rows = $items->fetchAll(PDO::FETCH_ASSOC);

        $board = $db->prepare("SELECT name FROM boards WHERE id = ?");
        $board->execute([$boardId]);
        $boardName = preg_replace('/[^a-z0-9_-]/i', '_', $board->fetchColumn() ?: 'board');

        header('Content-Type: text/csv; charset=UTF-8');
        header('Content-Disposition: attachment; filename="' . $boardName . '_' . date('Y-m-d') . '.csv"');
        header('Pragma: no-cache');

        $out = fopen('php://output', 'w');
        fprintf($out, chr(0xEF) . chr(0xBB) . chr(0xBF)); // UTF-8 BOM para Excel

        fputcsv($out, ['ID', 'Título', 'Grupo', 'Status', 'Prioridade', 'Responsáveis', 'Prazo', 'Criado em']);
        foreach ($rows as $r) {
            fputcsv($out, [
                $r['id'],
                $r['title'],
                $r['group_name'] ?? '',
                $r['status'],
                $r['priority'],
                $r['assignees'] ?? '',
                $r['due_date'] ? date('d/m/Y', strtotime($r['due_date'])) : '',
                date('d/m/Y H:i', strtotime($r['created_at'])),
            ]);
        }
        fclose($out);
    }
}
