<?php
declare(strict_types=1);

// Model/DAO/MovimientoInventarioDAO.php
// DAO para tabla `movimientos_inventario`. Mantiene compatibilidad: expone métodos que retornan arrays o Entities.

require_once __DIR__ . "/../Entity/MovimientoInventario.php";

class MovimientoInventarioDAO
{
    public function __construct(private PDO $pdo) {}

    /** @return array<int, array<string,mixed>> */
    public function listar(): array
    {
        $stmt = $this->pdo->query("SELECT * FROM `movimientos_inventario`");
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    /** @return array<int, MovimientoInventario> */
    public function listarEntidades(): array
    {
        return array_map(fn($r) => MovimientoInventario::fromRow($r), $this->listar());
    }

    public function obtenerPorId(int $id): ?array
    {
        $stmt = $this->pdo->prepare("SELECT * FROM `movimientos_inventario` WHERE `id_movimiento` = :id LIMIT 1");
        $stmt->execute([":id" => $id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    public function obtenerEntidadPorId(int $id): ?MovimientoInventario
    {
        $row = $this->obtenerPorId($id);
        return $row ? MovimientoInventario::fromRow($row) : null;
    }

    public function insertar(array $data): int
    {
        $sql = "INSERT INTO `movimientos_inventario` (`id_movimiento`, `id_producto`, `tipo`, `cantidad`, `motivo`, `referencia`, `fecha_mov`) VALUES (:id_movimiento, :id_producto, :tipo, :cantidad, :motivo, :referencia, :fecha_mov)";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($this->filtrar($data, ['id_movimiento', 'id_producto', 'tipo', 'cantidad', 'motivo', 'referencia', 'fecha_mov']));
        return (int)($data['id_movimiento'] ?? 0);
    }

    public function actualizar(int $id, array $data): bool
    {
        $sql = "UPDATE `movimientos_inventario` SET `id_producto` = :id_producto, `tipo` = :tipo, `cantidad` = :cantidad, `motivo` = :motivo, `referencia` = :referencia, `fecha_mov` = :fecha_mov WHERE `id_movimiento` = :id";
        $stmt = $this->pdo->prepare($sql);
        $params = $this->filtrar($data, ['id_producto', 'tipo', 'cantidad', 'motivo', 'referencia', 'fecha_mov']);
        $params[":id"] = $id;
        return $stmt->execute($params);
    }

    public function eliminar(int $id): bool
    {
        $stmt = $this->pdo->prepare("DELETE FROM `movimientos_inventario` WHERE `id_movimiento` = :id");
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