<?php
// Model/Entity/Carrito.php

class Carrito implements JsonSerializable
{
    public int $id_carrito = 0;
    public int $id_usuario = 0;
    public string $token_sesion = "";
    public string $fecha_creacion = "";
    public string $estado = "";

    /** @var array<string,mixed> */
    private array $raw = [];

    public static function fromRow(array $row): self
    {
        $e = new self();
        $e->raw = $row;
        $e->id_carrito = (int)($row["id_carrito"] ?? 0);
        $e->id_usuario = (int)($row["id_usuario"] ?? 0);
        $e->token_sesion = (string)($row["token_sesion"] ?? "");
        $e->fecha_creacion = (string)($row["fecha_creacion"] ?? "");
        $e->estado = (string)($row["estado"] ?? "");
        return $e;
    }

    public function toArray(): array
    {
        // Mantener compatibilidad: devolvemos la fila original si existe.
        if (!empty($this->raw)) return $this->raw;
        return [
            "id_carrito" => $this->id_carrito,
            "id_usuario" => $this->id_usuario,
            "token_sesion" => $this->token_sesion,
            "fecha_creacion" => $this->fecha_creacion,
            "estado" => $this->estado,
        ];
    }

    public function jsonSerialize(): mixed
    {
        return $this->toArray();
    }
}