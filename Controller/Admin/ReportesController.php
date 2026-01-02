<?php
// Controller/Admin/ReportesController.php

require_once __DIR__ . "/../../Model/Config/base.php";

class ReportesController {

    private function asegurarAdmin(): void {
        if (!isset($_SESSION["rol"]) || (int)$_SESSION["rol"] !== 1) {
            header("Location: " . PROJECT_BASE . "/index.html");
            exit;
        }
    }

    public function index(): void {
        $this->asegurarAdmin();
        $seccionActiva = "reportes";
        require __DIR__ . "/../../View/admin/reportes/index.view.php";
    }
}
