<?php
// Model/DAO/Admin/AdminDashboardDAO.php

class AdminDashboardDAO {
    public function __construct(private PDO $pdo) {}

    public function countProductos(): int {
        return (int)$this->pdo->query("SELECT COUNT(*) FROM productos")->fetchColumn();
    }

    public function countUsuarios(): int {
        return (int)$this->pdo->query("SELECT COUNT(*) FROM usuarios")->fetchColumn();
    }

    public function countAdmins(): int {
        return (int)$this->pdo->query("SELECT COUNT(*) FROM usuarios WHERE id_rol = 1")->fetchColumn();
    }

    public function countClientes(): int {
        return (int)$this->pdo->query("SELECT COUNT(*) FROM usuarios WHERE id_rol = 3")->fetchColumn();
    }

    public function countProductosOferta(): int {
        return (int)$this->pdo->query("
            SELECT COUNT(*)
            FROM productos
            WHERE precio_oferta IS NOT NULL
              AND precio_oferta > 0
        ")->fetchColumn();
    }

    public function countProductosActivos(): int {
        return (int)$this->pdo->query("SELECT COUNT(*) FROM productos WHERE activo = 1")->fetchColumn();
    }

    public function countInventarioSinStock(): int {
        return (int)$this->pdo->query("
            SELECT COUNT(*)
            FROM inventario
            WHERE stock_actual <= 0
        ")->fetchColumn();
    }

    public function countInventarioBajoStock(): int
{
    return (int)$this->pdo->query("
        SELECT COUNT(*)
        FROM productos p
        LEFT JOIN inventario i ON i.id_producto = p.id_producto
        WHERE p.activo = 1
          AND COALESCE(i.stock_actual, 0) <= p.stock_minimo
    ")->fetchColumn();
}


    public function ultimoProductoNombre(): string {
        return (string)$this->pdo->query("
            SELECT nombre
            FROM productos
            ORDER BY id_producto DESC
            LIMIT 1
        ")->fetchColumn();
    }

    public function ultimoUsuarioEmail(): string {
        return (string)$this->pdo->query("
            SELECT email
            FROM usuarios
            ORDER BY fecha_registro DESC
            LIMIT 1
        ")->fetchColumn();
    }
}
