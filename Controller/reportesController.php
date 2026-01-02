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
    "kpis" => function () use ($service) {
        // La vista admin espera estas llaves (ventasTotales, totalPedidos, etc.)
        api_responder([
            "ventasTotales"      => $service->ventasTotales(),
            "totalPedidos"      => $service->totalPedidos(),
            "totalClientes"     => $service->totalClientes(),
            "promedioPorPedido" => $service->promedioPorPedido(),
            "totalIVA"          => $service->totalIVA(),
        ]);
    },
    "ventasDia" => function () use ($service) {
        api_responder($service->ventasPorDia());
    },
    "ventasMes" => function () use ($service) {
        api_responder($service->ventasPorMes());
    },
    "productosMasVendidos" => function () use ($service) {
        api_responder($service->productosMasVendidos(5));
    },
    "productosMenosVendidos" => function () use ($service) {
        api_responder($service->productosMenosVendidos(5));
    },
    "clientesTop" => function () use ($service) {
        // método no recibe parámetro en esta base, ya viene con LIMIT 5
        api_responder($service->clientesTop());
    },
];

if (!isset($routes[$accion])) {
    api_responder(["error" => "Acción inválida."], 400);
}

$routes[$accion]();
