<?php
// Model/Entity/Categoria.php

class Categoria implements JsonSerializable
{
    public int $id_categoria = 0;
    public string $nombre = "";
    public string $slug = "";
    public string $descripcion = "";
    public int $id_padre = 0;
    public int $orden = 0;
    public int $activo = 0;

    /** @var array<string,mixed> */
    private array $raw = [];

    public static function fromRow(array $row): self
    {
        $e = new self();
        $e->raw = $row;
        $e->id_categoria = (int)($row["id_categoria"] ?? 0);
        $e->nombre = (string)($row["nombre"] ?? "");
        $e->slug = (string)($row["slug"] ?? "");
        $e->descripcion = (string)($row["descripcion"] ?? "");
        $e->id_padre = (int)($row["id_padre"] ?? 0);
        $e->orden = (int)($row["orden"] ?? 0);
        $e->activo = (int)($row["activo"] ?? 0);
        return $e;
    }

    public function toArray(): array
    {
        // Mantener compatibilidad: devolvemos la fila original si existe.
        if (!empty($this->raw)) return $this->raw;
        return [
            "id_categoria" => $this->id_categoria,
            "nombre" => $this->nombre,
            "slug" => $this->slug,
            "descripcion" => $this->descripcion,
            "id_padre" => $this->id_padre,
            "orden" => $this->orden,
            "activo" => $this->activo,
        ];
    }

    public function jsonSerialize(): mixed
    {
        return $this->toArray();
    }
}