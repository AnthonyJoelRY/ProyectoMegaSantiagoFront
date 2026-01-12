<?php
declare(strict_types=1);

// Model/DAO/InventarioDAO.php
// DAO para tabla `inventario`. Mantiene compatibilidad: expone métodos que retornan arrays o Entities.

require_once __DIR__ . "/../Entity/Inventario.php";

class InventarioDAO
{
    public function __construct(private PDO $pdo) {}

    /** @return array<int, array<string,mixed>> */
    public function listar(): array
    {
        $stmt = $this->pdo->query("SELECT * FROM `inventario`");
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    /** @return array<int, Inventario> */
    public function listarEntidades(): array
    {
        return array_map(fn($r) => Inventario::fromRow($r), $this->listar());
    }

    public function obtenerPorId(int $id): ?array
    {
        $stmt = $this->pdo->prepare("SELECT * FROM `inventario` WHERE `id_producto` = :id LIMIT 1");
        $stmt->execute([":id" => $id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    public function obtenerEntidadPorId(int $id): ?Inventario
    {
        $row = $this->obtenerPorId($id);
        return $row ? Inventario::fromRow($row) : null;
    }

public function insertar(array $data): int
{
file_put_contents(
    $_SERVER['DOCUMENT_ROOT'] . "/debug_inventario_otro.log",
    date("Y-m-d H:i:s") . " | InventarioDAO::insertar | data=" . json_encode($data) . PHP_EOL,
    FILE_APPEND
);


if (!isset($data["id_producto"]) || (int)$data["id_producto"] <= 0) {
    throw new Exception("❌ InventarioDAO::insertar recibió id_producto inválido: " . ($data["id_producto"] ?? "NULL"));
}


    $sql = "
        INSERT INTO `inventario`
            (`id_producto`, `stock_actual`, `ubicacion_almacen`, `ultima_actualizacion`)
        VALUES
            (:id_producto, :stock_actual, :ubicacion_almacen, :ultima_actualizacion)
    ";

    $stmt = $this->pdo->prepare($sql);
    $stmt->execute(
        $this->filtrar(
            $data,
            ['id_producto', 'stock_actual', 'ubicacion_almacen', 'ultima_actualizacion']
        )
    );

    return (int)$data['id_producto'];
}


    public function actualizar(int $id, array $data): bool
    {
        $sql = "UPDATE `inventario` SET `stock_actual` = :stock_actual, `ubicacion_almacen` = :ubicacion_almacen, `ultima_actualizacion` = :ultima_actualizacion WHERE `id_producto` = :id";
        $stmt = $this->pdo->prepare($sql);
        $params = $this->filtrar($data, ['stock_actual', 'ubicacion_almacen', 'ultima_actualizacion']);
        $params[":id"] = $id;
        return $stmt->execute($params);
    }

    public function eliminar(int $id): bool
    {
        $stmt = $this->pdo->prepare("DELETE FROM `inventario` WHERE `id_producto` = :id");
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