<?php
// Controller/Admin/ReportesController.php

require_once __DIR__ . "/../../Model/Config/base.php";

class ReportesController {

    private function asegurarPanelReportes(): void {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }
        
        $rol = isset($_SESSION["rol"]) ? (int)$_SESSION["rol"] : 0;
        
        if (!isset($_SESSION["usuario"]) || ($rol !== 1 && $rol !== 4)) {
            header("Location: " . PROJECT_BASE . "/index.html");
            exit;
        }
    }

    public function index(): void {
        // Ahora el método está dentro de la clase correctamente
        $this->asegurarPanelReportes();
        $seccionActiva = "reportes";
        require __DIR__ . "/../../View/admin/reportes/index.view.php";
    }

} // Cierre único de la clase