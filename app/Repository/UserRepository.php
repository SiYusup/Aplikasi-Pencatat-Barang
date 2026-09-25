<?php

namespace UCrazy\AplikasiPencatatBarangCrud\Repository;

use UCrazy\AplikasiPencatatBarangCrud\Domain\User;

class UserRepository
{
    private \PDO $connection;

    public function __construct(\PDO $connection)
    {
        $this->connection = $connection;
    }

    public function save(User $user): User
    {
        $stmt = $this->connection->prepare(
            "INSERT INTO users(name, email, password, role, avatar_url, is_active) VALUES (?, ?, ?, ?, ?, ?) RETURNING id"
        );
        $stmt->execute([$user->name, strtolower($user->email), $user->password, $user->role, $user->avatarUrl, $user->isActive ? 1 : 0]);
        $user->id = (int) $stmt->fetchColumn();
        return $user;
    }

    public function update(User $user): User
    {
        $stmt = $this->connection->prepare(
            "UPDATE users SET name = ?, email = ?, password = ?, role = ?, avatar_url = ?, is_active = ? WHERE id = ?"
        );
        $stmt->execute([$user->name, strtolower($user->email), $user->password, $user->role, $user->avatarUrl, $user->isActive ? 1 : 0, $user->id]);
        return $user;
    }

    public function findById(int $id): ?User
    {
        $stmt = $this->connection->prepare("SELECT * FROM users WHERE id = ?");
        $stmt->execute([$id]);
        $row = $stmt->fetch(\PDO::FETCH_ASSOC);
        return $row ? $this->map($row) : null;
    }

    public function findByEmail(string $email): ?User
    {
        $stmt = $this->connection->prepare("SELECT * FROM users WHERE LOWER(email) = LOWER(?)");
        $stmt->execute([$email]);
        $row = $stmt->fetch(\PDO::FETCH_ASSOC);
        return $row ? $this->map($row) : null;
    }

    public function countAll(): int
    {
        return (int) $this->connection->query("SELECT COUNT(*) FROM users")->fetchColumn();
    }

    public function deleteAll(): void
    {
        $this->connection->exec("DELETE FROM users");
    }

    private function map(array $row): User
    {
        $user = new User();
        $user->id = (int) $row['id'];
        $user->name = $row['name'];
        $user->email = $row['email'];
        $user->password = $row['password'];
        $user->role = $row['role'] ?? 'staff';
        $user->avatarUrl = $row['avatar_url'] ?? null;
        $user->isActive = (bool) $row['is_active'];
        $user->createdAt = $row['created_at'] ?? null;
        return $user;
    }
}
