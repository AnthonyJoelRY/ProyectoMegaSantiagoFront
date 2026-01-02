<?php
// Model/Entity/Producto.php
//
// Entity de dominio para Producto.
// - NO contiene SQL ni lógica de persistencia.
// - Se usa para mapear filas (arrays) provenientes de DAO a un objeto consistente.
// - toArray() devuelve la fila original para no romper vistas/controladores existentes.

class Producto implements JsonSerializable
{
    // Campos comunes (pueden variar según la consulta SQL)
    public int $id = 0;
    public string $nombre = "";

    public ?string $descripcion_corta = null;
    public ?string $descripcion_larga = null;

    public ?float $precio = null;
    public ?float $precio_oferta = null;

    public ?string $imagen = null;

    // Detalle / extras
    public array $imagenes = [];
    public array $colores  = [];

    /** @var array<string,mixed> */
    private array $raw = [];

    public static function fromRow(array $row): self
    {
        $p = new self();
        $p->raw = $row;

        if (isset($row["id"])) $p->id = (int)$row["id"];
        if (isset($row["nombre"])) $p->nombre = (string)$row["nombre"];

        if (array_key_exists("descripcion_corta", $row)) $p->descripcion_corta = $row["descripcion_corta"] !== null ? (string)$row["descripcion_corta"] : null;
        if (array_key_exists("descripcion_larga", $row)) $p->descripcion_larga = $row["descripcion_larga"] !== null ? (string)$row["descripcion_larga"] : null;

        if (array_key_exists("precio", $row) && $row["precio"] !== null) $p->precio = (float)$row["precio"];
        if (array_key_exists("precio_oferta", $row) && $row["precio_oferta"] !== null) $p->precio_oferta = (float)$row["precio_oferta"];

        if (array_key_exists("imagen", $row)) $p->imagen = $row["imagen"] !== null ? (string)$row["imagen"] : null;

        if (isset($row["imagenes"]) && is_array($row["imagenes"])) $p->imagenes = $row["imagenes"];
        if (isset($row["colores"]) && is_array($row["colores"])) $p->colores = $row["colores"];

        return $p;
    }

    /**
     * Devuelve la estructura original que viene del DAO.
     * Esto permite integrar Entity sin romper código existente que consume arrays.
     */
    public function toArray(): array
    {
        return $this->raw;
    }

    public function jsonSerialize(): mixed
    {
        return $this->toArray();
    }
}
