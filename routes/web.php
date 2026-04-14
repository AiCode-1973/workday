<?php
/** Registra todas as rotas */

$router = new Router();

// ── Auth ─────────────────────────────────────────────────────────────────
$router->get('/login',           [AuthController::class, 'showLogin']);
$router->post('/login',          [AuthController::class, 'login']);
$router->get('/register',        [AuthController::class, 'showRegister']);
$router->post('/register',       [AuthController::class, 'register']);
$router->get('/logout',          [AuthController::class, 'logout']);
$router->get('/forgot-password', [AuthController::class, 'showForgotPassword']);
$router->post('/forgot-password',[AuthController::class, 'forgotPassword']);

// ── Dashboard ─────────────────────────────────────────────────────────────
$router->get('/',          [DashboardController::class, 'index']);
$router->get('/dashboard', [DashboardController::class, 'index']);

// ── Boards ────────────────────────────────────────────────────────────────
$router->get('/boards',           [BoardController::class, 'index']);
$router->post('/boards',          [BoardController::class, 'store']);
$router->get('/boards/{id}',      [BoardController::class, 'show']);
$router->put('/boards/{id}',      [BoardController::class, 'update']);
$router->post('/boards/{id}/archive',               [BoardController::class, 'archive']);
$router->post('/boards/{boardId}/groups',            [BoardController::class, 'storeGroup']);
$router->put('/boards/{boardId}/groups/{groupId}',  [BoardController::class, 'updateGroup']);
$router->delete('/boards/{boardId}/groups/{groupId}',[BoardController::class, 'deleteGroup']);

// ── Items ─────────────────────────────────────────────────────────────────
$router->get('/boards/{boardId}/items',   [ItemController::class, 'index']);
$router->post('/boards/{boardId}/items',  [ItemController::class, 'store']);
$router->get('/items/{id}',               [ItemController::class, 'show']);
$router->put('/items/{id}',               [ItemController::class, 'update']);
$router->post('/items/{id}/move',         [ItemController::class, 'move']);
$router->post('/items/{id}/archive',      [ItemController::class, 'archive']);
$router->delete('/items/{id}',            [ItemController::class, 'destroy']);
$router->get('/items/{id}/comments',      [ItemController::class, 'getComments']);
$router->post('/items/{id}/comments',     [ItemController::class, 'addComment']);
$router->post('/items/{id}/upload',       [ItemController::class, 'uploadFile']);
$router->post('/items/{id}/tool-data',    [ItemController::class, 'saveToolData']);

// ── Notificações ─────────────────────────────────────────────────────────
$router->get('/notifications',              [NotificationController::class, 'index']);
$router->post('/notifications/{id}/read',   [NotificationController::class, 'markRead']);
$router->post('/notifications/read-all',    [NotificationController::class, 'markAllRead']);

// ── Automações ─────────────────────────────────────────────────────────────
$router->get('/boards/{boardId}/automations',         [AutomationController::class, 'index']);
$router->post('/boards/{boardId}/automations',        [AutomationController::class, 'store']);
$router->put('/boards/{boardId}/automations/{id}',    [AutomationController::class, 'update']);
$router->delete('/boards/{boardId}/automations/{id}', [AutomationController::class, 'destroy']);

// ── API Pública ───────────────────────────────────────────────────────────
$router->get('/api/boards',              [ApiController::class, 'boards']);
$router->get('/api/boards/{id}/items',   [ApiController::class, 'boardItems']);
$router->get('/api/items/{id}',          [ApiController::class, 'item']);

// ── Ferramentas por Quadro ───────────────────────────────────────────────
$router->get('/boards/{boardId}/tool',  [BoardController::class, 'toolView']);
$router->post('/boards/{boardId}/tool', [BoardController::class, 'saveTool']);

// ── Relatórios ────────────────────────────────────────────────────────────
$router->get('/reports',              [ReportController::class, 'index']);
$router->get('/reports/export-csv',   [ReportController::class, 'exportCsv']);

// ── Perfil ────────────────────────────────────────────────────────────────
$router->get('/profile',                  [ProfileController::class, 'show']);
$router->post('/profile',                 [ProfileController::class, 'update']);
$router->post('/profile/password',        [ProfileController::class, 'changePassword']);
$router->post('/profile/tokens',          [ProfileController::class, 'createToken']);
$router->delete('/profile/tokens',        [ProfileController::class, 'revokeToken']);

return $router;
