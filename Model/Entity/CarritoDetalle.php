<?php
// Model/Entity/CarritoDetalle.php

class CarritoDetalle implements JsonSerializable
{
    public int $id_detalle = 0;
    public int $id_carrito = 0;
    public int $id_producto = 0;
    public int $cantidad = 0;
    public float $precio_unit = 0.0;
    public float $subtotal = 0.0;

    /** @var array<string,mixed> */
    private array $raw = [];

    public static function fromRow(array $row): self
    {
        $e = new self();
        $e->raw = $row;
        $e->id_detalle = (int)($row["id_detalle"] ?? 0);
        $e->id_carrito = (int)($row["id_carrito"] ?? 0);
        $e->id_producto = (int)($row["id_producto"] ?? 0);
        $e->cantidad = (int)($row["cantidad"] ?? 0);
        $e->precio_unit = (float)($row["precio_unit"] ?? 0.0);
        $e->subtotal = (float)($row["subtotal"] ?? 0.0);
        return $e;
    }

    public function toArray(): array
    {
        // Mantener compatibilidad: devolvemos la fila original si existe.
        if (!empty($this->raw)) return $this->raw;
        return [
            "id_detalle" => $this->id_detalle,
            "id_carrito" => $this->id_carrito,
            "id_producto" => $this->id_producto,
            "cantidad" => $this->cantidad,
            "precio_unit" => $this->precio_unit,
            "subtotal" => $this->subtotal,
        ];
    }

    public function jsonSerialize(): mixed
    {
        return $this->toArray();
    }
}