<?php
class ItemController extends BaseController {

    public function index(string $boardId): void {
        $this->requireAuth();
        $itemModel = new ItemModel();
        $items     = $itemModel->getByBoard((int)$boardId, $_GET);
        $this->json($items);
    }

    public function show(string $id): void {
        $this->requireAuth();
        $itemModel = new ItemModel();
        $item      = $itemModel->getDetail((int)$id);
        if (!$item) $this->json(['error' => 'Item não encontrado'], 404);

        // Carrega dados de ferramenta vinculados ao item
        $db   = Database::getInstance();
        $stmt = $db->prepare("SELECT tool_type, content FROM item_tool_data WHERE item_id = ? LIMIT 1");
        $stmt->execute([(int)$id]);
        $toolRow = $stmt->fetch();
        if ($toolRow) {
            $item['tool_type']    = $toolRow['tool_type'];
            $item['tool_content'] = json_decode($toolRow['content'], true);
        }

        $this->json($item);
    }

    public function saveToolData(string $id): void {
        $this->requireAuth();
        $this->validateCsrf();
        $itemId   = (int)$id;
        $data     = $this->bodyJson();
        $toolType = $data['tool_type'] ?? 'sipoc';
        $content  = $data['content']   ?? null;

        if (!in_array($toolType, ['sipoc'], true)) {
            $this->json(['error' => 'Tipo de ferramenta inválido'], 422);
        }

        $db      = Database::getInstance();
        $encoded = json_encode($content, JSON_UNESCAPED_UNICODE);

        $check = $db->prepare("SELECT id FROM item_tool_data WHERE item_id = ? AND tool_type = ?");
        $check->execute([$itemId, $toolType]);
        if ($check->fetch()) {
            $db->prepare("UPDATE item_tool_data SET content = ? WHERE item_id = ? AND tool_type = ?")
               ->execute([$encoded, $itemId, $toolType]);
        } else {
            $db->prepare("INSERT INTO item_tool_data (item_id, tool_type, content) VALUES (?,?,?)")
               ->execute([$itemId, $toolType, $encoded]);
        }

        $this->json(['message' => 'Dados salvos']);
    }

    public function store(string $boardId): void {
        $this->requireAuth();
        $this->validateCsrf();
        $user    = $this->currentUser();
        $data    = $this->bodyJson();
        $bId     = (int)$boardId;

        $title = trim($data['title'] ?? '');
        if (!$title) $this->json(['error' => 'Título obrigatório'], 422);
        if (empty($data['group_id'])) $this->json(['error' => 'group_id obrigatório'], 422);

        $itemModel = new ItemModel();

        // Posição no final do grupo
        $db   = Database::getInstance();
        $stmt = $db->prepare("SELECT MAX(position)+1 FROM items WHERE group_id=? AND archived_at IS NULL");
        $stmt->execute([$data['group_id']]);
        $pos  = (int)($stmt->fetchColumn() ?? 0);

        $itemId = $itemModel->insert([
            'board_id'    => $bId,
            'group_id'    => $data['group_id'],
            'parent_id'   => $data['parent_id'] ?? null,
            'title'       => $title,
            'description' => $data['description'] ?? null,
            'priority'    => $data['priority'] ?? 'none',
            'due_date'    => $data['due_date'] ?? null,
            'start_date'  => $data['start_date'] ?? null,
            'position'    => $pos,
            'created_by'  => $user['id'],
        ]);

        // Assignees
        if (!empty($data['assignees']) && is_array($data['assignees'])) {
            foreach (array_unique(array_map('intval', $data['assignees'])) as $uid) {
                $db->prepare("INSERT IGNORE INTO item_assignees (item_id, user_id) VALUES (?,?)")
                   ->execute([$itemId, $uid]);
            }
        }

        // Labels
        if (!empty($data['labels']) && is_array($data['labels'])) {
            foreach (array_unique(array_map('intval', $data['labels'])) as $lid) {
                $db->prepare("INSERT IGNORE INTO item_labels (item_id, label_id) VALUES (?,?)")
                   ->execute([$itemId, $lid]);
            }
        }

        $this->logActivity($bId, $itemId, $user['id'], 'item.created', ['title' => $title]);
        $this->triggerAutomation($bId, 'item.created', ['item_id' => $itemId, 'group_id' => $data['group_id']]);

        $this->json(['id' => $itemId, 'message' => 'Item criado'], 201);
    }

