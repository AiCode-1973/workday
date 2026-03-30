<?php
/**
 * Model base com query helper
 */
abstract class BaseModel {
    protected PDO $db;
    protected string $table;

    public function __construct() {
        $this->db = Database::getInstance();
    }

    public function find(int $id): ?array {
        $stmt = $this->db->prepare("SELECT * FROM `{$this->table}` WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch() ?: null;
    }

    public function findBy(string $column, mixed $value): ?array {
        $stmt = $this->db->prepare("SELECT * FROM `{$this->table}` WHERE `{$column}` = ? LIMIT 1");
        $stmt->execute([$value]);
        return $stmt->fetch() ?: null;
    }

    public function all(string $where = '', array $binds = [], string $order = 'id ASC'): array {
        $sql = "SELECT * FROM `{$this->table}`";
        if ($where) $sql .= " WHERE {$where}";
        $sql .= " ORDER BY {$order}";
        $stmt = $this->db->prepare($sql);
        $stmt->execute($binds);
        return $stmt->fetchAll();
    }

    public function insert(array $data): int {
        $cols   = implode(',', array_map(fn($k) => "`{$k}`", array_keys($data)));
        $marks  = implode(',', array_fill(0, count($data), '?'));
        $this->db->prepare("INSERT INTO `{$this->table}` ({$cols}) VALUES ({$marks})")
                 ->execute(array_values($data));
        return (int) $this->db->lastInsertId();
    }

    public function update(int $id, array $data): bool {
        $set = implode(',', array_map(fn($k) => "`{$k}`=?", array_keys($data)));
        $stmt = $this->db->prepare("UPDATE `{$this->table}` SET {$set} WHERE id=?");
        return $stmt->execute([...array_values($data), $id]);
    }

    public function delete(int $id): bool {
        return $this->db->prepare("DELETE FROM `{$this->table}` WHERE id=?")
                        ->execute([$id]);
    }

    public function count(string $where = '', array $binds = []): int {
        $sql = "SELECT COUNT(*) FROM `{$this->table}`";
        if ($where) $sql .= " WHERE {$where}";
        $stmt = $this->db->prepare($sql);
        $stmt->execute($binds);
        return (int) $stmt->fetchColumn();
    }
}
