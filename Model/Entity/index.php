<?php
// index.php (Front Controller global)
// Un único punto de entrada para TODO el proyecto.
// - Panel sin exponer /admin en la URL: /dashboard y /panel/*
// - Mantiene compatibilidad: /admin/* redirige a /panel/*
// - Archivos reales (CSS/JS/imagenes/html/php existentes) se sirven directo (vía .htaccess)

require_once __DIR__ . '/Model/Config/base.php';

// Normaliza el path solicitado (sin querystring)
$uriPath = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?? '/';

// Quitar el PROJECT_BASE del inicio (si el proyecto vive en subcarpeta)
if (PROJECT_BASE !== '' && str_starts_with($uriPath, PROJECT_BASE)) {
    $uriPath = substr($uriPath, strlen(PROJECT_BASE));
}

$path = trim($uriPath, '/'); // ej: "panel/productos" o "login"

// Home: / -> sirve el index.html existente
if ($path === '') {
    $home = __DIR__ . '/index.html';
    if (is_file($home)) {
        header('Content-Type: text/html; charset=utf-8');
        readfile($home);
        exit;
    }
    http_response_code(404);
    echo 'No existe index.html';
    exit;
}

$lower = strtolower($path);

// ==============================
// 1) Compatibilidad /admin/*
// ==============================
if ($lower === 'admin' || str_starts_with($lower, 'admin/')) {
    $sub = trim(substr($lower, 5), '/'); // quita "admin"
    if ($sub === '' || $sub === 'dashboard') {
        header('Location: ' . PROJECT_BASE . '/dashboard', true, 302);
        exit;
    }
    header('Location: ' . PROJECT_BASE . '/panel/' . $sub, true, 302);
    exit;
}

// ==============================
// 2) Rutas públicas (HTML)
// ==============================
$publicMap = [
    'login'               => 'login.html',
    'registro'            => 'registro_empresa.html',
    'recuperar-clave'     => 'recuperar_clave.html',
    'carrito'             => 'carrito.html',
    'buscar'              => 'busqueda.html',
    'producto'            => 'producto.html',
    'bazar'               => 'bazar.html',
    'papeleria'           => 'papeleria.html',
    'productos-arte'      => 'productos-arte.html',
    'suministros-oficina' => 'suministros-oficina.html',
    'utiles-escolares'    => 'utiles-escolares.html',
];

if (isset($publicMap[$lower])) {
    $file = __DIR__ . '/View/pages/' . $publicMap[$lower];
    if (is_file($file)) {
        header('Content-Type: text/html; charset=utf-8');

        // Render “estático” pero arreglando rutas relativas (../../View, ../../Controller, etc.)
        $html = file_get_contents($file);
        $base = PROJECT_BASE;

        // Normaliza rutas típicas usadas en View/pages/*.html
        $html = str_replace('href="../../View/', 'href="' . $base . '/View/', $html);
        $html = str_replace('src="../../View/',  'src="'  . $base . '/View/', $html);
        $html = str_replace('href="../../Controller/', 'href="' . $base . '/Controller/', $html);
        $html = str_replace('src="../../Controller/',  'src="'  . $base . '/Controller/', $html);


        // Reemplazos adicionales dentro de JS inline (strings con ../../View o ../../Controller)
        $html = str_replace('"../../View/', '"' . $base . '/View/', $html);
        $html = str_replace("'../../View/", "'" . $base . '/View/', $html);
        $html = str_replace('"../../Controller/', '"' . $base . '/Controller/', $html);
        $html = str_replace("'../../Controller/", "'" . $base . '/Controller/', $html);

        echo $html;
        exit;
    }
}

// ==============================
// 3) Panel (antes /admin)
// ==============================

// Iniciar sesión solo en rutas del panel
if ($lower === 'dashboard' || $lower === 'panel' || str_starts_with($lower, 'panel/')) {
    if (session_status() !== PHP_SESSION_ACTIVE) {
        session_start();
    }
}

