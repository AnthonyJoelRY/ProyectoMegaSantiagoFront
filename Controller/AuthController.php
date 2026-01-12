<?php
// Controller/AuthController.php

require_once __DIR__ . "/_helpers/Bootstrap.php";

// ===============================
// DEPENDENCIAS
// ===============================
require_once __DIR__ . "/../Model/DB/db.php";
require_once __DIR__ . "/../Model/Factory/AuthServiceFactory.php";

// ===============================
// INICIALIZACIÓN
// ===============================
$pdo = obtenerConexion();
$service = (new AuthServiceFactory())->create($pdo);

$accion = $_GET["accion"] ?? "";

// Leer body JSON
$raw = file_get_contents("php://input");
$data = json_decode($raw, true);
$data = is_array($data) ? $data : [];

// ===============================
// ROUTES
// ===============================
$routes = [

    // -------- REGISTRO CLIENTE --------
    "registrar" => function () use ($service, $data) {
        $nombre   = trim($data["nombre"]   ?? "");
        $apellido = trim($data["apellido"] ?? "");
        $email    = trim($data["email"]    ?? "");
        $clave    = trim($data["clave"]    ?? "");

        $res = $service->registrarCliente(
            $nombre,
            $apellido,
            $email,
            $clave
        );

        if (isset($res["error"])) {
            $code = 400;
            if (str_contains($res["error"], "ya está registrado")) {
                $code = 409;
            }
            api_responder($res, $code);
        }

        api_responder($res, 200);
    },

    // -------- REGISTRO EMPRESA --------
  "registrarEmpresa" => function () use ($service, $data) {
    $res = $service->registrarEmpresa($data);
    api_responder($res, isset($res["error"]) ? 400 : 200);
},

    // -------- LOGIN --------
    "login" => function () use ($service, $data) {
        $email = trim($data["email"] ?? "");
        $clave = trim($data["clave"] ?? "");

        $res = $service->login($email, $clave);

        if (isset($res["error"])) {
            $code = 400;
            if ($res["error"] === "Credenciales incorrectas") {
                $code = 401;
            }
            api_responder($res, $code);
        }

        api_responder($res, 200);
    },
];

// ===============================
// EJECUCIÓN
// ===============================
if (!isset($routes[$accion])) {
    api_responder(["error" => "Acción no válida"], 400);
}

$routes[$accion]();
