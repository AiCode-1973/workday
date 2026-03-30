<?php

class ProfileController extends BaseController
{
    public function show(): void
    {
        $user   = $this->requireAuth();
        $db     = Database::getInstance();
        $userId = $_SESSION['user_id'];

        $profile = $db->prepare("SELECT id,name,email,avatar,created_at FROM users WHERE id = ?")->execute([$userId]) ? null : null;
        $stmt    = $db->prepare("SELECT id,name,email,avatar,created_at FROM users WHERE id = ?");
        $stmt->execute([$userId]);
        $profile = $stmt->fetch(PDO::FETCH_ASSOC);

        // API tokens do usuário
        $tStmt = $db->prepare("SELECT id,name,last_used_at,created_at FROM api_tokens WHERE user_id = ? ORDER BY created_at DESC");
        $tStmt->execute([$userId]);
        $apiTokens = $tStmt->fetchAll();

        $this->view('layouts/app', [
            'content'   => 'profile/index',
            'profile'   => $profile,
            'apiTokens' => $apiTokens,
            'pageTitle' => 'Perfil',
        ]);
    }

    public function update(): void
    {
        $user   = $this->requireAuth();
        $this->validateCsrf();
        $userId = $_SESSION['user_id'];
        $input  = $this->input();
        $db     = Database::getInstance();

        $name  = trim($input['name'] ?? '');
        $email = trim($input['email'] ?? '');

        if (!$name || !$email || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->json(['error' => 'Nome e e-mail válidos são obrigatórios.'], 422);
            return;
        }

        // Verifica duplicado de e-mail
        $check = $db->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
        $check->execute([$email, $userId]);
        if ($check->fetch()) {
            $this->json(['error' => 'E-mail já cadastrado por outro usuário.'], 409);
            return;
        }

        // Avatar upload
        $avatarName = null;
        if (!empty($_FILES['avatar']['tmp_name'])) {
            $ext = strtolower(pathinfo($_FILES['avatar']['name'], PATHINFO_EXTENSION));
            if (!in_array($ext, ['jpg','jpeg','png','gif','webp'])) {
                $this->json(['error' => 'Formato de avatar inválido.'], 422);
                return;
            }
            if ($_FILES['avatar']['size'] > 2097152) { // 2MB
                $this->json(['error' => 'Avatar deve ter no máximo 2MB.'], 422);
                return;
            }
            $avatarName = 'avatar_' . $userId . '_' . time() . '.' . $ext;
            move_uploaded_file($_FILES['avatar']['tmp_name'], UPLOAD_PATH . $avatarName);
        }

        $params = [$name, $email];
        $sql    = "UPDATE users SET name = ?, email = ?";
        if ($avatarName) { $sql .= ", avatar = ?"; $params[] = $avatarName; }
        $sql    .= " WHERE id = ?";
        $params[] = $userId;

        $db->prepare($sql)->execute($params);
        $_SESSION['user_name']  = $name;
        $_SESSION['user_email'] = $email;

        $this->json(['ok' => true, 'avatar' => $avatarName]);
    }

    public function changePassword(): void
    {
        $user   = $this->requireAuth();
        $this->validateCsrf();
        $userId = $_SESSION['user_id'];
        $input  = $this->bodyJson();
        $db     = Database::getInstance();

        $current = $input['current_password'] ?? '';
        $new      = $input['new_password'] ?? '';
        $confirm  = $input['confirm_password'] ?? '';

        if (strlen($new) < 8) { $this->json(['error' => 'Senha deve ter no mínimo 8 caracteres.'], 422); return; }
        if ($new !== $confirm) { $this->json(['error' => 'Senhas não conferem.'], 422); return; }

        $stmt = $db->prepare("SELECT password_hash FROM users WHERE id = ?");
        $stmt->execute([$userId]);
        $row  = $stmt->fetch();

        if (!$row || !password_verify($current, $row['password_hash'])) {
            $this->json(['error' => 'Senha atual incorreta.'], 403);
            return;
        }

        $hash = password_hash($new, PASSWORD_BCRYPT, ['cost' => 12]);
        $db->prepare("UPDATE users SET password_hash = ? WHERE id = ?")->execute([$hash, $userId]);
        $this->json(['ok' => true]);
    }

    public function createToken(): void
    {
        $user   = $this->requireAuth();
        $this->validateCsrf();
        $userId = $_SESSION['user_id'];
        $input  = $this->bodyJson();
        $db     = Database::getInstance();

        $name  = trim($input['name'] ?? 'Token ' . date('d/m/Y'));
        $token = bin2hex(random_bytes(32));

        $db->prepare("INSERT INTO api_tokens (user_id, name, token) VALUES (?,?,?)")
           ->execute([$userId, $name, hash('sha256', $token)]);

        $this->json(['token' => $token, 'name' => $name]);
    }

    public function revokeToken(): void
    {
        $user   = $this->requireAuth();
        $this->validateCsrf();
        $userId = $_SESSION['user_id'];
        $id     = (int) ($_GET['id'] ?? 0);
        $db     = Database::getInstance();

        $db->prepare("DELETE FROM api_tokens WHERE id = ? AND user_id = ?")->execute([$id, $userId]);
        $this->json(['ok' => true]);
    }
}
