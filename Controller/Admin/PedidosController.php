<?php
// Controller/Admin/PedidosController.php

require_once __DIR__ . "/../../Model/Config/base.php";
require_once __DIR__ . "/../../Model/DB/db.php";
require_once __DIR__ . "/../../Model/Service/Admin/AdminPedidoService.php";

class PedidosController {

    private function asegurarAdmin(): void {
        if (!isset($_SESSION["rol"]) || (int)$_SESSION["rol"] !== 1) {
            header("Location: " . PROJECT_BASE . "/index.html");
            exit;
        }
    }

    public function index(): void {
        $this->asegurarAdmin();

        $q = trim($_GET["q"] ?? "");
        $pdo = obtenerConexion();
        $service = new AdminPedidoService($pdo);

        $busqueda = $q;
        $pedidos = $service->listarPagados($q);

        $seccionActiva = "pedidos";
        require __DIR__ . "/../../View/admin/pedidos/index.view.php";
    }

    public function ver(): void {
        $this->asegurarAdmin();

        $id = (int)($_GET["id"] ?? 0);
        if ($id <= 0) { header("Location: " . PROJECT_BASE . "/panel/pedidos"); exit; }

        $pdo = obtenerConexion();
        $service = new AdminPedidoService($pdo);

        try {
            $data = $service->obtenerConDetalle($id);
            $pedido = $data["pedido"];
            $detalle = $data["detalle"];
        } catch (Throwable $e) {
            header("Location: " . PROJECT_BASE . "/panel/pedidos");
            exit;
        }

        $seccionActiva = "pedidos";
        require __DIR__ . "/../../View/admin/pedidos/ver.view.php";
    }
}
