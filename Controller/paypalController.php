<?php
// Controller/paypalController.php

require_once __DIR__ . "/_helpers/Bootstrap.php";

// Buffer para evitar que warnings rompan el JSON
ob_start();

require_once __DIR__ . "/../Model/DB/db.php";
require_once __DIR__ . "/../Model/Factory/PayPalServiceFactory.php";
require_once __DIR__ . "/../Model/Factory/PedidoServiceFactory.php";
require_once __DIR__ . "/../Model/Factory/FacturaServiceFactory.php";
require_once __DIR__ . "/../Model/Factory/EmailServiceFactory.php";

$pdo = obtenerConexion();
$service = (new PayPalServiceFactory())->create($pdo);

$accion = $_GET["accion"] ?? "";

function leerJsonBody(): array
{
    $raw = file_get_contents("php://input");
    $data = json_decode($raw, true);
    return is_array($data) ? $data : [];
}

$routes = [

    "config" => function () use ($service) {
        api_responder($service->getPublicConfig(), 200);
    },

    "create-order" => function () use ($service) {
        $data = leerJsonBody();
        $cart = $data["cart"] ?? [];
        if (!is_array($cart)) {
            api_responder(["error" => "Body inválido: cart debe ser array."], 400);
        }

        $res = $service->createOrderFromCart($cart);
        if (isset($res["error"])) {
            api_responder($res, 400);
        }
        api_responder($res, 200);
    },

    "capture-order" => function () use ($service, $pdo) {

        $data = leerJsonBody();
        $orderId   = (string)($data["orderId"] ?? "");
        $carrito   = $data["cart"] ?? [];
        $idUsuario = (int)($data["id_usuario"] ?? 0);

        if ($orderId === "") {
            api_responder(["error" => "Falta orderId"], 400);
        }

        if (!$idUsuario || empty($carrito) || !is_array($carrito)) {
            api_responder(["error" => "Datos incompletos"], 400);
        }

        // 1) Capturar pago en PayPal
        $res = $service->captureOrder($orderId);

        // Si no se completó, no guardamos nada
        if (!isset($res["status"]) || $res["status"] !== "COMPLETED") {
            api_responder([
                "error" => "El pago no fue completado",
                "paypal_status" => $res["status"] ?? "desconocido"
            ], 400);
        }

        // 2) Guardar pedido
        $pedidoService = (new PedidoServiceFactory())->create($pdo);
        $idPedido = $pedidoService->crearPedido($idUsuario, $carrito);

        // 3) Enviar factura por correo (no rompe la compra si falla)
        $emailSent = false;
        $emailError = null;

        try {
            $facturaService = (new FacturaServiceFactory())->create($pdo);
            $factura = $facturaService->buildFactura((int)$idPedido);

            if (isset($factura["error"])) {
                $emailError = $factura["error"];
            } else {
                $toEmail = (string)($factura["email"] ?? "");
                if ($toEmail !== "") {
                    $emailService = (new EmailServiceFactory())->create($pdo);
                    $respEmail = $emailService->sendHtml(
                        $toEmail,
                        (string)($factura["subject"] ?? ("Factura MegaSantiago - Pedido #" . $idPedido)),
                        (string)($factura["html"] ?? "")
                    );

                    $emailSent = (bool)($respEmail["ok"] ?? false);
                    if (!$emailSent) {
                        $emailError = (string)($respEmail["error"] ?? "No se pudo enviar el correo.");
                    }
                } else {
                    $emailError = "El usuario no tiene email registrado.";
                }
            }
        } catch (Throwable $e) {
            $emailSent = false;
            $emailError = $e->getMessage();
        }

        api_responder([
            "ok" => true,
            "status" => $res["status"] ?? "COMPLETED",
            "pedido_id" => $idPedido,
            "email_sent" => $emailSent,
            "email_error" => $emailError
        ], 200);
    },

];

if (!isset($routes[$accion])) {
    api_responder(["error" => "Acción no válida"], 400);
}

$routes[$accion]();
