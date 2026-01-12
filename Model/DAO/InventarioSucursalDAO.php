<?php
declare(strict_types=1);

// Model/DAO/InventarioSucursalDAO.php
// Manejo de stock por sucursal (tabla inventario_sucursal).
// ✅ Fallback: si no hay registro por sucursal, usa tabla `inventario` (stock global).

class InventarioSucursalDAO
{
    public function __construct(private PDO $pdo) {}

    public function obtenerStock(int $idSucursal, int $idProducto): int
    {
        // 1) intento por sucursal
        $stmt = $this->pdo->prepare("SELECT stock_actual FROM inventario_sucursal WHERE id_sucursal = :s AND id_producto = :p LIMIT 1");
        try {
            $stmt->execute(["s" => $idSucursal, "p" => $idProducto]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($row && isset($row["stock_actual"])) return (int)$row["stock_actual"];
        } catch (Throwable $e) {
            // si tabla no existe aún, cae al fallback
        }

        // 2) fallback global
        $stmt2 = $this->pdo->prepare("SELECT stock_actual FROM inventario WHERE id_producto = :p LIMIT 1");
        $stmt2->execute(["p" => $idProducto]);
        $row2 = $stmt2->fetch(PDO::FETCH_ASSOC);
        return (int)($row2["stock_actual"] ?? 0);
    }

    public function reducirStock(int $idSucursal, int $idProducto, int $cantidad): void
    {
        if ($cantidad <= 0) return;

        // Si existe inventario_sucursal, usamos esa tabla
        try {
            // ⚠️ PDO puede fallar si el mismo placeholder nombrado se repite (HY093).
            // Usamos placeholders distintos para el mismo valor.
            $stmt = $this->pdo->prepare("
                UPDATE inventario_sucursal
                SET stock_actual = stock_actual - :c1,
                    ultima_actualizacion = CURRENT_TIMESTAMP
                WHERE id_sucursal = :s AND id_producto = :p AND stock_actual >= :c2
            ");
            $stmt->execute(["c1" => $cantidad, "c2" => $cantidad, "s" => $idSucursal, "p" => $idProducto]);

            if ($stmt->rowCount() > 0) return; // ok

            // si no había fila, no podemos reducir (stock insuficiente o inexistente)
            // caemos al fallback global si la tabla inventario_sucursal no tiene el item
        } catch (Throwable $e) {
            // tabla no existe o error: fallback
        }

        // fallback global
        // ⚠️ Igual aquí: evitar repetir :c
        $stmt2 = $this->pdo->prepare("
            UPDATE inventario
            SET stock_actual = stock_actual - :c1,
                ultima_actualizacion = CURRENT_TIMESTAMP
            WHERE id_producto = :p AND stock_actual >= :c2
        ");
        $stmt2->execute(["c1" => $cantidad, "c2" => $cantidad, "p" => $idProducto]);
        if ($stmt2->rowCount() === 0) {
            throw new Exception("Stock insuficiente para el producto #$idProducto");
        }
    }

    /**
     * Valida stock de TODO el carrito en una sucursal.
     * @param array<int,array<string,mixed>> $carrito
     */
    public function validarStockEnSucursal(int $idSucursal, array $carrito): array
    {
        $faltantes = [];
        foreach ($carrito as $p) {
            $id = (int)($p["id"] ?? 0);
            $cant = (int)($p["cantidad"] ?? 0);
            if ($id <= 0 || $cant <= 0) continue;

            $stock = $this->obtenerStock($idSucursal, $id);
            if ($stock < $cant) {
                $faltantes[] = [
                    "id_producto" => $id,
                    "requerido" => $cant,
                    "stock" => $stock
                ];
            }
        }
        return $faltantes; // vacío => ok
    }

    /**
     * Busca una sucursal que tenga stock para todo el carrito.
     * @param array<int,array<string,mixed>> $carrito
     * @param array<int,int> $sucursalesIds
     */
    public function buscarSucursalConStock(array $carrito, array $sucursalesIds): ?int
    {
        foreach ($sucursalesIds as $idSucursal) {
            $faltantes = $this->validarStockEnSucursal((int)$idSucursal, $carrito);
            if (empty($faltantes)) return (int)$idSucursal;
        }
        return null;
    }

    /**
     * Lista inventario por sucursal con nombre de producto.
     * @return array<int,array<string,mixed>>
     */
    public function listarPorSucursal(int $idSucursal, ?string $q = null): array
    {
        $q = trim((string)$q);
        $sql = "
            SELECT i.id_sucursal, i.id_producto, i.stock_actual, i.stock_minimo, i.ultima_actualizacion,
                   p.nombre AS producto_nombre
            FROM inventario_sucursal i
            INNER JOIN productos p ON p.id_producto = i.id_producto
            WHERE i.id_sucursal = :s
        ";
        $params = ["s" => $idSucursal];
        if ($q !== '') {
            $sql .= " AND (p.nombre LIKE :q OR p.id_producto LIKE :q)";
            $params["q"] = "%{$q}%";
        }
        $sql .= " ORDER BY p.nombre ASC";

        try {
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
        } catch (Throwable $e) {
            // Si la tabla no existe, devolvemos vacío (el checkout hará fallback al stock global)
            return [];
        }
    }

    /**
     * Crea o actualiza (upsert) el stock de un producto en una sucursal.
     */
    public function upsertStock(int $idSucursal, int $idProducto, int $stockActual, int $stockMinimo = 0): void
    {
        try {
            $stmt = $this->pdo->prepare(
                "INSERT INTO inventario_sucursal (id_sucursal, id_producto, stock_actual, stock_minimo)
                 VALUES (:s, :p, :sa, :sm)
                 ON DUPLICATE KEY UPDATE stock_actual = VALUES(stock_actual), stock_minimo = VALUES(stock_minimo), ultima_actualizacion = CURRENT_TIMESTAMP"
            );
            $stmt->execute([
                "s" => $idSucursal,
                "p" => $idProducto,
                "sa" => $stockActual,
                "sm" => $stockMinimo,
            ]);
        } catch (Throwable $e) {
            // si no existe inventario_sucursal aún, no hacemos nada (para no romper)
            // pero lanzamos excepción para que el panel muestre un mensaje claro
            throw new Exception("No existe la tabla inventario_sucursal o no se pudo guardar el inventario. Ejecuta la migración SQL.");
        }
    }
}
