<?php
// Model/DAO/Admin/AdminPedidoDAO.php

class AdminPedidoDAO {
    public function __construct(private PDO $pdo) {}

    public function listarPagados(string $q = ""): array {
        $sql = "
            SELECT
                p.id_pedido,
                u.email AS cliente,
                p.fecha_pedido,
                p.total_pagar,
                p.estado
            FROM pedidos p
            JOIN usuarios u ON u.id_usuario = p.id_usuario
            WHERE p.estado = 'pagado'
        ";
        $params = [];
        if (trim($q) !== '') {
            $sql .= " AND (u.email LIKE :q OR p.id_pedido LIKE :q) ";
            $params[":q"] = "%" . trim($q) . "%";
        }
        $sql .= " ORDER BY p.id_pedido DESC ";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    public function obtener(int $id): ?array {
        $stmt = $this->pdo->prepare("
            SELECT
                p.id_pedido,
                p.fecha_pedido,
                p.total_productos,
                p.total_iva,
                p.total_pagar,
                p.estado,
                u.email AS cliente
            FROM pedidos p
            JOIN usuarios u ON u.id_usuario = p.id_usuario
            WHERE p.id_pedido = ?
        ");
        $stmt->execute([$id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    public function detalle(int $id): array {
        $stmt = $this->pdo->prepare("
            SELECT
                d.cantidad,
                d.precio_unit,
                d.subtotal,
                pr.nombre
            FROM pedido_detalle d
            JOIN productos pr ON pr.id_producto = d.id_producto
            WHERE d.id_pedido = ?
        ");
        $stmt->execute([$id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }
}
