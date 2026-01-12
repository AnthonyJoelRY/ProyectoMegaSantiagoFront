<?php
// Model/Entity/MovimientoInventario.php

class MovimientoInventario implements JsonSerializable
{
    public int $id_movimiento = 0;
    public int $id_producto = 0;
    public string $tipo = "";
    public int $cantidad = 0;
    public string $motivo = "";
    public string $referencia = "";
    public string $fecha_mov = "";

    /** @var array<string,mixed> */
    private array $raw = [];

    public static function fromRow(array $row): self
    {
        $e = new self();
        $e->raw = $row;
        $e->id_movimiento = (int)($row["id_movimiento"] ?? 0);
        $e->id_producto = (int)($row["id_producto"] ?? 0);
        $e->tipo = (string)($row["tipo"] ?? "");
        $e->cantidad = (int)($row["cantidad"] ?? 0);
        $e->motivo = (string)($row["motivo"] ?? "");
        $e->referencia = (string)($row["referencia"] ?? "");
        $e->fecha_mov = (string)($row["fecha_mov"] ?? "");
        return $e;
    }

    public function toArray(): array
    {
        // Mantener compatibilidad: devolvemos la fila original si existe.
        if (!empty($this->raw)) return $this->raw;
        return [
            "id_movimiento" => $this->id_movimiento,
            "id_producto" => $this->id_producto,
            "tipo" => $this->tipo,
            "cantidad" => $this->cantidad,
            "motivo" => $this->motivo,
            "referencia" => $this->referencia,
            "fecha_mov" => $this->fecha_mov,
        ];
    }

    public function jsonSerialize(): mixed
    {
        return $this->toArray();
    }
}