<?php
// Model/Entity/Promocion.php

class Promocion implements JsonSerializable
{
    public int $id_promocion = 0;
    public string $nombre = "";
    public string $descripcion = "";
    public string $fecha_inicio = "";
    public string $fecha_fin = "";
    public string $tipo_descuento = "";
    public float $valor_descuento = 0.0;
    public int $activo = 0;

    /** @var array<string,mixed> */
    private array $raw = [];

    public static function fromRow(array $row): self
    {
        $e = new self();
        $e->raw = $row;
        $e->id_promocion = (int)($row["id_promocion"] ?? 0);
        $e->nombre = (string)($row["nombre"] ?? "");
        $e->descripcion = (string)($row["descripcion"] ?? "");
        $e->fecha_inicio = (string)($row["fecha_inicio"] ?? "");
        $e->fecha_fin = (string)($row["fecha_fin"] ?? "");
        $e->tipo_descuento = (string)($row["tipo_descuento"] ?? "");
        $e->valor_descuento = (float)($row["valor_descuento"] ?? 0.0);
        $e->activo = (int)($row["activo"] ?? 0);
        return $e;
    }

    public function toArray(): array
    {
        // Mantener compatibilidad: devolvemos la fila original si existe.
        if (!empty($this->raw)) return $this->raw;
        return [
            "id_promocion" => $this->id_promocion,
            "nombre" => $this->nombre,
            "descripcion" => $this->descripcion,
            "fecha_inicio" => $this->fecha_inicio,
            "fecha_fin" => $this->fecha_fin,
            "tipo_descuento" => $this->tipo_descuento,
            "valor_descuento" => $this->valor_descuento,
            "activo" => $this->activo,
        ];
    }

    public function jsonSerialize(): mixed
    {
        return $this->toArray();
    }
}