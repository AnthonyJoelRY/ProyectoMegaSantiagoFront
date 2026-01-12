<?php
declare(strict_types=1);

// Model/DAO/CarritoDetalleDAO.php
// DAO para tabla `carrito_detalle`. Mantiene compatibilidad: expone métodos que retornan arrays o Entities.

require_once __DIR__ . "/../Entity/CarritoDetalle.php";

class CarritoDetalleDAO
{
    public function __construct(private PDO $pdo) {}

    /** @return array<int, array<string,mixed>> */
    public function listar(): array
    {
        $stmt = $this->pdo->query("SELECT * FROM `carrito_detalle`");
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    /** @return array<int, CarritoDetalle> */
    public function listarEntidades(): array
    {
        return array_map(fn($r) => CarritoDetalle::fromRow($r), $this->listar());
    }

    public function obtenerPorId(int $id): ?array
    {
        $stmt = $this->pdo->prepare("SELECT * FROM `carrito_detalle` WHERE `id_detalle` = :id LIMIT 1");
        $stmt->execute([":id" => $id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    public function obtenerEntidadPorId(int $id): ?CarritoDetalle
    {
        $row = $this->obtenerPorId($id);
        return $row ? CarritoDetalle::fromRow($row) : null;
    }

    public function insertar(array $data): int
    {
        $sql = "INSERT INTO `carrito_detalle` (`id_detalle`, `id_carrito`, `id_producto`, `cantidad`, `precio_unit`, `subtotal`) VALUES (:id_detalle, :id_carrito, :id_producto, :cantidad, :precio_unit, :subtotal)";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($this->filtrar($data, ['id_detalle', 'id_carrito', 'id_producto', 'cantidad', 'precio_unit', 'subtotal']));
        return (int)($data['id_detalle'] ?? 0);
    }

    public function actualizar(int $id, array $data): bool
    {
        $sql = "UPDATE `carrito_detalle` SET `id_carrito` = :id_carrito, `id_producto` = :id_producto, `cantidad` = :cantidad, `precio_unit` = :precio_unit, `subtotal` = :subtotal WHERE `id_detalle` = :id";
        $stmt = $this->pdo->prepare($sql);
        $params = $this->filtrar($data, ['id_carrito', 'id_producto', 'cantidad', 'precio_unit', 'subtotal']);
        $params[":id"] = $id;
        return $stmt->execute($params);
    }

    public function eliminar(int $id): bool
    {
        $stmt = $this->pdo->prepare("DELETE FROM `carrito_detalle` WHERE `id_detalle` = :id");
        return $stmt->execute([":id" => $id]);
    }

    /** @param array<string,mixed> $data @param array<int,string> $keys */
    private function filtrar(array $data, array $keys): array
    {
        $out = [];
        foreach ($keys as $k) {
            if (array_key_exists($k, $data)) $out[":".$k] = $data[$k];
        }
        return $out;
    }
}