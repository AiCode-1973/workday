<?php
class BoardModel extends BaseModel {
    protected string $table = 'boards';

    public function getByWorkspace(int $workspaceId, int $userId): array {
        $stmt = $this->db->prepare("
            SELECT b.*, 
                   p.name AS portfolio_name,
                   p.color AS portfolio_color,
                   u.name AS creator_name,
                   (SELECT COUNT(*) FROM items i WHERE i.board_id = b.id AND i.archived_at IS NULL) AS total_items,
                   (SELECT COUNT(*) FROM items i
                    JOIN board_groups g ON g.id = i.group_id
                    WHERE i.board_id = b.id AND g.is_done = 1 AND i.archived_at IS NULL) AS done_items
            FROM boards b
            LEFT JOIN portfolios p ON p.id = b.portfolio_id
            JOIN users u ON u.id = b.created_by
            WHERE b.workspace_id = ?
              AND b.archived_at IS NULL
              AND (b.is_private = 0 OR b.created_by = ? OR EXISTS(
                SELECT 1 FROM board_members bm WHERE bm.board_id = b.id AND bm.user_id = ?
              ))
            ORDER BY p.name, b.name
        ");
        $stmt->execute([$workspaceId, $userId, $userId]);
        return $stmt->fetchAll();
    }

    public function getWithGroups(int $boardId): ?array {
        $board = $this->find($boardId);
        if (!$board) return null;

        $stmt = $this->db->prepare("SELECT * FROM board_groups WHERE board_id = ? ORDER BY position");
        $stmt->execute([$boardId]);
        $board['groups'] = $stmt->fetchAll();

        // campos personalizados
        $stmt = $this->db->prepare("SELECT * FROM board_fields WHERE board_id = ? ORDER BY position");
        $stmt->execute([$boardId]);
        $board['fields'] = $stmt->fetchAll();

        return $board;
    }

    public function canAccess(int $boardId, int $userId): bool {
        $stmt = $this->db->prepare("
            SELECT 1 FROM boards b
            LEFT JOIN workspace_members wm ON wm.workspace_id = b.workspace_id AND wm.user_id = ?
            LEFT JOIN board_members bm ON bm.board_id = b.id AND bm.user_id = ?
            WHERE b.id = ? AND b.archived_at IS NULL
              AND (b.is_private = 0 OR b.created_by = ? OR bm.user_id IS NOT NULL OR wm.role = 'admin')
        ");
        $stmt->execute([$userId, $userId, $boardId, $userId]);
        return (bool) $stmt->fetchColumn();
    }
}
