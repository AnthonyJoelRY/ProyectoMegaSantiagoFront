<?php
// Controller/usuariosController.php

require_once __DIR__ . "/_helpers/Bootstrap.php";

require_once __DIR__ . "/../Model/DB/db.php";
require_once __DIR__ . "/../Model/Factory/UsuarioServiceFactory.php";

$pdo     = obtenerConexion();
$service = (new UsuarioServiceFactory())->create($pdo);

$accion = $_GET["accion"] ?? "";
$data   = api_read_json_body();

// ===============================
// ROUTES (SIN SWITCH)
// ===============================
$routes = [

    // -------- REGISTRO VENDEDOR --------
    "registrar" => function () use ($service, $data) {
        $email = trim($data["email"] ?? "");
        $clave = trim($data["clave"] ?? "");

	        $res = $service->registrarVendedor($email, $clave);
	        if (isset($res["error"])) api_responder($res, 400);

	        api_responder($res, 200);
    },

    // -------- REGISTRO EMPRESA + PROPIETARIO --------
    "registrarEmpresa" => function () use ($service, $data) {

	        $res = $service->registrarEmpresa($data);
	        if (isset($res["error"])) api_responder($res, 400);

	        api_responder($res, 200);
    },

    // -------- LOGIN --------
    "login" => function () use ($service, $data) {
        $email = trim($data["email"] ?? "");
        $clave = trim($data["clave"] ?? "");

	        $res = $service->login($email, $clave);
	        if (isset($res["error"])) api_responder($res, 401);

	        api_responder($res, 200);
    },
];

if (!isset($routes[$accion])) {
	api_responder(["error" => "Acción inválida."], 400);
}

$routes[$accion]();
