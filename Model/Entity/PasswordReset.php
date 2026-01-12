<?php
// Model/Entity/PasswordReset.php

class PasswordReset implements JsonSerializable
{
    public int $id = 0;
    public string $email = "";
    public string $token = "";
    public string $expires_at = "";
    public string $created_at = "";

    /** @var array<string,mixed> */
    private array $raw = [];

    public static function fromRow(array $row): self
    {
        $e = new self();
        $e->raw = $row;
        $e->id = (int)($row["id"] ?? 0);
        $e->email = (string)($row["email"] ?? "");
        $e->token = (string)($row["token"] ?? "");
        $e->expires_at = (string)($row["expires_at"] ?? "");
        $e->created_at = (string)($row["created_at"] ?? "");
        return $e;
    }

    public function toArray(): array
    {
        // Mantener compatibilidad: devolvemos la fila original si existe.
        if (!empty($this->raw)) return $this->raw;
        return [
            "id" => $this->id,
            "email" => $this->email,
            "token" => $this->token,
            "expires_at" => $this->expires_at,
            "created_at" => $this->created_at,
        ];
    }

    public function jsonSerialize(): mixed
    {
        return $this->toArray();
    }
}