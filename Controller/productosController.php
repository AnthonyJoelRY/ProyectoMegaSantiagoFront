<?php
// Controller/productosController.php

// ✅ DEBUG TEMPORAL (quítalo cuando ya funcione)
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . "/_helpers/Bootstrap.php";

// ✅ Headers
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=utf-8");

require_once __DIR__ . "/../Model/DB/db.php";
require_once __DIR__ . "/../Model/Factory/ProductoServiceFactory.php";

try {
    $pdo = obtenerConexion();
    $service = (new ProductoServiceFactory())->create($pdo);

    $accion = $_GET["accion"] ?? "";

    $routes = [

        "listarPorCategoria" => function () use ($service) {
            $categoria = trim($_GET["categoria"] ?? "");
            $res = $service->listarPorCategoria($categoria);
            if (isset($res["error"])) api_responder($res, 400);
            api_responder($res, 200);
        },

        // ✅ Opción A: búsqueda pública (sin id_usuario)
        "buscar" => function () use ($service) {
            $termino = trim($_GET["q"] ?? "");

            // ✅ FIX: tu service pide 2 args -> le mandamos null como idUsuario
            $res = $service->buscar($termino, null);

            if (isset($res["error"])) api_responder($res, 400);
            api_responder($res, 200);
        },

        "masVendidos" => function () use ($service) {
            $limit = isset($_GET["limit"]) ? (int)$_GET["limit"] : 4;
            $res = $service->masVendidos($limit);
            if (isset($res["error"])) api_responder($res, 400);
            api_responder($res, 200);
        },

        "ofertas" => function () use ($service) {
            $limit = isset($_GET["limit"]) ? (int)$_GET["limit"] : 6;
            $res = $service->ofertas($limit);
            if (isset($res["error"])) api_responder($res, 400);
            api_responder($res, 200);
        },

        "promociones" => function () use ($service) {
            $limit = isset($_GET["limit"]) ? (int)$_GET["limit"] : 3;
            $res = $service->promociones($limit);
            if (isset($res["error"])) api_responder($res, 400);
            api_responder($res, 200);
        },

        "detalle" => function () use ($service) {
            $id = isset($_GET["id"]) ? (int)$_GET["id"] : 0;
            $res = $service->detalle($id);
            if (isset($res["error"])) api_responder($res, 404);
            api_responder($res, 200);
        },

        "relacionados" => function () use ($service) {
            $id = isset($_GET["id"]) ? (int)$_GET["id"] : 0;
            $limit = isset($_GET["limit"]) ? (int)$_GET["limit"] : 4;
            $res = $service->relacionados($id, $limit);
            if (isset($res["error"])) api_responder($res, 400);
            api_responder($res, 200);
        },
    ];

    if (!isset($routes[$accion])) {
        api_responder(["error" => "Acción no válida"], 400);
    }

    $routes[$accion]();

} catch (Throwable $e) {
    // ✅ Ahora sí verás el error real en el navegador
    http_response_code(500);
    echo json_encode([
        "error"  => "Internal Server Error",
        "detail" => $e->getMessage(),
        "file"   => basename($e->getFile()),
        "line"   => $e->getLine(),
    ], JSON_UNESCAPED_UNICODE);
}
