<?php
class AutomationController extends BaseController {

    public function index(string $boardId): void {
        $this->requireAuth();
        $db   = Database::getInstance();
        $stmt = $db->prepare("SELECT a.*, u.name AS creator_name FROM automations a JOIN users u ON u.id = a.created_by WHERE a.board_id=? ORDER BY a.id");
        $stmt->execute([(int)$boardId]);
        $this->json($stmt->fetchAll());
    }

    public function store(string $boardId): void {
        $this->requireAuth();
        $this->validateCsrf();
        $user = $this->currentUser();
        $data = $this->bodyJson();
        $bId  = (int)$boardId;

        $name = trim($data['name'] ?? '');
        if (!$name) $this->json(['error' => 'Nome obrigatório'], 422);
        if (empty($data['trigger'])) $this->json(['error' => 'Trigger obrigatório'], 422);
        if (empty($data['actions'])) $this->json(['error' => 'Actions obrigatórios'], 422);

        $db = Database::getInstance();
        $db->prepare("INSERT INTO automations (board_id,name,trigger,conditions,actions,created_by) VALUES (?,?,?,?,?,?)")
           ->execute([
               $bId, $name,
               json_encode($data['trigger']),
               json_encode($data['conditions'] ?? null),
               json_encode($data['actions']),
               $user['id'],
           ]);

        $this->json(['id' => (int)$db->lastInsertId(), 'message' => 'Automação criada'], 201);
    }

    public function update(string $boardId, string $id): void {
        $this->requireAuth();
        $this->validateCsrf();
        $data = $this->bodyJson();
        $db   = Database::getInstance();
        $aId  = (int)$id;
        $bId  = (int)$boardId;

        $allowed = ['name', 'is_active'];
        $sets    = [];
        $binds   = [];
        foreach ($allowed as $col) {
            if (isset($data[$col])) { $sets[] = "`{$col}`=?"; $binds[] = $data[$col]; }
        }
        if (isset($data['trigger']))    { $sets[] = "`trigger`=?";    $binds[] = json_encode($data['trigger']); }
        if (isset($data['actions']))    { $sets[] = "`actions`=?";    $binds[] = json_encode($data['actions']); }
        if (isset($data['conditions'])) { $sets[] = "`conditions`=?"; $binds[] = json_encode($data['conditions']); }

        if ($sets) {
            $binds[] = $aId;
            $db->prepare("UPDATE automations SET " . implode(',', $sets) . " WHERE id=? AND board_id={$bId}")
               ->execute($binds);
        }
        $this->json(['message' => 'Automação atualizada']);
    }

    public function destroy(string $boardId, string $id): void {
        $this->requireAuth();
        $this->validateCsrf();
        $db = Database::getInstance();
        $db->prepare("DELETE FROM automations WHERE id=? AND board_id=?")->execute([(int)$id, (int)$boardId]);
        $this->json(['message' => 'Automação removida']);
    }
}
