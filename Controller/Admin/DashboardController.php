<?php
// Controller/Admin/DashboardController.php

require_once __DIR__ . "/../../Model/Config/base.php";
require_once __DIR__ . "/../../Model/DB/db.php";
require_once __DIR__ . "/../../Model/Service/Admin/AdminDashboardService.php";

class DashboardController {

    private function asegurarDashboard(): void {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }
        if (!isset($_SESSION["usuario"]) || !isset($_SESSION["rol"])) {
            header("Location: " . PROJECT_BASE . "/index.html");
            exit;
        }
        $rol = (int)$_SESSION["rol"];
        if ($rol === 4) {
            // Empleado: dashboard limitado -> redirigir a pedidos
            header("Location: " . PROJECT_BASE . "/panel/pedidos");
            exit;
        }
        if ($rol !== 1) {
            header("Location: " . PROJECT_BASE . "/index.html");
            exit;
        }
    }


    public function index(): void {
        $this->asegurarDashboard();

        $pdo = obtenerConexion();
        $service = new AdminDashboardService($pdo);
        $data = $service->obtenerResumen();

        // variables para la vista
        extract($data);

        require __DIR__ . "/../../View/admin/dashboard.view.php";
    }
}
