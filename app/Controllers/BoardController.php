<?php
class BoardController extends BaseController {

    public function index(): void {
        $this->requireAuth();
        $user        = $this->currentUser();
        $workspaceId = $_SESSION['workspace_id'] ?? 0;

        $boardModel = new BoardModel();
        $boards     = $boardModel->getByWorkspace($workspaceId, $user['id']);

        // Busca portfolios para agrupar
        $db = Database::getInstance();
        $stmt = $db->prepare("SELECT * FROM portfolios WHERE workspace_id = ? ORDER BY name");
        $stmt->execute([$workspaceId]);
        $portfolios = $stmt->fetchAll();

        $this->view('layouts/app', [
            'pageTitle'  => 'Quadros',
            'content'    => 'boards/index',
            'boards'     => $boards,
            'portfolios' => $portfolios,
        ]);
    }

    public function show(string $id): void {
        $this->requireAuth();
        $user      = $this->currentUser();
        $boardId   = (int)$id;

        $boardModel = new BoardModel();
        if (!$boardModel->canAccess($boardId, $user['id'])) {
            http_response_code(403);
            $this->view('errors/403');
            return;
        }

        $board = $boardModel->getWithGroups($boardId);
        if (!$board) {
            http_response_code(404);
            $this->view('errors/404');
            return;
        }

        $view = $_GET['view'] ?? $board['default_view'];

        $itemModel = new ItemModel();
        $items     = $itemModel->getByBoard($boardId, [
            'group_id'  => $_GET['group_id']  ?? null,
            'assignee'  => $_GET['assignee']  ?? null,
            'priority'  => $_GET['priority']  ?? null,
        ]);

        // Membros do workspace para atribuição
        $db = Database::getInstance();
        $stmt = $db->prepare("
            SELECT u.id, u.name, u.avatar FROM users u
            JOIN workspace_members wm ON wm.user_id = u.id
            WHERE wm.workspace_id = ? ORDER BY u.name
        ");
        $stmt->execute([$_SESSION['workspace_id'] ?? 0]);
        $members = $stmt->fetchAll();

        // Dados da ferramenta (mantidos para compatibilidade com view, mas não mais utilizados)
        $toolData = null;

        $this->view('layouts/app', [
            'pageTitle' => $board['name'],
            'content'   => 'boards/show',
            'board'     => $board,
            'items'     => $items,
            'members'   => $members,
            'view'      => $view,
            'toolData'  => $toolData,
        ]);
    }

    public function store(): void {
        $this->requireAuth();
        $this->validateCsrf();
        $user        = $this->currentUser();
        $workspaceId = $_SESSION['workspace_id'] ?? 0;
        $data        = $this->bodyJson();

        $name = trim($data['name'] ?? '');
        if (!$name) {
            $this->json(['error' => 'Nome obrigatório'], 422);
        }

        $boardModel = new BoardModel();
        $boardId    = $boardModel->insert([
            'workspace_id' => $workspaceId,
            'portfolio_id' => $data['portfolio_id'] ?? null,
            'name'         => $name,
            'description'  => $data['description'] ?? null,
            'color'        => $data['color'] ?? '#6366f1',
            'default_view' => $data['default_view'] ?? 'kanban',
            'created_by'   => $user['id'],
        ]);

        // Cria grupos padrão
        $db = Database::getInstance();
        $defaults = [
            ['Backlog',      '#94a3b8', 0, 0],
            ['Em Progresso', '#3b82f6', 1, 0],
            ['Concluído',    '#22c55e', 2, 1],
        ];
        foreach ($defaults as [$name, $color, $pos, $done]) {
            $db->prepare("INSERT INTO board_groups (board_id,name,color,position,is_done) VALUES (?,?,?,?,?)")
               ->execute([$boardId, $name, $color, $pos, $done]);
        }

        $this->logActivity($boardId, null, $user['id'], 'board.created', ['name' => $name]);
        $this->json(['id' => $boardId, 'message' => 'Quadro criado com sucesso'], 201);
    }

    public function update(string $id): void {
        $this->requireAuth();
        $this->validateCsrf();
        $user      = $this->currentUser();
        $boardId   = (int)$id;
        $data      = $this->bodyJson();

        $boardModel = new BoardModel();
        if (!$boardModel->canAccess($boardId, $user['id'])) {
            $this->json(['error' => 'Acesso negado'], 403);
        }

        $allowed = ['name','description','color','default_view','portfolio_id'];
        $update  = array_intersect_key($data, array_flip($allowed));

        if (!empty($update)) {
            $boardModel->update($boardId, $update);
        }

        $this->json(['message' => 'Quadro atualizado']);
    }

    public function archive(string $id): void {
        $this->requireAuth();
        $this->validateCsrf();
        $boardId    = (int)$id;
        $boardModel = new BoardModel();
        $boardModel->update($boardId, ['archived_at' => date('Y-m-d H:i:s')]);
        $this->json(['message' => 'Quadro arquivado']);
    }

    // ── Grupos ──────────────────────────────────────────────────────────────

    public function storeGroup(string $boardId): void {
        $this->requireAuth();
        $this->validateCsrf();
        $data    = $this->bodyJson();
        $bId     = (int)$boardId;
        $name    = trim($data['name'] ?? '');
        if (!$name) $this->json(['error' => 'Nome obrigatório'], 422);

        $db = Database::getInstance();
        $stmt = $db->prepare("SELECT MAX(position)+1 as next FROM board_groups WHERE board_id = ?");
        $stmt->execute([$bId]);
        $next = (int)($stmt->fetchColumn() ?? 0);

        $db->prepare("INSERT INTO board_groups (board_id,name,color,position) VALUES (?,?,?,?)")
           ->execute([$bId, $name, $data['color'] ?? '#94a3b8', $next]);

        $this->json(['id' => (int)$db->lastInsertId(), 'message' => 'Grupo criado'], 201);
    }

    public function updateGroup(string $boardId, string $groupId): void {
        $this->requireAuth();
        $this->validateCsrf();
        $data  = $this->bodyJson();
        $db    = Database::getInstance();
        $gId   = (int)$groupId;
        $bId   = (int)$boardId;

        $allowed = ['name','color','is_done'];
        $sets    = [];
        $binds   = [];
        foreach ($allowed as $col) {
            if (isset($data[$col])) { $sets[] = "`{$col}`=?"; $binds[] = $data[$col]; }
        }
        if ($sets) {
            $binds[] = $gId;
            $db->prepare("UPDATE board_groups SET " . implode(',', $sets) . " WHERE id=? AND board_id={$bId}")
               ->execute($binds);
        }
        $this->json(['message' => 'Grupo atualizado']);
    }

    public function deleteGroup(string $boardId, string $groupId): void {
        $this->requireAuth();
        $this->validateCsrf();
        $db  = Database::getInstance();
        $db->prepare("DELETE FROM board_groups WHERE id=? AND board_id=?")->execute([(int)$groupId, (int)$boardId]);
        $this->json(['message' => 'Grupo removido']);
    }

    // ── Ferramentas ─────────────────────────────────────────────────────────

    public function toolView(string $boardId): void {
        $this->requireAuth();
        $user    = $this->currentUser();
        $bId     = (int)$boardId;

        $boardModel = new BoardModel();
        if (!$boardModel->canAccess($bId, $user['id'])) {
            http_response_code(403);
            $this->view('errors/403');
            return;
        }

        $board = $boardModel->find($bId);
        if (!$board || ($board['tool'] ?? 'none') === 'none') {
            http_response_code(404);
            $this->view('errors/404');
            return;
        }

        $db   = Database::getInstance();
        $stmt = $db->prepare("SELECT content FROM board_tool_data WHERE board_id = ? AND tool_type = ?");
        $stmt->execute([$bId, $board['tool']]);
        $raw      = $stmt->fetchColumn();
        $toolData = $raw ? json_decode($raw, true) : null;

        $this->view('layouts/app', [
            'pageTitle' => $board['name'] . ' — SIPOC',
            'content'   => 'boards/tool_sipoc',
            'board'     => $board,
            'toolData'  => $toolData,
        ]);
    }

    public function saveTool(string $boardId): void {
        $this->requireAuth();
        $this->validateCsrf();
        $user = $this->currentUser();
        $bId  = (int)$boardId;

        $boardModel = new BoardModel();
        if (!$boardModel->canAccess($bId, $user['id'])) {
            $this->json(['error' => 'Acesso negado'], 403);
        }

        $board = $boardModel->find($bId);
        if (!$board || ($board['tool'] ?? 'none') === 'none') {
            $this->json(['error' => 'Nenhuma ferramenta configurada para este quadro'], 422);
        }

        $data    = $this->bodyJson();
        $content = json_encode($data, JSON_UNESCAPED_UNICODE);

        $db = Database::getInstance();
        $db->prepare("
            INSERT INTO board_tool_data (board_id, tool_type, content, updated_at)
            VALUES (?, ?, ?, NOW())
            ON DUPLICATE KEY UPDATE content = VALUES(content), updated_at = NOW()
        ")->execute([$bId, $board['tool'], $content]);

        $this->json(['message' => 'Salvo com sucesso']);
    }

    private function logActivity(int $boardId, ?int $itemId, int $userId, string $action, array $meta = []): void {
        $db = Database::getInstance();
        $db->prepare("INSERT INTO activity_logs (board_id,item_id,user_id,action,meta) VALUES (?,?,?,?,?)")
           ->execute([$boardId, $itemId, $userId, $action, json_encode($meta)]);
    }
}