// Dashboard
if ($lower === 'dashboard') {
    require_once __DIR__ . '/Controller/Admin/DashboardController.php';
    (new DashboardController())->index();
    exit;
}

// /panel -> manda a /dashboard
if ($lower === 'panel') {
    header('Location: ' . PROJECT_BASE . '/dashboard', true, 302);
    exit;
}

if (str_starts_with($lower, 'panel/')) {
    $sub = trim(substr($lower, 6), '/'); // quita "panel/"
    $sub = preg_replace('/\.php$/', '', $sub);
    $sub = preg_replace('#/index$#', '', $sub);
    $sub = trim($sub, '/');

    // Productos
    if ($sub === 'productos') {
        require_once __DIR__ . '/Controller/Admin/ProductosController.php';
        (new ProductosController())->index();
        exit;
    }
    if (str_starts_with($sub, 'productos/')) {
        $action = substr($sub, strlen('productos/'));
        require_once __DIR__ . '/Controller/Admin/ProductosController.php';
        $c = new ProductosController();
        if ($action === 'nuevo') { $c->nuevo(); exit; }
        if ($action === 'editar') { $c->editar(); exit; }
        if ($action === 'acciones') { $c->acciones(); exit; }
    }

    // Usuarios
    if ($sub === 'usuarios') {
        require_once __DIR__ . '/Controller/Admin/UsuariosController.php';
        (new UsuariosController())->index();
        exit;
    }
    if (str_starts_with($sub, 'usuarios/')) {
        $action = substr($sub, strlen('usuarios/'));
        require_once __DIR__ . '/Controller/Admin/UsuariosController.php';
        $c = new UsuariosController();
        if ($action === 'editar') { $c->editar(); exit; }
        if ($action === 'acciones') { $c->acciones(); exit; }
    }

    // Pedidos
    if ($sub === 'pedidos') {
        require_once __DIR__ . '/Controller/Admin/PedidosController.php';
        (new PedidosController())->index();
        exit;
    }
    if (str_starts_with($sub, 'pedidos/')) {
        $action = substr($sub, strlen('pedidos/'));
        require_once __DIR__ . '/Controller/Admin/PedidosController.php';
        $c = new PedidosController();
        if ($action === 'ver') { $c->ver(); exit; }
        if ($action === 'estado') { $c->estado(); exit; }
    }

    // Reportes
    if ($sub === 'reportes') {
        require_once __DIR__ . '/Controller/Admin/ReportesController.php';
        (new ReportesController())->index();
        exit;
    }

    // Sucursales
    if ($sub === 'sucursales') {
        require_once __DIR__ . '/Controller/Admin/SucursalesController.php';
        (new SucursalesController())->index();
        exit;
    }
    if (str_starts_with($sub, 'sucursales/')) {
        $action = substr($sub, strlen('sucursales/'));
        require_once __DIR__ . '/Controller/Admin/SucursalesController.php';
        $c = new SucursalesController();
        if ($action === 'nuevo') { $c->nuevo(); exit; }
        if ($action === 'editar') { $c->editar(); exit; }
        if ($action === 'acciones') { $c->acciones(); exit; }
    }

    // Inventario por sucursal
    if ($sub === 'inventario-sucursal') {
        require_once __DIR__ . '/Controller/Admin/InventarioSucursalController.php';
        (new InventarioSucursalController())->index();
        exit;
    }
    if (str_starts_with($sub, 'inventario-sucursal/')) {
        $action = substr($sub, strlen('inventario-sucursal/'));
        require_once __DIR__ . '/Controller/Admin/InventarioSucursalController.php';
        $c = new InventarioSucursalController();
        if ($action === 'acciones') { $c->acciones(); exit; }
    }

    http_response_code(404);
    echo 'Ruta de panel no encontrada';
    exit;
}

http_response_code(404);
echo 'Página no encontrada';
