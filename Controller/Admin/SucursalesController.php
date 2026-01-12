<?php
// Controller/Admin/SucursalesController.php

declare(strict_types=1);

require_once __DIR__ . "/../../Model/Config/base.php";
require_once __DIR__ . "/../../Model/DB/db.php";
require_once __DIR__ . "/../../Model/DAO/SucursalDAO.php";

class SucursalesController
{
    private function asegurarAdmin(): void
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }
        $rol = isset($_SESSION["rol"]) ? (int)$_SESSION["rol"] : 0;
        // ✅ SOLO Admin puede gestionar sucursales
        if (!isset($_SESSION["usuario"]) || $rol !== 1) {
            header("Location: " . PROJECT_BASE . "/index.html");
            exit;
        }
    }

    public function index(): void
    {
        $this->asegurarAdmin();

        $q = trim((string)($_GET["q"] ?? ""));
        $pdo = obtenerConexion();
        $dao = new SucursalDAO($pdo);
        $sucursales = $dao->listar($q);

        $seccionActiva = "sucursales";
        require __DIR__ . "/../../View/admin/sucursales/index.view.php";
    }

    public function nuevo(): void
    {
        $this->asegurarAdmin();
        $seccionActiva = "sucursales";
        require __DIR__ . "/../../View/admin/sucursales/nuevo.view.php";
    }

    public function editar(): void
    {
        $this->asegurarAdmin();
        $id = (int)($_GET["id"] ?? 0);
        if ($id <= 0) {
            header("Location: " . PROJECT_BASE . "/panel/sucursales");
            exit;
        }

        $pdo = obtenerConexion();
        $dao = new SucursalDAO($pdo);
        $sucursal = $dao->obtenerPorId($id);
        if (!$sucursal) {
            header("Location: " . PROJECT_BASE . "/panel/sucursales");
            exit;
        }

        $seccionActiva = "sucursales";
        require __DIR__ . "/../../View/admin/sucursales/editar.view.php";
    }

    public function acciones(): void
    {
        $this->asegurarAdmin();

        if ($_SERVER["REQUEST_METHOD"] !== "POST") {
            header("Location: " . PROJECT_BASE . "/panel/sucursales");
            exit;
        }

        $accion = (string)($_POST["accion"] ?? "");
        $pdo = obtenerConexion();
        $dao = new SucursalDAO($pdo);

        try {
            switch ($accion) {
                case "crear":
                    $dao->crear($_POST);
                    break;
                case "editar":
                    $id = (int)($_POST["id_sucursal"] ?? 0);
                    if ($id > 0) $dao->actualizar($id, $_POST);
                    break;
                case "activar":
                    $id = (int)($_POST["id_sucursal"] ?? 0);
                    if ($id > 0) $dao->activar($id);
                    break;
                case "desactivar":
                    $id = (int)($_POST["id_sucursal"] ?? 0);
                    if ($id > 0) $dao->desactivar($id);
                    break;
            }
        } catch (Throwable $e) {
            // puedes guardar log si quieres
        }

        header("Location: " . PROJECT_BASE . "/panel/sucursales");
        exit;
    }
}
