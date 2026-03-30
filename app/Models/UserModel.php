<?php
class UserModel extends BaseModel {
    protected string $table = 'users';

    public function findByEmail(string $email): ?array {
        return $this->findBy('email', $email);
    }

    public function createUser(array $data): int {
        $data['password'] = password_hash($data['password'], PASSWORD_BCRYPT, ['cost' => 12]);
        return $this->insert($data);
    }

    public function verifyPassword(string $plain, string $hash): bool {
        return password_verify($plain, $hash);
    }

    public function getWorkspaceMembers(int $workspaceId): array {
        $stmt = $this->db->prepare("
            SELECT u.id, u.name, u.email, u.avatar, wm.role
            FROM users u
            JOIN workspace_members wm ON wm.user_id = u.id
            WHERE wm.workspace_id = ?
            ORDER BY u.name
        ");
        $stmt->execute([$workspaceId]);
        return $stmt->fetchAll();
    }

    public function updateLastLogin(int $userId): void {
        $this->update($userId, ['last_login' => date('Y-m-d H:i:s')]);
    }

    public function generateToken(int $userId): string {
        $token = bin2hex(random_bytes(32));
        $this->update($userId, ['token' => $token]);
        return $token;
    }
}