    public function update(string $id): void {
        $this->requireAuth();
        $this->validateCsrf();
        $user   = $this->currentUser();
        $itemId = (int)$id;
        $data   = $this->bodyJson();

        $itemModel = new ItemModel();
        $item      = $itemModel->find($itemId);
        if (!$item) $this->json(['error' => 'Item não encontrado'], 404);

        $allowed = ['title','description','priority','due_date','start_date','group_id','position'];
        $update  = array_intersect_key($data, array_flip($allowed));

        // Verifica mudança de grupo para log
        $prevGroup = $item['group_id'];

        if ($update) {
            // Se moveu para grupo "done", registra done_at
            if (!empty($update['group_id']) && (int)$update['group_id'] !== $prevGroup) {
                $db   = Database::getInstance();
                $stmt = $db->prepare("SELECT is_done FROM board_groups WHERE id=?");
                $stmt->execute([$update['group_id']]);
                $grp  = $stmt->fetch();
                if ($grp && $grp['is_done']) {
                    $update['done_at'] = date('Y-m-d H:i:s');
                } elseif (isset($update['group_id'])) {
                    $update['done_at'] = null;
                }
            }
            $itemModel->update($itemId, $update);
        }

        // Campos personalizados
        if (!empty($data['fields']) && is_array($data['fields'])) {
            foreach ($data['fields'] as $fieldId => $value) {
                $itemModel->setFieldValue($itemId, (int)$fieldId, (string)$value);
            }
        }

        // Assignees (substituição total)
        if (isset($data['assignees']) && is_array($data['assignees'])) {
            $db = Database::getInstance();
            $db->prepare("DELETE FROM item_assignees WHERE item_id=?")->execute([$itemId]);
            foreach (array_unique(array_map('intval', $data['assignees'])) as $uid) {
                $db->prepare("INSERT IGNORE INTO item_assignees (item_id, user_id) VALUES (?,?)")
                   ->execute([$itemId, $uid]);
            }
        }

        $this->logActivity($item['board_id'], $itemId, $user['id'], 'item.updated', $update);

        if (!empty($update['group_id']) && (int)$update['group_id'] !== $prevGroup) {
            $this->triggerAutomation($item['board_id'], 'status_changed', [
                'item_id'  => $itemId,
                'from'     => $prevGroup,
                'to'       => $update['group_id'],
            ]);
        }

        $this->json(['message' => 'Item atualizado']);
    }

    public function move(string $id): void {
        $this->requireAuth();
        $this->validateCsrf();
        $data    = $this->bodyJson();
        $itemId  = (int)$id;
        $groupId = (int)($data['group_id'] ?? 0);
        $pos     = (int)($data['position'] ?? 0);

        if (!$groupId) $this->json(['error' => 'group_id obrigatório'], 422);

        $itemModel = new ItemModel();
        $item      = $itemModel->find($itemId);
        if (!$item) $this->json(['error' => 'Item não encontrado'], 404);

        $itemModel->moveToGroup($itemId, $groupId, $pos);

        // Reordena itens do grupo destino
        $db   = Database::getInstance();
        $stmt = $db->prepare("SELECT id FROM items WHERE group_id=? AND id!=? AND archived_at IS NULL ORDER BY position");
        $stmt->execute([$groupId, $itemId]);
        $others = $stmt->fetchAll(PDO::FETCH_COLUMN);
        array_splice($others, $pos, 0, [$itemId]);
        foreach ($others as $i => $oId) {
            $db->prepare("UPDATE items SET position=? WHERE id=?")->execute([$i, $oId]);
        }

        $this->logActivity($item['board_id'], $itemId, $_SESSION['user_id'], 'item.moved', [
            'from_group' => $item['group_id'], 'to_group' => $groupId
        ]);

        $this->triggerAutomation($item['board_id'], 'status_changed', [
            'item_id' => $itemId, 'from' => $item['group_id'], 'to' => $groupId
        ]);

        $this->json(['message' => 'Item movido']);
    }

    public function archive(string $id): void {
        $this->requireAuth();
        $this->validateCsrf();
        $itemModel = new ItemModel();
        $itemModel->update((int)$id, ['archived_at' => date('Y-m-d H:i:s')]);
        $this->json(['message' => 'Item arquivado']);
    }

