<?php
// Controller/Admin/InventarioSucursalController.php

declare(strict_types=1);

require_once __DIR__ . "/../../Model/Config/base.php";
require_once __DIR__ . "/../../Model/DB/db.php";
require_once __DIR__ . "/../../Model/DAO/SucursalDAO.php";
require_once __DIR__ . "/../../Model/DAO/InventarioSucursalDAO.php";

class InventarioSucursalController
{
    private function asegurarAdminOEmpleado(): void
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }
        $rol = isset($_SESSION["rol"]) ? (int)$_SESSION["rol"] : 0;
        if (!isset($_SESSION["usuario"]) || ($rol !== 1 && $rol !== 4)) {
            header("Location: " . PROJECT_BASE . "/index.html");
            exit;
        }
    }

    /**
     * Pantalla principal: elegir sucursal y ver/editar inventario
     */
    public function index(): void
    {
        $this->asegurarAdminOEmpleado();

        $pdo = obtenerConexion();
        $sucursalDAO = new SucursalDAO($pdo);
        $invDAO = new InventarioSucursalDAO($pdo);

        $sucursales = $sucursalDAO->listar();
        $idSucursal = (int)($_GET["id_sucursal"] ?? 0);
        if ($idSucursal <= 0 && !empty($sucursales)) {
            $idSucursal = (int)($sucursales[0]["id_sucursal"] ?? 0);
        }

        $q = trim((string)($_GET["q"] ?? ""));

        // Lista inventario (si tabla no existe aún, devuelve [])
        $inventario = $idSucursal > 0 ? $invDAO->listarPorSucursal($idSucursal, $q) : [];

        // Productos para el selector (limitado para no matar el panel)
        $productos = [];
        try {
            $stmt = $pdo->query("SELECT id_producto, nombre FROM productos ORDER BY nombre ASC LIMIT 500");
            $productos = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
        } catch (Throwable $e) {
            $productos = [];
        }

        $seccionActiva = "inventario_sucursal";
        require __DIR__ . "/../../View/admin/inventario_sucursal/index.view.php";
    }

    /**
     * Acciones POST:
     * - upsert: guardar stock
     * - copiar_global: copiar stock global (inventario) -> inventario_sucursal para una sucursal
     */
    public function acciones(): void
    {
        $this->asegurarAdminOEmpleado();

        if ($_SERVER["REQUEST_METHOD"] !== "POST") {
            header("Location: " . PROJECT_BASE . "/panel/inventario-sucursal");
            exit;
        }

        $accion = (string)($_POST["accion"] ?? "");
        $idSucursal = (int)($_POST["id_sucursal"] ?? 0);

        $pdo = obtenerConexion();
        $invDAO = new InventarioSucursalDAO($pdo);

        try {
            if ($accion === "upsert") {
                $idProducto = (int)($_POST["id_producto"] ?? 0);
                $stockActual = (int)($_POST["stock_actual"] ?? 0);
                $stockMinimo = (int)($_POST["stock_minimo"] ?? 0);
                if ($idSucursal > 0 && $idProducto > 0) {
                    $invDAO->upsertStock($idSucursal, $idProducto, max(0, $stockActual), max(0, $stockMinimo));
                }
            }

            if ($accion === "copiar_global") {
                // Copia todo inventario global a inventario_sucursal para esta sucursal
                if ($idSucursal > 0) {
                    $stmt = $pdo->query("SELECT id_producto, stock_actual FROM inventario");
                    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
                    foreach ($rows as $r) {
                        $invDAO->upsertStock(
                            $idSucursal,
                            (int)($r["id_producto"] ?? 0),
                            (int)($r["stock_actual"] ?? 0),
                            0
                        );
                    }
                }
            }
        } catch (Throwable $e) {
            // podrías guardar log
        }

        header("Location: " . PROJECT_BASE . "/panel/inventario-sucursal?id_sucursal=" . $idSucursal);
        exit;
    }
}
