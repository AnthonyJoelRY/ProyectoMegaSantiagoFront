<?php
// Controller/reportesController.php

require_once __DIR__ . "/_helpers/Bootstrap.php";

require_once __DIR__ . "/../Model/DB/db.php";
require_once __DIR__ . "/../Model/Factory/ReporteServiceFactory.php";

$pdo     = obtenerConexion();
$service = (new ReporteServiceFactory())->create($pdo);

$accion = $_GET["accion"] ?? "";

// ===============================
// ROUTES (SIN SWITCH)
// ===============================
$routes = [

    // ===============================
    // KPIs
    // ===============================
    "kpis" => function () use ($service) {
        api_responder([
            "ventasTotales"      => $service->ventasTotales(),
            "totalPedidos"       => $service->totalPedidos(),
            "totalClientes"      => $service->totalClientes(),
            "promedioPorPedido"  => $service->promedioPorPedido(),
            "totalIVA"           => $service->totalIVA(),
        ]);
    },

    // ===============================
    // VENTAS
    // ===============================
    "ventasDia" => function () use ($service) {
        api_responder($service->ventasPorDia());
    },

    "ventasMes" => function () use ($service) {
        api_responder($service->ventasPorMes());
    },

    // ===============================
    // PRODUCTOS
    // ===============================
    "productosMasVendidos" => function () use ($service) {
        api_responder($service->productosMasVendidos(5));
    },

    "productosMenosVendidos" => function () use ($service) {
        api_responder($service->productosMenosVendidos(5));
    },

    "productosSinStock" => function () use ($service) {
        api_responder($service->productosSinStock());
    },

    "productosStockBajo" => function () use ($service) {
        api_responder($service->productosStockBajo());
    },

    // ===============================
    // CLIENTES
    // ===============================
    "clientesTop" => function () use ($service) {
        api_responder($service->clientesTop());
    },
];


if (!isset($routes[$accion])) {
    api_responder(["error" => "Acción inválida."], 400);
}

$routes[$accion]();