    public function destroy(string $id): void {
        $this->requireAuth();
        $this->validateCsrf();
        $itemModel = new ItemModel();
        $itemModel->delete((int)$id);
        $this->json(['message' => 'Item removido']);
    }

    // ── Comentários ─────────────────────────────────────────────────────────

    public function getComments(string $id): void {
        $this->requireAuth();
        $db   = Database::getInstance();
        $stmt = $db->prepare("
            SELECT c.*, u.name AS user_name, u.avatar AS user_avatar
            FROM comments c JOIN users u ON u.id = c.user_id
            WHERE c.item_id = ? AND c.parent_id IS NULL
            ORDER BY c.created_at ASC
        ");
        $stmt->execute([(int)$id]);
        $comments = $stmt->fetchAll();

        // Replies
        foreach ($comments as &$c) {
            $stmt = $db->prepare("
                SELECT c.*, u.name AS user_name, u.avatar AS user_avatar
                FROM comments c JOIN users u ON u.id = c.user_id WHERE c.parent_id = ?
                ORDER BY c.created_at ASC
            ");
            $stmt->execute([$c['id']]);
            $c['replies'] = $stmt->fetchAll();
        }

        $this->json($comments);
    }

    public function addComment(string $id): void {
        $this->requireAuth();
        $this->validateCsrf();
        $user   = $this->currentUser();
        $data   = $this->bodyJson();
        $itemId = (int)$id;
        $body   = trim($data['body'] ?? '');

        if (!$body) $this->json(['error' => 'Comentário vazio'], 422);

        $db = Database::getInstance();
        $db->prepare("INSERT INTO comments (item_id, user_id, parent_id, body) VALUES (?,?,?,?)")
           ->execute([$itemId, $user['id'], $data['parent_id'] ?? null, $body]);

        $commentId = (int)$db->lastInsertId();

        $item = (new ItemModel())->find($itemId);
        if ($item) {
            $this->logActivity($item['board_id'], $itemId, $user['id'], 'comment.added', ['body' => mb_substr($body, 0, 100)]);
        }

        $this->json(['id' => $commentId, 'message' => 'Comentário adicionado'], 201);
    }

    // ── Upload de Arquivo ────────────────────────────────────────────────────

    public function uploadFile(string $id): void {
        $this->requireAuth();
        $user   = $this->currentUser();
        $itemId = (int)$id;

        if (empty($_FILES['file'])) {
            $this->json(['error' => 'Nenhum arquivo enviado'], 422);
        }

        $file = $_FILES['file'];
        $ext  = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));

        if (!in_array($ext, ALLOWED_EXTENSIONS, true)) {
            $this->json(['error' => 'Tipo de arquivo não permitido'], 422);
        }
        if ($file['size'] > UPLOAD_MAX_SIZE) {
            $this->json(['error' => 'Arquivo muito grande (máx 10MB)'], 422);
        }

        $filename = uniqid('att_', true) . '.' . $ext;
        $dest     = UPLOAD_PATH . $filename;

        if (!move_uploaded_file($file['tmp_name'], $dest)) {
            $this->json(['error' => 'Falha ao salvar arquivo'], 500);
        }

        $db = Database::getInstance();
        $db->prepare("INSERT INTO attachments (item_id, uploaded_by, filename, original, mime_type, size) VALUES (?,?,?,?,?,?)")
           ->execute([$itemId, $user['id'], $filename, basename($file['name']), $file['type'], $file['size']]);

        $this->json(['id' => (int)$db->lastInsertId(), 'filename' => $filename, 'original' => basename($file['name'])], 201);
    }

    // ── Helpers ──────────────────────────────────────────────────────────────

    private function logActivity(int $boardId, ?int $itemId, int $userId, string $action, array $meta = []): void {
        $db = Database::getInstance();
        $db->prepare("INSERT INTO activity_logs (board_id,item_id,user_id,action,meta) VALUES (?,?,?,?,?)")
           ->execute([$boardId, $itemId, $userId, $action, json_encode($meta)]);
    }

    private function triggerAutomation(int $boardId, string $event, array $context): void {
        $service = new AutomationService();
        $service->process($boardId, $event, $context);
    }
}
