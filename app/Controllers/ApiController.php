<?php
/**
 * Controller de API pública (RESTful com token)
 */
class ApiController extends BaseController {

    public function __construct() {
        $this->authenticateApi();
    }

    private function authenticateApi(): void {
        $auth  = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
        $token = '';
        if (preg_match('/Bearer\s+(.+)/i', $auth, $m)) {
            $token = $m[1];
        }
        if (!$token) {
            $this->json(['error' => 'Token de API necessário'], 401);
        }

        $db        = Database::getInstance();
        $tokenHash = hash('sha256', $token);
        $stmt      = $db->prepare("SELECT t.*, u.id AS uid, u.name, u.email, u.role FROM api_tokens t JOIN users u ON u.id = t.user_id WHERE t.token=? AND (t.expires_at IS NULL OR t.expires_at > NOW())");
        $stmt->execute([$tokenHash]);
        $row  = $stmt->fetch();
        if (!$row) {
            $this->json(['error' => 'Token inválido ou expirado'], 401);
        }

        // Atualiza last_used
        $db->prepare("UPDATE api_tokens SET last_used_at=NOW() WHERE token=?")->execute([$tokenHash]);

        $_SESSION['user_id'] = $row['uid'];
        $_SESSION['user']    = ['id' => $row['uid'], 'name' => $row['name'], 'email' => $row['email'], 'role' => $row['role']];
    }

    // GET /api/boards
    public function boards(): void {
        $user        = $this->currentUser();
        $workspaceId = (int)($_GET['workspace_id'] ?? 0);
        if (!$workspaceId) $this->json(['error' => 'workspace_id obrigatório'], 422);

        $boardModel = new BoardModel();
        $this->json($boardModel->getByWorkspace($workspaceId, $user['id']));
    }

    // GET /api/boards/{id}/items
    public function boardItems(string $id): void {
        $itemModel = new ItemModel();
        $this->json($itemModel->getByBoard((int)$id, $_GET));
    }

    // GET /api/items/{id}
    public function item(string $id): void {
        $itemModel = new ItemModel();
        $item = $itemModel->getDetail((int)$id);
        if (!$item) $this->json(['error' => 'Não encontrado'], 404);
        $this->json($item);
    }
}
