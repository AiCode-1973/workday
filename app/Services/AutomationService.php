<?php
/**
 * Motor de Automações "Se-Isso-Então-Aquilo"
 */
class AutomationService {

    private PDO $db;

    public function __construct() {
        $this->db = Database::getInstance();
    }

    public function process(int $boardId, string $event, array $context): void {
        $stmt = $this->db->prepare("SELECT * FROM automations WHERE board_id=? AND is_active=1");
        $stmt->execute([$boardId]);
        $automations = $stmt->fetchAll();

        foreach ($automations as $auto) {
            $trigger    = json_decode($auto['trigger'], true) ?? [];
            $conditions = json_decode($auto['conditions'] ?? 'null', true);
            $actions    = json_decode($auto['actions'], true) ?? [];

            if (!$this->matchesTrigger($trigger, $event, $context)) continue;
            if ($conditions && !$this->checkConditions($conditions, $context)) continue;

            foreach ($actions as $action) {
                $this->executeAction($action, $context, $auto);
            }
        }
    }

    private function matchesTrigger(array $trigger, string $event, array $context): bool {
        if (($trigger['event'] ?? '') !== $event) return false;

        // Para status_changed, verifica from/to
        if ($event === 'status_changed') {
            if (isset($trigger['to']) && (int)$trigger['to'] !== (int)($context['to'] ?? 0)) return false;
            if (isset($trigger['from']) && (int)$trigger['from'] !== (int)($context['from'] ?? 0)) return false;
        }

        return true;
    }

    private function checkConditions(array $conditions, array $context): bool {
        foreach ($conditions as $cond) {
            $field  = $cond['field']    ?? '';
            $op     = $cond['operator'] ?? '=';
            $value  = $cond['value']    ?? '';
            $actual = $context[$field]  ?? null;

            $match = match ($op) {
                '='    => $actual == $value,
                '!='   => $actual != $value,
                '>'    => $actual > $value,
                '<'    => $actual < $value,
                default => false,
            };
            if (!$match) return false;
        }
        return true;
    }

    private function executeAction(array $action, array $context, array $automation): void {
        switch ($action['type'] ?? '') {
            case 'notify_user':
                $this->actionNotifyUser($action, $context, $automation);
                break;
            case 'move_item':
                $this->actionMoveItem($action, $context);
                break;
            case 'set_priority':
                $this->actionSetPriority($action, $context);
                break;
            case 'set_assignee':
                $this->actionSetAssignee($action, $context);
                break;
            case 'create_item':
                $this->actionCreateItem($action, $context, $automation);
                break;
        }
    }

    private function actionNotifyUser(array $action, array $context, array $auto): void {
        $userIds = (array)($action['user_ids'] ?? []);
        if (empty($userIds) && !empty($context['item_id'])) {
            // Notifica assignees do item
            $stmt = $this->db->prepare("SELECT user_id FROM item_assignees WHERE item_id=?");
            $stmt->execute([$context['item_id']]);
            $userIds = $stmt->fetchAll(PDO::FETCH_COLUMN);
        }

        $title = $action['title'] ?? "Automação: {$auto['name']}";
        $body  = $action['body']  ?? '';
        $link  = !empty($context['item_id']) ? "/items/{$context['item_id']}" : null;

        foreach ($userIds as $uid) {
            $this->db->prepare("INSERT INTO notifications (user_id,type,title,body,link) VALUES (?,?,?,?,?)")
                     ->execute([$uid, 'automation', $title, $body, $link]);
        }
    }

    private function actionMoveItem(array $action, array $context): void {
        $itemId  = $context['item_id'] ?? null;
        $groupId = $action['group_id'] ?? null;
        if (!$itemId || !$groupId) return;

        $this->db->prepare("UPDATE items SET group_id=? WHERE id=?")->execute([$groupId, $itemId]);
    }

    private function actionSetPriority(array $action, array $context): void {
        $itemId   = $context['item_id'] ?? null;
        $priority = $action['priority'] ?? 'none';
        if (!$itemId) return;
        $this->db->prepare("UPDATE items SET priority=? WHERE id=?")->execute([$priority, $itemId]);
    }

    private function actionSetAssignee(array $action, array $context): void {
        $itemId  = $context['item_id'] ?? null;
        $userIds = (array)($action['user_ids'] ?? []);
        if (!$itemId || empty($userIds)) return;
        foreach ($userIds as $uid) {
            $this->db->prepare("INSERT IGNORE INTO item_assignees (item_id,user_id) VALUES (?,?)")
                     ->execute([$itemId, (int)$uid]);
        }
    }

    private function actionCreateItem(array $action, array $context, array $auto): void {
        $groupId = $action['group_id'] ?? null;
        $title   = $action['title']    ?? 'Item criado por automação';
        if (!$groupId) return;

        $stmt = $this->db->prepare("SELECT board_id FROM board_groups WHERE id=?");
        $stmt->execute([$groupId]);
        $row = $stmt->fetch();
        if (!$row) return;

        $this->db->prepare("INSERT INTO items (board_id,group_id,title,created_by,position) VALUES (?,?,?,?,0)")
                 ->execute([$row['board_id'], $groupId, $title, $auto['created_by']]);
    }
}
