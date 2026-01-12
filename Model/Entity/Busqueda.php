<?php
// Model/Entity/Busqueda.php

class Busqueda implements JsonSerializable
{
    public int $id_busqueda = 0;
    public string $termino = "";
    public int $id_usuario = 0;
    public string $fecha_busqueda = "";
    public int $resultados = 0;

    /** @var array<string,mixed> */
    private array $raw = [];

    public static function fromRow(array $row): self
    {
        $e = new self();
        $e->raw = $row;
        $e->id_busqueda = (int)($row["id_busqueda"] ?? 0);
        $e->termino = (string)($row["termino"] ?? "");
        $e->id_usuario = (int)($row["id_usuario"] ?? 0);
        $e->fecha_busqueda = (string)($row["fecha_busqueda"] ?? "");
        $e->resultados = (int)($row["resultados"] ?? 0);
        return $e;
    }

    public function toArray(): array
    {
        // Mantener compatibilidad: devolvemos la fila original si existe.
        if (!empty($this->raw)) return $this->raw;
        return [
            "id_busqueda" => $this->id_busqueda,
            "termino" => $this->termino,
            "id_usuario" => $this->id_usuario,
            "fecha_busqueda" => $this->fecha_busqueda,
            "resultados" => $this->resultados,
        ];
    }

    public function jsonSerialize(): mixed
    {
        return $this->toArray();
    }
}