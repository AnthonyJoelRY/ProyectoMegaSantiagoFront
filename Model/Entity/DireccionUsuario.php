<?php
declare(strict_types=1);

// Model/Entity/DireccionUsuario.php
// Entity para tabla `direcciones_usuario`.

class DireccionUsuario
{
    public function __construct(
        public int $id_direccion,
        public int $id_usuario,
        public string $tipo = "envio",               // 'envio' | 'facturacion'
        public string $direccion = "",
        public ?string $ciudad = null,
        public ?string $provincia = null,
        public ?string $codigo_postal = null,
        public ?string $referencia = null,
        public int $es_principal = 0
    ) {}

    /** @param array<string,mixed> $row */
    public static function fromRow(array $row): self
    {
        return new self(
            (int)($row["id_direccion"] ?? 0),
            (int)($row["id_usuario"] ?? 0),
            (string)($row["tipo"] ?? "envio"),
            (string)($row["direccion"] ?? ""),
            isset($row["ciudad"]) ? (string)$row["ciudad"] : null,
            isset($row["provincia"]) ? (string)$row["provincia"] : null,
            isset($row["codigo_postal"]) ? (string)$row["codigo_postal"] : null,
            isset($row["referencia"]) ? (string)$row["referencia"] : null,
            (int)($row["es_principal"] ?? 0)
        );
    }

    /** @return array<string,mixed> */
    public function toArray(): array
    {
        return [
            "id_direccion" => $this->id_direccion,
            "id_usuario" => $this->id_usuario,
            "tipo" => $this->tipo,
            "direccion" => $this->direccion,
            "ciudad" => $this->ciudad,
            "provincia" => $this->provincia,
            "codigo_postal" => $this->codigo_postal,
            "referencia" => $this->referencia,
            "es_principal" => $this->es_principal,
        ];
    }
}
