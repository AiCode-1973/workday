<?php
class ItemModel extends BaseModel {
    protected string $table = 'items';

    public function getByBoard(int $boardId, array $filters = []): array {
        $where  = ['i.board_id = ?', 'i.archived_at IS NULL', 'i.parent_id IS NULL'];
        $binds  = [$boardId];

        if (!empty($filters['group_id'])) {
            $where[] = 'i.group_id = ?';
            $binds[] = $filters['group_id'];
        }
        if (!empty($filters['assignee'])) {
            $where[] = 'EXISTS(SELECT 1 FROM item_assignees ia WHERE ia.item_id = i.id AND ia.user_id = ?)';
            $binds[] = $filters['assignee'];
        }
        if (!empty($filters['priority'])) {
            $where[] = 'i.priority = ?';
            $binds[] = $filters['priority'];
        }

        $sql = "
            SELECT i.*,
                   g.name AS group_name, g.color AS group_color,
                   u.name AS creator_name,
                   (SELECT GROUP_CONCAT(CONCAT(usr.id,'|',usr.name,'|',COALESCE(usr.avatar,'')) SEPARATOR ',')
                    FROM item_assignees ia JOIN users usr ON usr.id = ia.user_id WHERE ia.item_id = i.id) AS assignees_raw,
                   (SELECT COUNT(*) FROM items sub WHERE sub.parent_id = i.id AND sub.archived_at IS NULL) AS subtask_count,
                   (SELECT COUNT(*) FROM comments c WHERE c.item_id = i.id) AS comment_count,
                   (SELECT COUNT(*) FROM attachments a WHERE a.item_id = i.id) AS attachment_count
            FROM items i
            JOIN board_groups g ON g.id = i.group_id
            JOIN users u ON u.id = i.created_by
            WHERE " . implode(' AND ', $where) . "
            ORDER BY i.group_id, i.position, i.created_at
        ";
        $stmt = $this->db->prepare($sql);
        $stmt->execute($binds);
        $rows = $stmt->fetchAll();

        // Parseia assignees
        foreach ($rows as &$row) {
            $row['assignees'] = $this->parseAssignees($row['assignees_raw'] ?? '');
            unset($row['assignees_raw']);
        }
        return $rows;
    }

    public function getDetail(int $itemId): ?array {
        $stmt = $this->db->prepare("
            SELECT i.*, g.name AS group_name, g.color AS group_color, b.name AS board_name
            FROM items i
            JOIN board_groups g ON g.id = i.group_id
            JOIN boards b ON b.id = i.board_id
            WHERE i.id = ? AND i.archived_at IS NULL
        ");
        $stmt->execute([$itemId]);
        $item = $stmt->fetch();
        if (!$item) return null;

        // Assignees
        $stmt = $this->db->prepare("
            SELECT u.id, u.name, u.avatar FROM item_assignees ia
            JOIN users u ON u.id = ia.user_id WHERE ia.item_id = ?
        ");
        $stmt->execute([$itemId]);
        $item['assignees'] = $stmt->fetchAll();

        // Subtarefas
        $stmt = $this->db->prepare("
            SELECT i.*, GROUP_CONCAT(CONCAT(u.id,'|',u.name) SEPARATOR ',') AS assignees_raw
            FROM items i
            LEFT JOIN item_assignees ia ON ia.item_id = i.id
            LEFT JOIN users u ON u.id = ia.user_id
            WHERE i.parent_id = ? AND i.archived_at IS NULL
            GROUP BY i.id ORDER BY i.position
        ");
        $stmt->execute([$itemId]);
        $subs = $stmt->fetchAll();
        foreach ($subs as &$sub) {
            $sub['assignees'] = $this->parseAssignees($sub['assignees_raw'] ?? '');
            unset($sub['assignees_raw']);
        }
        $item['subtasks'] = $subs;

        // Valores de campos personalizados
        $stmt = $this->db->prepare("
            SELECT ifv.field_id, bf.name, bf.type, bf.options, ifv.value
            FROM item_field_values ifv
            JOIN board_fields bf ON bf.id = ifv.field_id
            WHERE ifv.item_id = ?
        ");
        $stmt->execute([$itemId]);
        $item['field_values'] = $stmt->fetchAll();

        // Labels
        $stmt = $this->db->prepare("
            SELECT l.id, l.name, l.color FROM item_labels il JOIN labels l ON l.id = il.label_id WHERE il.item_id = ?
        ");
        $stmt->execute([$itemId]);
        $item['labels'] = $stmt->fetchAll();

        return $item;
    }

    public function moveToGroup(int $itemId, int $groupId, int $position): bool {
        return $this->update($itemId, ['group_id' => $groupId, 'position' => $position]);
    }

    public function setFieldValue(int $itemId, int $fieldId, string $value): void {
        $stmt = $this->db->prepare("
            INSERT INTO item_field_values (item_id, field_id, value)
            VALUES (?,?,?)
            ON DUPLICATE KEY UPDATE value = VALUES(value)
        ");
        $stmt->execute([$itemId, $fieldId, $value]);
    }

    private function parseAssignees(string $raw): array {
        if (!$raw) return [];
        return array_map(function ($part) {
            [$id, $name, $avatar] = explode('|', $part) + ['', '', ''];
            return ['id' => (int)$id, 'name' => $name, 'avatar' => $avatar ?: null];
        }, explode(',', $raw));
    }
}
