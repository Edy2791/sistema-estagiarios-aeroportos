<?php
// includes/session_handler.php – Sessões no banco de dados (com $pdo injetado)

class DbSessionHandler implements SessionHandlerInterface {
    private $pdo;

    public function __construct(PDO $pdo) {
        $this->pdo = $pdo;
    }

    public function open($savePath, $sessionName): bool {
        return true;
    }

    public function close(): bool {
        return true;
    }

    public function read($id): string {
        $stmt = $this->pdo->prepare("SELECT data FROM sessions WHERE id = ?");
        $stmt->execute([$id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ? $row['data'] : '';
    }

    public function write($id, $data): bool {
        $stmt = $this->pdo->prepare("REPLACE INTO sessions (id, data) VALUES (?, ?)");
        return $stmt->execute([$id, $data]);
    }

    public function destroy($id): bool {
        $stmt = $this->pdo->prepare("DELETE FROM sessions WHERE id = ?");
        return $stmt->execute([$id]);
    }

    public function gc($max): int|false {
        $old = time() - $max;
        $stmt = $this->pdo->prepare("DELETE FROM sessions WHERE access < FROM_UNIXTIME(?)");
        $stmt->execute([$old]);
        return $stmt->rowCount();
    }
}
?>