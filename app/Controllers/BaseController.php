<?php
/**
 * Controller base
 */
abstract class BaseController {

    protected function view(string $template, array $data = []): void {
        extract($data, EXTR_SKIP);
        $file = ROOT . '/views/' . $template . '.php';
        if (!file_exists($file)) {
            throw new RuntimeException("View não encontrada: {$template}");
        }
        require $file;
    }

    protected function json(mixed $data, int $status = 200): void {
        http_response_code($status);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }

    protected function redirect(string $url): void {
        header('Location: ' . APP_URL . $url);
        exit;
    }

    protected function requireAuth(): void {
        if (empty($_SESSION['user_id'])) {
            if ($this->isAjax()) {
                $this->json(['error' => 'Não autenticado'], 401);
            }
            $this->redirect('/login');
        }
    }

    protected function currentUser(): ?array {
        return $_SESSION['user'] ?? null;
    }

    protected function isAjax(): bool {
        return !empty($_SERVER['HTTP_X_REQUESTED_WITH'])
            && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
    }

    protected function validateCsrf(): void {
        $token = $_POST['_csrf'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
        if (!hash_equals($_SESSION['csrf_token'] ?? '', $token)) {
            $this->json(['error' => 'Token CSRF inválido'], 403);
        }
    }

    protected function input(string $key, mixed $default = null): mixed {
        $value = $_POST[$key] ?? $_GET[$key] ?? $default;
        return is_string($value) ? htmlspecialchars(trim($value), ENT_QUOTES, 'UTF-8') : $value;
    }

    protected function bodyJson(): array {
        $raw = file_get_contents('php://input');
        return json_decode($raw, true) ?? [];
    }

    protected function paginate(int $total, int $perPage = 25, int $page = 1): array {
        $page      = max(1, $page);
        $lastPage  = (int) ceil($total / $perPage);
        $offset    = ($page - 1) * $perPage;
        return compact('total', 'perPage', 'page', 'lastPage', 'offset');
    }
}
