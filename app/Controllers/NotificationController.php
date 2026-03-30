<?php
class NotificationController extends BaseController {

    public function index(): void {
        $this->requireAuth();
        $user  = $this->currentUser();
        $db    = Database::getInstance();
        $page  = max(1, (int)($_GET['page'] ?? 1));
        $limit = 20;
        $offset= ($page - 1) * $limit;

        $stmt  = $db->prepare("SELECT * FROM notifications WHERE user_id=? ORDER BY created_at DESC LIMIT ? OFFSET ?");
        $stmt->execute([$user['id'], $limit, $offset]);
        $notifications = $stmt->fetchAll();

        $stmt  = $db->prepare("SELECT COUNT(*) FROM notifications WHERE user_id=? AND read_at IS NULL");
        $stmt->execute([$user['id']]);
        $unread = (int)$stmt->fetchColumn();

        $this->json(['data' => $notifications, 'unread' => $unread]);
    }

    public function markRead(string $id): void {
        $this->requireAuth();
        $this->validateCsrf();
        $user = $this->currentUser();
        $db   = Database::getInstance();
        $db->prepare("UPDATE notifications SET read_at=NOW() WHERE id=? AND user_id=?")
           ->execute([(int)$id, $user['id']]);
        $this->json(['message' => 'Marcado como lido']);
    }

    public function markAllRead(): void {
        $this->requireAuth();
        $this->validateCsrf();
        $user = $this->currentUser();
        $db   = Database::getInstance();
        $db->prepare("UPDATE notifications SET read_at=NOW() WHERE user_id=? AND read_at IS NULL")
           ->execute([$user['id']]);
        $this->json(['message' => 'Todas marcadas como lidas']);
    }
}
