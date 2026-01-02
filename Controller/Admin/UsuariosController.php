<?php
// Controller/Admin/UsuariosController.php

require_once __DIR__ . "/../../Model/Config/base.php";
require_once __DIR__ . "/../../Model/DB/db.php";
require_once __DIR__ . "/../../Model/Service/Admin/AdminUsuarioService.php";

class UsuariosController {

    private function asegurarAdmin(): void {
        if (!isset($_SESSION["rol"]) || (int)$_SESSION["rol"] !== 1) {
            header("Location: " . PROJECT_BASE . "/index.html");
            exit;
        }
    }

    public function index(): void {
        $this->asegurarAdmin();

        $pdo = obtenerConexion();
        $service = new AdminUsuarioService($pdo);

        $roles = $service->roles();
        $usuarios = $service->listar();

        $seccionActiva = "usuarios";
        require __DIR__ . "/../../View/admin/usuarios/index.view.php";
    }

    public function editar(): void {
        $this->asegurarAdmin();

        $id = (int)($_GET["id"] ?? 0);
        if ($id <= 0) { header("Location: " . PROJECT_BASE . "/panel/usuarios"); exit; }

        $pdo = obtenerConexion();
        $service = new AdminUsuarioService($pdo);

        $usuario = $service->obtener($id);
        if (!$usuario) { header("Location: " . PROJECT_BASE . "/panel/usuarios"); exit; }

        $roles = $service->roles();
        $seccionActiva = "usuarios";
        require __DIR__ . "/../../View/admin/usuarios/editar.view.php";
    }

    public function acciones(): void {
        $this->asegurarAdmin();

        if ($_SERVER["REQUEST_METHOD"] !== "POST") {
            header("Location: " . PROJECT_BASE . "/panel/usuarios");
            exit;
        }

        $accion = $_POST["accion"] ?? "";
        $pdo = obtenerConexion();
        $service = new AdminUsuarioService($pdo);

        try {
            switch ($accion) {
                case "cambiar_rol":
                    $idUsuario = (int)($_POST["id_usuario"] ?? 0);
                    $idRol = (int)($_POST["id_rol"] ?? 0);
                    $service->cambiarRol($idUsuario, $idRol);
                    header("Location: " . PROJECT_BASE . "/panel/usuarios?ok=1");
                    exit;

                case "toggle":
                    $idUsuario = (int)($_POST["id_usuario"] ?? 0);
                    $service->toggle($idUsuario);
                    header("Location: " . PROJECT_BASE . "/panel/usuarios?ok=1");
                    exit;

                case "editar":
                    $idUsuario = (int)($_POST["id_usuario"] ?? 0);
                    $service->actualizar($idUsuario, $_POST);
                    header("Location: " . PROJECT_BASE . "/panel/usuarios/editar?id=" . $idUsuario . "&ok=1");
                    exit;

                default:
                    header("Location: " . PROJECT_BASE . "/panel/usuarios");
                    exit;
            }
        } catch (Throwable $e) {
            die($e->getMessage());
        }
    }
}
