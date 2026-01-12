<?php
// Controller/Admin/PedidosController.php

declare(strict_types=1);

require_once __DIR__ . "/../../Model/Config/base.php";
require_once __DIR__ . "/../../Model/DB/db.php";
require_once __DIR__ . "/../../Model/Service/Admin/AdminPedidoService.php";

class PedidosController
{
    private function asegurarPanelPedidos(): void
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

    private function asegurarEmpleado(): void
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }
        $rol = isset($_SESSION["rol"]) ? (int)$_SESSION["rol"] : 0;
        if (!isset($_SESSION["usuario"]) || ($rol !== 1 && $rol !== 4)) {
            header("Location: " . PROJECT_BASE . "/panel/pedidos");
            exit;
        }
    }

    public function index(): void
    {
        $this->asegurarPanelPedidos();

        $q = trim((string)($_GET["q"] ?? ""));
        $pdo = obtenerConexion();
        $service = new AdminPedidoService($pdo);
        $pedidos = $service->listarPagados($q);

        $seccionActiva = "pedidos";
        require __DIR__ . "/../../View/admin/pedidos/index.view.php";
    }

    public function ver(): void
    {
        $this->asegurarPanelPedidos();

        $id = (int)($_GET["id"] ?? 0);
        if ($id <= 0) {
            header("Location: " . PROJECT_BASE . "/panel/pedidos");
            exit;
        }

        $pdo = obtenerConexion();
        $service = new AdminPedidoService($pdo);

        try {
            $data = $service->obtenerConDetalle($id);
            $pedido = $data["pedido"] ?? null;
            $detalles = $data["detalle"] ?? [];
            if (!$pedido) throw new RuntimeException("Pedido no encontrado");
        } catch (Throwable $e) {
            header("Location: " . PROJECT_BASE . "/panel/pedidos");
            exit;
        }

        $seccionActiva = "pedidos";
        require __DIR__ . "/../../View/admin/pedidos/ver.view.php";
    }

    // Cambiar estado (solo Empleado)
    public function estado(): void
    {
        $this->asegurarEmpleado();

        $id = (int)($_POST["id_pedido"] ?? 0);
        $estado = trim((string)($_POST["estado"] ?? ""));

        if ($id <= 0 || $estado === "") {
            header("Location: " . PROJECT_BASE . "/panel/pedidos");
            exit;
        }

        $pdo = obtenerConexion();
        $service = new AdminPedidoService($pdo);

        try {
            $service->cambiarEstado($id, $estado);
        } catch (Throwable $e) {
            // podrías mostrar un mensaje; por ahora redirigimos
        }

        $redirectTo = trim((string)($_POST["redirect_to"] ?? ""));
        // Evitar open-redirect: solo permitimos rutas internas del panel
        if ($redirectTo !== "" && strpos($redirectTo, PROJECT_BASE . "/panel/") === 0) {
            header("Location: " . $redirectTo);
            exit;
        }

        header("Location: " . PROJECT_BASE . "/panel/pedidos/ver?id=" . $id);
        exit;
    }
}
