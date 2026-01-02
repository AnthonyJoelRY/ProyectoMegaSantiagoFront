<?php
// Model/Service/PedidoService.php

class PedidoService
{

    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function crearPedido(int $idUsuario, array $carrito): int
    {

        $this->pdo->beginTransaction();

        try {

            // 1️⃣ Obtener precios reales desde BD
            $ids = array_map(fn($p) => (int)$p["id"], $carrito);

            $placeholders = implode(',', array_fill(0, count($ids), '?'));

            $stmt = $this->pdo->prepare("
            SELECT id_producto, precio, precio_oferta
            FROM productos
            WHERE id_producto IN ($placeholders)
        ");
            $stmt->execute($ids);
            $productosBD = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $mapProductos = [];
            foreach ($productosBD as $p) {
                $mapProductos[$p["id_producto"]] =
                    $p["precio_oferta"] > 0 ? $p["precio_oferta"] : $p["precio"];
            }

            // 2️⃣ Calcular totales
            $totalProductos = 0;

            foreach ($carrito as $p) {
                $id = (int)$p["id"];
                $cantidad = (int)$p["cantidad"];

                if (!isset($mapProductos[$id])) {
                    throw new Exception("Producto no encontrado");
                }

                $totalProductos += $mapProductos[$id] * $cantidad;
            }

            $iva = round($totalProductos * 0.12, 2);
            $totalPagar = $totalProductos + $iva;

            // 3️⃣ Insertar pedido
            $stmt = $this->pdo->prepare("
            INSERT INTO pedidos
(id_usuario, total_productos, total_iva, total_pagar, estado, fecha_pedido)
VALUES (?, ?, ?, ?, 'pagado', NOW())

        ");
            $stmt->execute([$idUsuario, $totalProductos, $iva, $totalPagar]);

            $idPedido = $this->pdo->lastInsertId();

            // 4️⃣ Insertar detalle
            $stmtDetalle = $this->pdo->prepare("
            INSERT INTO pedido_detalle
            (id_pedido, id_producto, cantidad, precio_unit, subtotal)
            VALUES (?, ?, ?, ?, ?)
        ");

            foreach ($carrito as $p) {
                $id = (int)$p["id"];
                $cantidad = (int)$p["cantidad"];
                $precio = $mapProductos[$id];
                $subtotal = $precio * $cantidad;

                $stmtDetalle->execute([
                    $idPedido,
                    $id,
                    $cantidad,
                    $precio,
                    $subtotal
                ]);
            }

            $this->pdo->commit();
            return $idPedido;
        } catch (Exception $e) {
            $this->pdo->rollBack();
            throw $e;
        }
    }
}
