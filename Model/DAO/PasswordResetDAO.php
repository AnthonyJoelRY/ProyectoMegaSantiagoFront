<?php
class PasswordResetDAO {
    private PDO $pdo;

    public function __construct(PDO $pdo) {
        $this->pdo = $pdo;
    }

    public function create(string $email, string $token, string $expiresAt): bool {
        $stmt = $this->pdo->prepare("
            INSERT INTO password_resets (email, token, expires_at)
            VALUES (?, ?, ?)
        ");
        return $stmt->execute([$email, $token, $expiresAt]);
    }

    public function deleteByEmail(string $email): void {
        $stmt = $this->pdo->prepare("DELETE FROM password_resets WHERE email = ?");
        $stmt->execute([$email]);
    }

    public function cleanupExpired(): void {
        $stmt = $this->pdo->prepare("DELETE FROM password_resets WHERE expires_at < NOW()");
        $stmt->execute();
    }

    public function findValidByToken(string $token): ?array {
        $stmt = $this->pdo->prepare("
            SELECT id, email, token, expires_at
            FROM password_resets
            WHERE token = ?
              AND expires_at >= NOW()
            ORDER BY id DESC
            LIMIT 1
        ");
        $stmt->execute([$token]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    public function deleteById(int $id): void {
        $stmt = $this->pdo->prepare("DELETE FROM password_resets WHERE id = ? LIMIT 1");
        $stmt->execute([$id]);
    }
}
