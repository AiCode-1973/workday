<?php
class AuthController extends BaseController {

    public function showLogin(): void {
        if (!empty($_SESSION['user_id'])) {
            $this->redirect('/dashboard');
        }
        $this->view('auth/login');
    }

    public function login(): void {
        $this->validateCsrf();
        $email    = $this->input('email');
        $password = $_POST['password'] ?? '';

        $userModel = new UserModel();
        $user = $userModel->findByEmail($email);

        if (!$user || !$userModel->verifyPassword($password, $user['password'])) {
            $this->view('auth/login', ['error' => 'E-mail ou senha inválidos.']);
            return;
        }

        if (!$user['is_active']) {
            $this->view('auth/login', ['error' => 'Conta desativada. Contate o administrador.']);
            return;
        }

        $userModel->updateLastLogin($user['id']);

        session_regenerate_id(true);
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['user']    = [
            'id'     => $user['id'],
            'name'   => $user['name'],
            'email'  => $user['email'],
            'avatar' => $user['avatar'],
            'role'   => $user['role'],
        ];

        // Detecta workspace padrão
        $db = Database::getInstance();
        $stmt = $db->prepare("SELECT workspace_id FROM workspace_members WHERE user_id = ? LIMIT 1");
        $stmt->execute([$user['id']]);
        $row = $stmt->fetch();
        if ($row) {
            $_SESSION['workspace_id'] = $row['workspace_id'];
        }

        $this->redirect('/dashboard');
    }

    public function showRegister(): void {
        if (!empty($_SESSION['user_id'])) {
            $this->redirect('/dashboard');
        }
        $this->view('auth/register');
    }

    public function register(): void {
        $this->validateCsrf();
        $name     = $this->input('name');
        $email    = $this->input('email');
        $password = $_POST['password'] ?? '';
        $confirm  = $_POST['password_confirm'] ?? '';

        $errors = [];
        if (strlen($name) < 2)             $errors[] = 'Nome precisa ter no mínimo 2 caracteres.';
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'E-mail inválido.';
        if (strlen($password) < 8)         $errors[] = 'Senha precisa ter no mínimo 8 caracteres.';
        if ($password !== $confirm)        $errors[] = 'Senhas não conferem.';

        $userModel = new UserModel();
        if ($userModel->findByEmail($email)) {
            $errors[] = 'E-mail já cadastrado.';
        }

        if ($errors) {
            $this->view('auth/register', ['errors' => $errors, 'old' => compact('name', 'email')]);
            return;
        }

        $userId = $userModel->createUser(['name' => $name, 'email' => $email, 'password' => $password]);

        // Cria workspace pessoal
        $db = Database::getInstance();
        $slug = $this->makeSlug($name);
        $db->prepare("INSERT INTO workspaces (name, slug, owner_id) VALUES (?,?,?)")
           ->execute(["Workspace de {$name}", $slug, $userId]);
        $wsId = (int) $db->lastInsertId();
        $db->prepare("INSERT INTO workspace_members (workspace_id, user_id, role) VALUES (?,?,'admin')")
           ->execute([$wsId, $userId]);

        session_regenerate_id(true);
        $_SESSION['user_id']      = $userId;
        $_SESSION['workspace_id'] = $wsId;
        $_SESSION['user']         = [
            'id' => $userId, 'name' => $name, 'email' => $email, 'avatar' => null, 'role' => 'member'
        ];

        $this->redirect('/dashboard');
    }

    public function logout(): void {
        session_destroy();
        $this->redirect('/login');
    }

    public function showForgotPassword(): void {
        $this->view('auth/forgot');
    }

    public function forgotPassword(): void {
        $this->validateCsrf();
        $email     = $this->input('email');
        $userModel = new UserModel();
        $user      = $userModel->findByEmail($email);

        // Não revela se o e-mail existe (segurança)
        $msg = 'Se o e-mail estiver cadastrado, você receberá as instruções em breve.';

        if ($user) {
            $token = $userModel->generateToken($user['id']);
            try {
                MailService::sendPasswordReset($user['email'], $user['name'], $token);
            } catch (Throwable $e) {
                error_log('MailService error: ' . $e->getMessage());
            }
        }

        $this->view('auth/forgot', ['success' => $msg]);
    }

    private function makeSlug(string $text): string {
        $slug = strtolower(preg_replace('/[^a-zA-Z0-9]+/', '-', $text));
        $slug = trim($slug, '-');
        // Garante unicidade
        $db   = Database::getInstance();
        $base = $slug;
        $i    = 1;
        while (true) {
            $stmt = $db->prepare("SELECT id FROM workspaces WHERE slug = ?");
            $stmt->execute([$slug]);
            if (!$stmt->fetch()) break;
            $slug = $base . '-' . $i++;
        }
        return $slug;
    }
}
