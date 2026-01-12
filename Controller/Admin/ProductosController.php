<?php
// Controller/Admin/ProductosController.php

require_once __DIR__ . "/../../Model/Config/base.php";
require_once __DIR__ . "/../../Model/DB/db.php";
require_once __DIR__ . "/../../Model/Service/Admin/AdminProductoService.php";


class ProductosController {

    private function asegurarAdminOEmpleado(): void {
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
        $this->asegurarAdminOEmpleado();

        $q = trim($_GET["q"] ?? "");
        $pdo = obtenerConexion();
        $service = new AdminProductoService($pdo);

        $productos = $service->listar($q);
        $busqueda = $q;

        $seccionActiva = "productos";
        require __DIR__ . "/../../View/admin/productos/index.view.php";
    }

    public function nuevo(): void {
        $this->asegurarAdminOEmpleado();

        $pdo = obtenerConexion();
        $service = new AdminProductoService($pdo);
        $categorias = $service->categorias();

        // Imágenes disponibles (si usas locales, no se usan actualmente)
        $carpeta = __DIR__ . "/../../Model/imagenes/";
        $imagenes = [];
        if (is_dir($carpeta)) {
            foreach (scandir($carpeta) as $f) {
                if ($f === "." || $f === "..") continue;
                $ext = strtolower(pathinfo($f, PATHINFO_EXTENSION));
                if (in_array($ext, ["png","jpg","jpeg","webp","gif"])) $imagenes[] = $f;
            }
        }

        $seccionActiva = "productos";
        require __DIR__ . "/../../View/admin/productos/nuevo.view.php";
    }

    public function editar(): void {
        $this->asegurarAdminOEmpleado();

        $id = (int)($_GET["id"] ?? 0);
        if ($id <= 0) {
            header("Location: " . PROJECT_BASE . "/panel/productos");
            exit;
        }

        $pdo = obtenerConexion();
        $service = new AdminProductoService($pdo);

        $producto = $service->obtener($id);
        if (!$producto) {
            header("Location: " . PROJECT_BASE . "/panel/productos");
            exit;
        }

        $categorias = $service->categorias();

        $seccionActiva = "productos";
        require __DIR__ . "/../../View/admin/productos/editar.view.php";
    }

    public function acciones(): void {
        $this->asegurarAdminOEmpleado();

        if ($_SERVER["REQUEST_METHOD"] !== "POST") {
            header("Location: " . PROJECT_BASE . "/panel/productos");
            exit;
        }

        $accion = $_POST["accion"] ?? "";
        $pdo = obtenerConexion();
        $service = new AdminProductoService($pdo);

        // 🔴 DEBUG: registrar cada POST que entra a acciones()
file_put_contents(
    __DIR__ . "/debug_post.log",
    date("Y-m-d H:i:s") . " | accion=" . $accion . " | POST=" . json_encode($_POST) . PHP_EOL,
    FILE_APPEND
);
        
        try {
            
            switch ($accion) {
                case "crear":
                    $service->crear($_POST);
                    header("Location: " . PROJECT_BASE . "/panel/productos?ok=1");
                    exit;

                case "editar":
    $id = (int)($_POST["id_producto"] ?? 0);
    $service->editar($id, $_POST);
    header("Location: " . PROJECT_BASE . "/panel/productos?edit=1");
    exit;


                case "desactivar":
                    $id = (int)($_POST["id_producto"] ?? 0);
                    $service->desactivar($id);
                    header("Location: " . PROJECT_BASE . "/panel/productos?ok=1");
                    exit;

                case "activar":
                    $id = (int)($_POST["id_producto"] ?? 0);
                    $service->activar($id);
                    header("Location: " . PROJECT_BASE . "/panel/productos?ok=1");
                    exit;

                default:
                    header("Location: " . PROJECT_BASE . "/panel/productos");
                    exit;
            }
        } catch (Throwable $e) {
    echo "<pre>";
    echo "ERROR: " . $e->getMessage() . "\n\n";
    echo "ARCHIVO: " . $e->getFile() . "\n";
    echo "LINEA: " . $e->getLine() . "\n\n";
    echo "TRACE:\n" . $e->getTraceAsString();
    echo "</pre>";
    exit;
}

    }
}
