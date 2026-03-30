<?php
class DashboardController extends BaseController {

    public function index(): void {
        $this->requireAuth();
        $user        = $this->currentUser();
        $workspaceId = $_SESSION['workspace_id'] ?? 0;

        $db = Database::getInstance();

        // Boards do workspace
        $boardModel = new BoardModel();
        $boards     = $boardModel->getByWorkspace($workspaceId, $user['id']);

        // Meus itens atrasados
        $stmt = $db->prepare("
            SELECT i.id, i.title, i.due_date, i.priority,
                   b.name AS board_name, g.name AS group_name, g.color AS group_color
            FROM items i
            JOIN boards b ON b.id = i.board_id
            JOIN board_groups g ON g.id = i.group_id
            JOIN item_assignees ia ON ia.item_id = i.id
            WHERE ia.user_id = ? AND b.workspace_id = ?
              AND i.due_date < CURDATE() AND i.done_at IS NULL AND i.archived_at IS NULL
            ORDER BY i.due_date ASC LIMIT 10
        ");
        $stmt->execute([$user['id'], $workspaceId]);
        $overdueItems = $stmt->fetchAll();

        // Itens atribuídos a mim (próximos 7 dias)
        $stmt = $db->prepare("
            SELECT i.id, i.title, i.due_date, i.priority,
                   b.name AS board_name, g.name AS group_name, g.color AS group_color
            FROM items i
            JOIN boards b ON b.id = i.board_id
            JOIN board_groups g ON g.id = i.group_id
            JOIN item_assignees ia ON ia.item_id = i.id
            WHERE ia.user_id = ? AND b.workspace_id = ?
              AND i.due_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 7 DAY)
              AND i.done_at IS NULL AND i.archived_at IS NULL
            ORDER BY i.due_date ASC LIMIT 10
        ");
        $stmt->execute([$user['id'], $workspaceId]);
        $upcomingItems = $stmt->fetchAll();

        // Notificações não lidas
        $stmt = $db->prepare("
            SELECT * FROM notifications WHERE user_id = ? AND read_at IS NULL
            ORDER BY created_at DESC LIMIT 20
        ");
        $stmt->execute([$user['id']]);
        $notifications = $stmt->fetchAll();

        // Atividades recentes
        $stmt = $db->prepare("
            SELECT al.*, u.name AS user_name, u.avatar AS user_avatar,
                   b.name AS board_name, i.title AS item_title
            FROM activity_logs al
            JOIN users u ON u.id = al.user_id
            LEFT JOIN boards b ON b.id = al.board_id
            LEFT JOIN items i ON i.id = al.item_id
            WHERE b.workspace_id = ?
            ORDER BY al.created_at DESC LIMIT 20
        ");
        $stmt->execute([$workspaceId]);
        $activities = $stmt->fetchAll();

        // Contagens rápidas
        $stmt = $db->prepare("SELECT COUNT(*) FROM boards WHERE workspace_id = ? AND archived_at IS NULL");
        $stmt->execute([$workspaceId]);
        $totalBoards = $stmt->fetchColumn();

        $stmt = $db->prepare("
            SELECT COUNT(*) FROM items i JOIN boards b ON b.id = i.board_id
            WHERE b.workspace_id = ? AND i.done_at IS NULL AND i.archived_at IS NULL
        ");
        $stmt->execute([$workspaceId]);
        $openItems = $stmt->fetchColumn();

        $stmt = $db->prepare("SELECT COUNT(*) FROM workspace_members WHERE workspace_id = ?");
        $stmt->execute([$workspaceId]);
        $totalMembers = $stmt->fetchColumn();

        $this->view('layouts/app', [
            'pageTitle'     => 'Dashboard',
            'content'       => 'dashboard/index',
            'boards'        => $boards,
            'overdueItems'  => $overdueItems,
            'upcomingItems' => $upcomingItems,
            'notifications' => $notifications,
            'activities'    => $activities,
            'stats'         => compact('totalBoards','openItems','totalMembers'),
        ]);
    }
}
