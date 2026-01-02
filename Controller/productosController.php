<?php
// Controller/productosController.php


require_once __DIR__ . "/_helpers/Bootstrap.php";

header("Access-Control-Allow-Origin: *");

require_once __DIR__ . "/../Model/DB/db.php";
require_once __DIR__ . "/../Model/Factory/ProductoServiceFactory.php";

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

    "buscar" => function () use ($service) {
        $termino = trim($_GET["q"] ?? "");
        $idUsuario = isset($_GET["id_usuario"]) ? (int)$_GET["id_usuario"] : null;

        $res = $service->buscar($termino, $idUsuario);
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

    // ============================
    // DETALLE DE PRODUCTO
    // ============================
    "detalle" => function () use ($service) {
        $id = isset($_GET["id"]) ? (int)$_GET["id"] : 0;
        $res = $service->detalle($id);
        if (isset($res["error"])) api_responder($res, 404);
        api_responder($res, 200);
    },

    // ============================
    // PRODUCTOS RELACIONADOS
    // ============================
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

