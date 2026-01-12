<?php
// Model/Entity/Inventario.php

class Inventario implements JsonSerializable
{
    public int $id_producto = 0;
    public int $stock_actual = 0;
    public string $ubicacion_almacen = "";
    public string $ultima_actualizacion = "";

    /** @var array<string,mixed> */
    private array $raw = [];

    public static function fromRow(array $row): self
    {
        $e = new self();
        $e->raw = $row;
        $e->id_producto = (int)($row["id_producto"] ?? 0);
        $e->stock_actual = (int)($row["stock_actual"] ?? 0);
        $e->ubicacion_almacen = (string)($row["ubicacion_almacen"] ?? "");
        $e->ultima_actualizacion = (string)($row["ultima_actualizacion"] ?? "");
        return $e;
    }

    public function toArray(): array
    {
        // Mantener compatibilidad: devolvemos la fila original si existe.
        if (!empty($this->raw)) return $this->raw;
        return [
            "id_producto" => $this->id_producto,
            "stock_actual" => $this->stock_actual,
            "ubicacion_almacen" => $this->ubicacion_almacen,
            "ultima_actualizacion" => $this->ultima_actualizacion,
        ];
    }

    public function jsonSerialize(): mixed
    {
        return $this->toArray();
    }
}