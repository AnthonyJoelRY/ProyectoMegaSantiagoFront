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

    // Campos administrativos / extras (opcionales)
    public ?string $sku = null;
    public ?int $activo = null;
    public ?int $stock = null;

    // Detalle / extras
    public array $imagenes = [];
    public array $colores  = [];

    /** @var array<string,mixed> */
    private array $raw = [];

    public static function fromRow(array $row): self
    {
        $p = new self();
        $p->raw = $row;

        // id puede venir como id (alias) o id_producto (tabla)
        if (isset($row["id"])) $p->id = (int)$row["id"];
        if (isset($row["id_producto"])) $p->id = (int)$row["id_producto"];

        if (isset($row["nombre"])) $p->nombre = (string)$row["nombre"];

        if (array_key_exists("descripcion_corta", $row)) {
            $p->descripcion_corta = $row["descripcion_corta"] !== null ? (string)$row["descripcion_corta"] : null;
        }
        if (array_key_exists("descripcion_larga", $row)) {
            $p->descripcion_larga = $row["descripcion_larga"] !== null ? (string)$row["descripcion_larga"] : null;
        }

        if (array_key_exists("precio", $row)) {
            $p->precio = $row["precio"] !== null ? (float)$row["precio"] : null;
        }
        if (array_key_exists("precio_oferta", $row)) {
            $p->precio_oferta = $row["precio_oferta"] !== null ? (float)$row["precio_oferta"] : null;
        }

        // imagen puede venir como imagen (alias) o url_imagen
        if (array_key_exists("imagen", $row)) {
            $p->imagen = $row["imagen"] !== null ? (string)$row["imagen"] : null;
        } elseif (array_key_exists("url_imagen", $row)) {
            $p->imagen = $row["url_imagen"] !== null ? (string)$row["url_imagen"] : null;
        }

        // extras admin
        if (array_key_exists("sku", $row)) $p->sku = $row["sku"] !== null ? (string)$row["sku"] : null;
        if (array_key_exists("activo", $row)) $p->activo = $row["activo"] !== null ? (int)$row["activo"] : null;

        // stock puede venir como stock o stock_actual
        if (array_key_exists("stock", $row)) {
            $p->stock = $row["stock"] !== null ? (int)$row["stock"] : null;
        } elseif (array_key_exists("stock_actual", $row)) {
            $p->stock = $row["stock_actual"] !== null ? (int)$row["stock_actual"] : null;
        }

        // arrays opcionales
        if (isset($row["imagenes"]) && is_array($row["imagenes"])) $p->imagenes = $row["imagenes"];
        if (isset($row["colores"]) && is_array($row["colores"])) $p->colores = $row["colores"];

        return $p;
    }

    public function toArray(): array
    {
        // Mantener compatibilidad: devolvemos la fila original si existe.
        if (!empty($this->raw)) return $this->raw;

        return [
            "id" => $this->id,
            "nombre" => $this->nombre,
            "descripcion_corta" => $this->descripcion_corta,
            "descripcion_larga" => $this->descripcion_larga,
            "precio" => $this->precio,
            "precio_oferta" => $this->precio_oferta,
            "imagen" => $this->imagen,
            "imagenes" => $this->imagenes,
            "colores" => $this->colores,
            "sku" => $this->sku,
            "activo" => $this->activo,
            "stock" => $this->stock,
        ];
    }

    public function jsonSerialize(): mixed
    {
        return $this->toArray();
    }
}
