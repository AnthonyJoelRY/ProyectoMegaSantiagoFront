<?php
// Model/Entity/PromocionProducto.php

class PromocionProducto implements JsonSerializable
{
    public int $id_promocion = 0;
    public int $id_producto = 0;

    /** @var array<string,mixed> */
    private array $raw = [];

    public static function fromRow(array $row): self
    {
        $e = new self();
        $e->raw = $row;
        $e->id_promocion = (int)($row["id_promocion"] ?? 0);
        $e->id_producto = (int)($row["id_producto"] ?? 0);
        return $e;
    }

    public function toArray(): array
    {
        // Mantener compatibilidad: devolvemos la fila original si existe.
        if (!empty($this->raw)) return $this->raw;
        return [
            "id_promocion" => $this->id_promocion,
            "id_producto" => $this->id_producto,
        ];
    }

    public function jsonSerialize(): mixed
    {
        return $this->toArray();
    }
}