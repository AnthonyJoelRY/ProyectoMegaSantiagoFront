<?php
// Controller/Admin/DashboardController.php

require_once __DIR__ . "/../../Model/Config/base.php";
require_once __DIR__ . "/../../Model/DB/db.php";
require_once __DIR__ . "/../../Model/Service/Admin/AdminDashboardService.php";

class DashboardController {

    private function asegurarAdmin(): void {
        if (!isset($_SESSION["usuario"]) || !isset($_SESSION["rol"]) || (int)$_SESSION["rol"] !== 1) {
            header("Location: " . PROJECT_BASE . "/index.html");
            exit;
        }
    }

    public function index(): void {
        $this->asegurarAdmin();

        $pdo = obtenerConexion();
        $service = new AdminDashboardService($pdo);
        $data = $service->obtenerResumen();

        // variables para la vista
        extract($data);

        require __DIR__ . "/../../View/admin/dashboard.view.php";
    }
}
