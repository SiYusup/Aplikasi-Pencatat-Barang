<?php

namespace UCrazy\AplikasiPencatatBarangCrud\Repository;

use UCrazy\AplikasiPencatatBarangCrud\Domain\Session;

class SessionRepository
{
    private \PDO $connection;

    public function __construct(\PDO $connection)
    {
        $this->connection = $connection;
    }

    public function save(Session $session): Session
    {
        $stmt = $this->connection->prepare("INSERT INTO sessions(id, user_id) VALUES (?, ?)");
        $stmt->execute([$session->id, $session->userId]);
        return $session;
    }

    public function findById(string $id): ?Session
    {
        $stmt = $this->connection->prepare("SELECT id, user_id FROM sessions WHERE id = ?");
        $stmt->execute([$id]);
        $row = $stmt->fetch(\PDO::FETCH_ASSOC);
        if (!$row) {
            return null;
        }
        $session = new Session();
        $session->id = $row['id'];
        $session->userId = (int) $row['user_id'];
        return $session;
    }

    public function deleteById(string $id): void
    {
        $stmt = $this->connection->prepare("DELETE FROM sessions WHERE id = ?");
        $stmt->execute([$id]);
    }

    public function deleteAll(): void
    {
        $this->connection->exec("DELETE FROM sessions");
    }
}
