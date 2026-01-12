<?php
// Model/Entity/EmpresaUsuario.php

class EmpresaUsuario implements JsonSerializable
{
    public int $id = 0;
    public int $empresa_id = 0;
    public int $usuario_id = 0;
    public string $rol = "";
    public string $created_at = "";

    /** @var array<string,mixed> */
    private array $raw = [];

    public static function fromRow(array $row): self
    {
        $e = new self();
        $e->raw = $row;
        $e->id = (int)($row["id"] ?? 0);
        $e->empresa_id = (int)($row["empresa_id"] ?? 0);
        $e->usuario_id = (int)($row["usuario_id"] ?? 0);
        $e->rol = (string)($row["rol"] ?? "");
        $e->created_at = (string)($row["created_at"] ?? "");
        return $e;
    }

    public function toArray(): array
    {
        // Mantener compatibilidad: devolvemos la fila original si existe.
        if (!empty($this->raw)) return $this->raw;
        return [
            "id" => $this->id,
            "empresa_id" => $this->empresa_id,
            "usuario_id" => $this->usuario_id,
            "rol" => $this->rol,
            "created_at" => $this->created_at,
        ];
    }

    public function jsonSerialize(): mixed
    {
        return $this->toArray();
    }
}