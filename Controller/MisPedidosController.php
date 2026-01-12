<?php
// Controller/MisPedidosController.php
// API para que cualquier usuario autenticado vea únicamente SUS pedidos.

require_once __DIR__ . "/_helpers/Bootstrap.php";
require_once __DIR__ . "/_helpers/Api.php";

require_once __DIR__ . "/../Model/DB/db.php";
require_once __DIR__ . "/../Model/DAO/PedidoDAO.php";
require_once __DIR__ . "/../Model/DAO/Admin/AdminPedidoDAO.php";

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

function require_login(): array
{
    $idUsuario = isset($_SESSION['id']) ? (int)$_SESSION['id'] : 0;
    $email = isset($_SESSION['email']) ? (string)$_SESSION['email'] : '';

    // Requiere sesión válida
    if ($idUsuario <= 0 || $email === '') {
        api_responder(["ok" => false, "error" => "No autorizado"], 403);
    }

    return ["id" => $idUsuario, "email" => $email];
}

$pdo = obtenerConexion();
$pedidoDAO = new PedidoDAO($pdo);
$adminPedidoDAO = new AdminPedidoDAO($pdo);

$accion = $_GET['accion'] ?? '';

$routes = [
    // GET /Controller/MisPedidosController.php?accion=listar
    'listar' => function () use ($pedidoDAO) {
        $u = require_login();
        $pedidos = $pedidoDAO->listarPorUsuario((int)$u['id']);
        api_responder(["ok" => true, "pedidos" => $pedidos], 200);
    },

    // GET /Controller/MisPedidosController.php?accion=ver&id=123
    'ver' => function () use ($pedidoDAO, $adminPedidoDAO) {
        $u = require_login();
        $id = (int)($_GET['id'] ?? 0);
        if ($id <= 0) {
            api_responder(["ok" => false, "error" => "ID inválido"], 400);
        }

        // Seguridad: el pedido debe pertenecer al usuario
        $pedidoBase = $pedidoDAO->obtenerPorIdYUsuario($id, (int)$u['id']);
        if (!$pedidoBase) {
            api_responder(["ok" => false, "error" => "Pedido no encontrado"], 404);
        }

        // Reutilizamos el detalle enriquecido del panel (incluye sucursal/dirección si existe)
        $pedido = $adminPedidoDAO->obtener($id);
        if (!$pedido) {
            api_responder(["ok" => false, "error" => "Pedido no encontrado"], 404);
        }

        // Doble-check (por seguridad)
        if ((int)($pedido['id_usuario'] ?? 0) !== (int)$u['id']) {
            api_responder(["ok" => false, "error" => "No autorizado"], 403);
        }

        $detalle = $adminPedidoDAO->detalle($id);

        // Devolvemos solo lo necesario
        $out = [
            'id_pedido' => (int)$pedido['id_pedido'],
            'fecha_pedido' => (string)($pedido['fecha_pedido'] ?? ''),
            'estado' => (string)($pedido['estado'] ?? ''),
            'total_pagar' => (float)($pedido['total_pagar'] ?? 0),
            'tipo_entrega' => (string)($pedido['tipo_entrega'] ?? ''),

            // Sucursal / dirección (según aplique)
            'sucursal_retiro' => [
                'nombre' => $pedido['sucursal_retiro_nombre'] ?? null,
                'direccion' => $pedido['sucursal_retiro_direccion'] ?? null,
                'ciudad' => $pedido['sucursal_retiro_ciudad'] ?? null,
                'telefono' => $pedido['sucursal_retiro_telefono'] ?? null,
                'horario' => $pedido['sucursal_retiro_horario'] ?? null,
            ],
            'sucursal_origen' => [
                'nombre' => $pedido['sucursal_origen_nombre'] ?? null,
                'direccion' => $pedido['sucursal_origen_direccion'] ?? null,
                'ciudad' => $pedido['sucursal_origen_ciudad'] ?? null,
                'telefono' => $pedido['sucursal_origen_telefono'] ?? null,
                'horario' => $pedido['sucursal_origen_horario'] ?? null,
            ],
            'direccion_envio' => [
                'direccion' => $pedido['direccion_envio_direccion'] ?? null,
                'ciudad' => $pedido['direccion_envio_ciudad'] ?? null,
                'provincia' => $pedido['direccion_envio_provincia'] ?? null,
                'codigo_postal' => $pedido['direccion_envio_codigo_postal'] ?? null,
                'referencia' => $pedido['direccion_envio_referencia'] ?? null,
            ],

            'items' => $detalle,
        ];

        api_responder(["ok" => true, "pedido" => $out], 200);
    },
];

if (!isset($routes[$accion])) {
    api_responder(["ok" => false, "error" => "Acción no válida"], 400);
}

$routes[$accion]();
