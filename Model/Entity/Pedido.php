<?php
// Model/Entity/Pedido.php

class Pedido implements JsonSerializable
{
    public int $id_pedido = 0;
    public int $id_usuario = 0;
    public string $fecha_pedido = "";
    public float $total_productos = 0.0;
    public float $total_iva = 0.0;
    public float $total_pagar = 0.0;
    public string $estado = "";
    public string $tipo_entrega = "envio"; // envio | retiro_local
    public ?int $id_sucursal_retiro = null;
    public ?int $id_sucursal_origen = null;
    public ?int $id_direccion_envio = null;
    public string $observaciones = "";

    /** @var array<string,mixed> */
    private array $raw = [];

    public static function fromRow(array $row): self
    {
        $e = new self();
        $e->raw = $row;
        $e->id_pedido = (int)($row["id_pedido"] ?? 0);
        $e->id_usuario = (int)($row["id_usuario"] ?? 0);
        $e->fecha_pedido = (string)($row["fecha_pedido"] ?? "");
        $e->total_productos = (float)($row["total_productos"] ?? 0.0);
        $e->total_iva = (float)($row["total_iva"] ?? 0.0);
        $e->total_pagar = (float)($row["total_pagar"] ?? 0.0);
        $e->estado = (string)($row["estado"] ?? "");
        $e->tipo_entrega = (string)($row["tipo_entrega"] ?? "envio");
        $e->id_sucursal_retiro = isset($row["id_sucursal_retiro"]) ? (int)$row["id_sucursal_retiro"] : null;
        $e->id_sucursal_origen = isset($row["id_sucursal_origen"]) ? (int)$row["id_sucursal_origen"] : null;
        $e->id_direccion_envio = isset($row["id_direccion_envio"]) ? (int)$row["id_direccion_envio"] : null;
        $e->observaciones = (string)($row["observaciones"] ?? "");
        return $e;
    }

    public function toArray(): array
    {
        // Mantener compatibilidad: devolvemos la fila original si existe.
        if (!empty($this->raw)) return $this->raw;
        return [
            "id_pedido" => $this->id_pedido,
            "id_usuario" => $this->id_usuario,
            "fecha_pedido" => $this->fecha_pedido,
            "total_productos" => $this->total_productos,
            "total_iva" => $this->total_iva,
            "total_pagar" => $this->total_pagar,
            "estado" => $this->estado,
            "tipo_entrega" => $this->tipo_entrega,
            "id_sucursal_retiro" => $this->id_sucursal_retiro,
            "id_sucursal_origen" => $this->id_sucursal_origen,
            "id_direccion_envio" => $this->id_direccion_envio,
            "observaciones" => $this->observaciones,
        ];
    }

    public function jsonSerialize(): mixed
    {
        return $this->toArray();
    }
}