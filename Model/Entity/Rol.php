<?php
// Model/Entity/Rol.php

class Rol implements JsonSerializable
{
    public int $id_rol = 0;
    public string $nombre = "";
    public string $descripcion = "";

    /** @var array<string,mixed> */
    private array $raw = [];

    public static function fromRow(array $row): self
    {
        $e = new self();
        $e->raw = $row;
        $e->id_rol = (int)($row["id_rol"] ?? 0);
        $e->nombre = (string)($row["nombre"] ?? "");
        $e->descripcion = (string)($row["descripcion"] ?? "");
        return $e;
    }

    public function toArray(): array
    {
        // Mantener compatibilidad: devolvemos la fila original si existe.
        if (!empty($this->raw)) return $this->raw;
        return [
            "id_rol" => $this->id_rol,
            "nombre" => $this->nombre,
            "descripcion" => $this->descripcion,
        ];
    }

    public function jsonSerialize(): mixed
    {
        return $this->toArray();
    }
}