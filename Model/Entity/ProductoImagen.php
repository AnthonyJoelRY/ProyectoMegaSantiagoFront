<?php
// Model/Entity/ProductoImagen.php

class ProductoImagen implements JsonSerializable
{
    public int $id_imagen = 0;
    public int $id_producto = 0;
    public string $url_imagen = "";
    public int $es_principal = 0;
    public int $orden = 0;

    /** @var array<string,mixed> */
    private array $raw = [];

    public static function fromRow(array $row): self
    {
        $e = new self();
        $e->raw = $row;
        $e->id_imagen = (int)($row["id_imagen"] ?? 0);
        $e->id_producto = (int)($row["id_producto"] ?? 0);
        $e->url_imagen = (string)($row["url_imagen"] ?? "");
        $e->es_principal = (int)($row["es_principal"] ?? 0);
        $e->orden = (int)($row["orden"] ?? 0);
        return $e;
    }

    public function toArray(): array
    {
        // Mantener compatibilidad: devolvemos la fila original si existe.
        if (!empty($this->raw)) return $this->raw;
        return [
            "id_imagen" => $this->id_imagen,
            "id_producto" => $this->id_producto,
            "url_imagen" => $this->url_imagen,
            "es_principal" => $this->es_principal,
            "orden" => $this->orden,
        ];
    }

    public function jsonSerialize(): mixed
    {
        return $this->toArray();
    }
}