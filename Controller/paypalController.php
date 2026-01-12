<?php
// Controller/paypalController.php

require_once __DIR__ . "/_helpers/Bootstrap.php";

/**
 * ✅ 1. EVITAR CORRUPCIÓN DE SALIDA
 */
ob_start();

$accion = $_GET["accion"] ?? "";

/**
 * ✅ 2. CONFIGURACIÓN PÚBLICA (SIN BD)
 */
function paypal_public_config_no_db(): array
{
    global $paypal_mode, $paypal_client_id, $paypal_client_secret, $paypal_currency;
    $path = __DIR__ . "/../Model/Config/paypal_credentials.php";
    if (!file_exists($path)) return ["error" => "No credentials file"];
    include $path;
    return [
        "mode"      => $paypal_mode ?? "sandbox",
        "clientId"  => $paypal_client_id ?? "",
        "currency"  => $paypal_currency ?? "USD",
        "hasSecret" => !empty($paypal_client_secret)
    ];
}



// ✅ Normaliza montos para PayPal (acepta $0.31, 0,31, etc.)
function normalizarAmount($raw): ?string
{
    if ($raw === null) return null;

    $s = is_string($raw) ? $raw : (string)$raw;
    // deja solo numeros, coma, punto y signo
    $s = preg_replace('/[^0-9\-\.,]/', '', $s);
    $s = str_replace(',', '.', $s);
    $s = trim($s);

    if ($s === '' || $s === '.' || $s === '-' ) return null;

    $n = floatval($s);
    if (!is_finite($n) || $n <= 0) return null;

    return number_format($n, 2, '.', '');
}


if ($accion === "config") {
    ob_clean();
    api_responder(paypal_public_config_no_db(), 200);
    exit;
}


// ✅ CREAR ORDEN (SIN BD)
// - Recibe { amount: "0.31", cart:[{precio, cantidad},...] }
// - No valida stock aquí (eso se hace al capturar/registrar pedido)
if ($accion === "create-order") {
    $data = json_decode(file_get_contents("php://input"), true) ?: [];

// ✅ Asegurar sesión para poder "recordar" el carrito entre create-order y capture-order
if (session_status() !== PHP_SESSION_ACTIVE) { session_start(); }

// Guardar carrito en sesión si viene en la petición (para fallback en capture-order)
if (isset($data["cart"])) {
    $_SESSION["paypal_cart"] = $data["cart"];
}

// ✅ Guardar info de checkout (entrega/dirección/sucursal) como fallback
// (en algunos hostings, el body JSON de capture-order puede llegar vacío).
if (isset($data["tipo_entrega"]) || isset($data["id_sucursal_retiro"]) || isset($data["id_sucursal_origen"]) || isset($data["id_direccion_envio"]) || isset($data["direccion_nueva"])) {
    $_SESSION["paypal_checkout"] = [
        "tipo_entrega" => $data["tipo_entrega"] ?? null,
        "id_sucursal_retiro" => $data["id_sucursal_retiro"] ?? null,
        "id_sucursal_origen" => $data["id_sucursal_origen"] ?? null,
        "id_direccion_envio" => $data["id_direccion_envio"] ?? null,
        "direccion_nueva" => $data["direccion_nueva"] ?? null,
    ];
}
$rawAmount = $data["amount"] ?? null;
    $amount = normalizarAmount($rawAmount);

    // ✅ Fallback: si no viene amount (o viene mal), lo calculamos desde el carrito
    if ($amount === null) {
        $cart = is_array($data["cart"] ?? null) ? $data["cart"] : [];
        $sum = 0.0;

        foreach ($cart as $it) {
            if (!is_array($it)) continue;

            // qty: cantidad | quantity | qty
            $qty = $it["cantidad"] ?? ($it["quantity"] ?? ($it["qty"] ?? 0));
            $qty = (int)$qty;
            if ($qty <= 0) $qty = 1;

            // price: precio_unit | precio | price | subtotal/qty
            $priceRaw = null;
            foreach (["precio_unit", "precio", "price"] as $k) {
                if (isset($it[$k]) && $it[$k] !== "") { $priceRaw = $it[$k]; break; }
            }
            if ($priceRaw === null && isset($it["subtotal"])) {
                $sub = normalizarAmount($it["subtotal"]);
                if ($sub !== null) {
                    $subF = (float)$sub;
                    $priceRaw = ($qty > 0) ? ($subF / $qty) : $subF;
                }
            }

            $price = normalizarAmount($priceRaw);
            if ($price === null) continue;

            $sum += ((float)$price) * $qty;
        }

        $amount = ($sum > 0) ? number_format($sum, 2, '.', '') : null;
    }

    if ($amount === null) {
        ob_clean();
        api_responder(["error" => "Monto inválido (amount)", "debug" => ["raw" => $rawAmount]], 400);
        exit;
    }

    // Cargar credenciales sin BD
    $path = __DIR__ . "/../Model/Config/paypal_credentials.php";
    if (!file_exists($path)) {
        ob_clean();
        api_responder(["error" => "No existe paypal_credentials.php"], 500);
        exit;
    }
    require $path;

    $baseUrl  = ($paypal_mode === "live") ? "https://api-m.paypal.com" : "https://api-m.sandbox.paypal.com";
    $currency = $paypal_currency ?: "USD";

    // 1) Access Token
    $ch = curl_init($baseUrl . "/v1/oauth2/token");
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => "grant_type=client_credentials",
        CURLOPT_HTTPHEADER => ["Accept: application/json", "Accept-Language: en_US"],
        CURLOPT_USERPWD => $paypal_client_id . ":" . $paypal_client_secret,
    ]);
    $tokenResp = curl_exec($ch);
    $tokenCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    $tokenJson = json_decode($tokenResp, true);
    if ($tokenCode < 200 || $tokenCode >= 300 || empty($tokenJson["access_token"])) {
        ob_clean();
        api_responder(["error" => "PayPal token error", "detalle" => $tokenResp], 500);
        exit;
    }

    // 2) Create Order
    $payload = [
        "intent" => "CAPTURE",
        "purchase_units" => [[
            "amount" => [
                "currency_code" => $currency,
                "value" => $amount
            ]
        ]]
    ];

    $ch = curl_init($baseUrl . "/v2/checkout/orders");
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => json_encode($payload),
        CURLOPT_HTTPHEADER => [
            "Content-Type: application/json",
            "Authorization: Bearer " . $tokenJson["access_token"]
        ],
    ]);
    $orderResp = curl_exec($ch);
    $orderCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    $orderJson = json_decode($orderResp, true);
    if ($orderCode < 200 || $orderCode >= 300 || empty($orderJson["id"])) {
        ob_clean();
        api_responder(["error" => "PayPal create order error", "detalle" => $orderResp], 500);
        exit;
    }

    ob_clean();
    api_responder(["id" => $orderJson["id"]], 200);
    exit;
}

// ============================================================
// ✅ CAPTURAR ORDEN (SERVER) + REGISTRAR PEDIDO + ENVIAR FACTURA
//
// Nota importante:
// - Este flujo se ejecuta DESPUÉS de que el usuario aprueba el pago en PayPal.
// - Aquí sí usamos BD (pedido, detalle, stock, factura, correo).
// - Se carga lo mínimo indispensable para no romper lo que ya funciona.
// ============================================================

/** Lee JSON body de forma segura */
function leerJsonBodySeguro(): array
{
    $raw = file_get_contents("php://input");
    $data = json_decode($raw ?: "", true);
    return is_array($data) ? $data : [];
}

// Fallback: si el front no manda id_usuario (hostings que vacían php://input), lo tomamos de la sesión
function obtenerIdUsuarioSesion(): int {
    if (session_status() !== PHP_SESSION_ACTIVE) {
        @session_start();
    }
    return (int)($_SESSION['id'] ?? $_SESSION['id_usuario'] ?? 0);
}


/** Normaliza carrito recibido del front para el PedidoService (id, cantidad) */
function normalizarCarritoPedido(array $carrito): array
{
    // Soporta envolturas tipo {items:[...]} o {carrito:[...]}
    if (isset($carrito["items"]) && is_array($carrito["items"])) {
        $carrito = $carrito["items"];
    } elseif (isset($carrito["carrito"]) && is_array($carrito["carrito"])) {
        $carrito = $carrito["carrito"];
    }

    $out = [];
    foreach ($carrito as $it) {
        if (!is_array($it)) continue;
        $id = (int)($it["id"] ?? ($it["id_producto"] ?? ($it["idProducto"] ?? 0)));
        // Soporta {cantidad} o {qty}
        $cantidad = (int)($it["cantidad"] ?? ($it["qty"] ?? ($it["cantidad_producto"] ?? ($it["quantity"] ?? 0))));
        if ($id > 0 && $cantidad > 0) {
            $out[] = ["id" => $id, "cantidad" => $cantidad];
        }
    }
    return $out;
}

/** Extrae status "COMPLETED" desde respuesta PayPal */
function paypalExtraerStatus(array $pp): string
{
    // Top-level
    $status = (string)($pp["status"] ?? "");
    if ($status) return $status;

    // Captures status
    $cap = $pp["purchase_units"][0]["payments"]["captures"][0]["status"] ?? null;
    if (is_string($cap) && $cap !== "") return $cap;

    return "";
}

// ✅ CAPTURE-ORDER (server-side cURL)
if ($accion === "capture-order") {
    try {
        require_once __DIR__ . "/../Model/DB/db.php";
        require_once __DIR__ . "/../Model/Service/PayPalService.php";
        require_once __DIR__ . "/../Model/Service/PedidoService.php";
        require_once __DIR__ . "/../Model/Service/FacturaService.php";
        require_once __DIR__ . "/../Model/Service/EmailService.php";

        $pdo = obtenerConexion();
        $payPal = new PayPalService($pdo);
        $pedidoService = new PedidoService($pdo);
        $facturaService = new FacturaService($pdo);
        $emailService = new EmailService();

        // ✅ Sesión para obtener id_usuario y carrito como fallback
        if (session_status() !== PHP_SESSION_ACTIVE) { session_start(); }
$data = leerJsonBodySeguro();

        // Fallbacks: algunos hostings devuelven php://input vacío.
        if (empty($data) && !empty($_POST)) {
            $data = $_POST;
        }
        // Permitir orderId por querystring como respaldo
        if (!isset($data["orderId"]) && isset($_GET["orderId"])) {
            $data["orderId"] = $_GET["orderId"];
        }
        // Compatibilidad: algunas integraciones usan orderID
        if (!isset($data["orderId"]) && isset($data["orderID"])) {
            $data["orderId"] = $data["orderID"];
        }


        $orderId = trim((string)($data["orderId"] ?? ""));
        $idUsuario = (int)($data["id_usuario"] ?? 0);
        if ($idUsuario <= 0) {
            $idUsuario = obtenerIdUsuarioSesion();
        }
        if ($idUsuario <= 0 && isset($_GET['id_usuario'])) {
            $idUsuario = (int)$_GET['id_usuario'];
        }

        // Cart puede venir como array (JSON) o como string (x-www-form-urlencoded)
        $carritoRaw = $data["cart"] ?? [];
        if (is_string($carritoRaw)) {
            $tmp = json_decode($carritoRaw, true);
            if (is_array($tmp)) $carritoRaw = $tmp;
        }
        if (!is_array($carritoRaw)) $carritoRaw = [];
        $carrito = normalizarCarritoPedido($carritoRaw);

        if ($orderId === "") {
            ob_clean();
            api_responder(["error" => "orderId inválido", "debug" => ["content_type" => ($_SERVER["CONTENT_TYPE"] ?? ""), "has_post" => !empty($_POST), "has_get" => isset($_GET["orderId"]) ]], 400);
            exit;
        }

        // ✅ Idempotencia: si este orderId ya fue procesado en esta sesión, no volver a capturar/registrar
        if (!empty($_SESSION["paypal_processed_orders"]) && is_array($_SESSION["paypal_processed_orders"])) {
            if (isset($_SESSION["paypal_processed_orders"]["$orderId"])) {
                $prev = $_SESSION["paypal_processed_orders"]["$orderId"];
                ob_clean();
                api_responder([
                    "ok" => true,
                    "paypal_status" => "COMPLETED",
                    "status" => "COMPLETED",
                    "orderId" => $orderId,
                    "id_pedido" => (int)($prev["id_pedido"] ?? 0),
                    "note" => "Orden ya procesada (idempotente)",
                ], 200);
                exit;
            }
        }
        if ($idUsuario <= 0) {
            ob_clean();
            api_responder(["error" => "Usuario inválido"], 400);
            exit;
        }
        // ✅ Fallback: si el frontend no manda cart en capture-order, lo tomamos de sesión (guardado en create-order)
        if (empty($carrito) && isset($_SESSION["paypal_cart"])) {
            $carritoRaw2 = $_SESSION["paypal_cart"];
            if (is_string($carritoRaw2)) {
                $tmp2 = json_decode($carritoRaw2, true);
                if (is_array($tmp2)) $carritoRaw2 = $tmp2;
            }
            if (!is_array($carritoRaw2)) $carritoRaw2 = [];
            $carrito = normalizarCarritoPedido($carritoRaw2);
        }

        if (empty($carrito)) {
            ob_clean();
            api_responder(["error" => "Carrito vacío"], 400);
            exit;
        }
        // 1) Captura en PayPal
        $pp = $payPal->captureOrder($orderId);
        if (isset($pp["error"])) {
            // ✅ Fallback: si la orden ya fue capturada, consultamos el estado y seguimos
            $detalle = $pp["detalle"] ?? null;
            $issue = "";
            if (is_array($detalle) && isset($detalle["details"][0]["issue"])) {
                $issue = (string)$detalle["details"][0]["issue"];
            }
            if ($issue === "ORDER_ALREADY_CAPTURED") {
                $pp2 = $payPal->getOrder($orderId);
                if (!isset($pp2["error"])) {
                    $pp = $pp2;
                } else {
                    ob_clean();
                    api_responder(["error" => $pp2["error"], "detalle" => $pp2["detalle"] ?? null], 400);
                    exit;
                }
            } else {
                ob_clean();
                api_responder(["error" => $pp["error"], "detalle" => $pp["detalle"] ?? null], 400);
                exit;
            }
        }

        $status = paypalExtraerStatus($pp);
        if ($status !== "COMPLETED") {
            ob_clean();
            api_responder([
                "ok" => false,
                "paypal_status" => $status,
                "status" => $status,
                "paypal" => $pp,
            ], 200);
            exit;
        }

        // 2) Registrar pedido
        $checkout = [
            "tipo_entrega" => $data["tipo_entrega"] ?? "envio",
            "id_sucursal_retiro" => $data["id_sucursal_retiro"] ?? null,
            "id_sucursal_origen" => $data["id_sucursal_origen"] ?? null,
            "id_direccion_envio" => $data["id_direccion_envio"] ?? null,
            "direccion_nueva" => $data["direccion_nueva"] ?? null,
            // ✅ Pago confirmado en PayPal
            "estado" => "pagado",
        ];

        // ✅ Fallback: si el hosting entregó body vacío o sin datos de entrega,
        // usar lo “recordado” en create-order (guardado en sesión).
        if (
            (empty($data) || !isset($data["tipo_entrega"]))
            && isset($_SESSION["paypal_checkout"]) && is_array($_SESSION["paypal_checkout"])
        ) {
            $saved = $_SESSION["paypal_checkout"];
            foreach (["tipo_entrega","id_sucursal_retiro","id_sucursal_origen","id_direccion_envio","direccion_nueva"] as $k) {
                if ((!
                        array_key_exists($k, $checkout) ||
                        $checkout[$k] === null ||
                        $checkout[$k] === ""
                    )
                    && array_key_exists($k, $saved)
                ) {
                    $checkout[$k] = $saved[$k];
                }
            }
        }

        // ✅ Si al registrar el pedido se detecta stock insuficiente (race condition),
        // devolvemos un mensaje claro para el frontend.
        try {
            $idPedido = $pedidoService->crearPedidoCheckout($idUsuario, $carrito, $checkout);
        } catch (Throwable $e) {
            $msg = (string)$e->getMessage();
            if (stripos($msg, 'Stock insuficiente') !== false) {
                ob_clean();
                api_responder([
                    "ok" => false,
                    "error" => "Sobrepasa lo que hay existencia",
                    "detalle" => $msg,
                ], 409);
                exit;
            }
            if (stripos($msg, 'Solo se permiten direcciones dentro de Loja') !== false) {
                ob_clean();
                api_responder([
                    "ok" => false,
                    "error" => "Solo se permiten direcciones dentro de Loja",
                    "detalle" => $msg,
                ], 400);
                exit;
            }
            throw $e;
        }

        // ✅ Marcar como procesado en sesión para evitar dobles registros si el front reintenta
        if (!isset($_SESSION["paypal_processed_orders"]) || !is_array($_SESSION["paypal_processed_orders"])) {
            $_SESSION["paypal_processed_orders"] = [];
        }
        $_SESSION["paypal_processed_orders"]["$orderId"] = [
            "id_pedido" => (int)$idPedido,
            "ts" => time(),
        ];

        // ✅ limpiar carrito cacheado del checkout (evita "Carrito vacío"/reintentos raros)
        unset($_SESSION["paypal_cart"]);

        // 3) Factura + correo (si está habilitado)
        $emailSent = false;
        $emailError = null;

        $fact = $facturaService->buildFactura((int)$idPedido);
        if (!isset($fact["error"])) {
            $to = (string)($fact["email"] ?? "");
            $subject = (string)($fact["subject"] ?? "Factura MegaSantiago");
            $html = (string)($fact["html"] ?? "");

            if ($to && $html) {
                $send = $emailService->sendHtml($to, $subject, $html);
                $emailSent = (bool)($send["ok"] ?? false);
                $emailError = $send["error"] ?? null;
            } else {
                $emailError = "email o html vacío";
            }
        } else {
            $emailError = (string)($fact["error"] ?? "No se pudo generar factura");
        }

        ob_clean();
        api_responder([
            "ok" => true,
            "paypal_status" => $status,
            "status" => $status,
            "orderId" => $orderId,
            "id_pedido" => (int)$idPedido,
            "email_sent" => $emailSent,
            "email_error" => $emailSent ? null : $emailError,
        ], 200);
        exit;

    } catch (Throwable $e) {
        ob_clean();
        api_responder([
            "error" => "Fallo de inicialización",
            "detalle" => $e->getMessage(),
            "archivo" => basename($e->getFile()),
            "linea" => $e->getLine(),
        ], 500);
        exit;
    }
}

// ✅ REGISTRAR-PEDIDO (CLIENT mode: el front ya capturó con actions.order.capture())
if ($accion === "registrar-pedido") {
    try {
        require_once __DIR__ . "/../Model/DB/db.php";
        require_once __DIR__ . "/../Model/Service/PedidoService.php";
        require_once __DIR__ . "/../Model/Service/FacturaService.php";
        require_once __DIR__ . "/../Model/Service/EmailService.php";

        $pdo = obtenerConexion();
        $pedidoService = new PedidoService($pdo);
        $facturaService = new FacturaService($pdo);
        $emailService = new EmailService();

        $data = leerJsonBodySeguro();

        // Fallbacks: algunos hostings devuelven php://input vacío.
        if (empty($data) && !empty($_POST)) {
            $data = $_POST;
        }
        // Permitir orderId por querystring como respaldo
        if (!isset($data["orderId"]) && isset($_GET["orderId"])) {
            $data["orderId"] = $_GET["orderId"];
        }
        // Compatibilidad: algunas integraciones usan orderID
        if (!isset($data["orderId"]) && isset($data["orderID"])) {
            $data["orderId"] = $data["orderID"];
        }


        $idUsuario = (int)($data["id_usuario"] ?? 0);
        if ($idUsuario <= 0) {
            $idUsuario = obtenerIdUsuarioSesion();
        }
        if ($idUsuario <= 0 && isset($_GET['id_usuario'])) {
            $idUsuario = (int)$_GET['id_usuario'];
        }

        $orderId = trim((string)($data["orderId"] ?? ""));
        $pp = is_array($data["paypal"] ?? null) ? $data["paypal"] : [];
        $status = paypalExtraerStatus($pp);

        // Cart puede venir como array (JSON) o como string (x-www-form-urlencoded)
        $carritoRaw = $data["cart"] ?? [];
        if (is_string($carritoRaw)) {
            $tmp = json_decode($carritoRaw, true);
            if (is_array($tmp)) $carritoRaw = $tmp;
        }
        if (!is_array($carritoRaw)) $carritoRaw = [];
        $carrito = normalizarCarritoPedido($carritoRaw);

        if ($idUsuario <= 0) {
            ob_clean();
            api_responder(["error" => "Usuario inválido"], 400);
            exit;
        }
        if (empty($carrito)) {
            ob_clean();
            api_responder(["error" => "Carrito vacío"], 400);
            exit;
        }
        if ($status !== "COMPLETED") {
            ob_clean();
            api_responder([
                "ok" => false,
                "paypal_status" => $status,
                "status" => $status,
                "orderId" => $orderId,
            ], 200);
            exit;
        }

        $checkout = [
            "tipo_entrega" => $data["tipo_entrega"] ?? "envio",
            "id_sucursal_retiro" => $data["id_sucursal_retiro"] ?? null,
            "id_sucursal_origen" => $data["id_sucursal_origen"] ?? null,
            "id_direccion_envio" => $data["id_direccion_envio"] ?? null,
            "direccion_nueva" => $data["direccion_nueva"] ?? null,
        ];

        // ✅ Si al registrar el pedido se detecta stock insuficiente (race condition),
        // devolvemos un mensaje claro para el frontend.
        try {
            $idPedido = $pedidoService->crearPedidoCheckout($idUsuario, $carrito, $checkout);
        } catch (Throwable $e) {
            $msg = (string)$e->getMessage();
            if (stripos($msg, 'Stock insuficiente') !== false) {
                ob_clean();
                api_responder([
                    "ok" => false,
                    "error" => "Sobrepasa lo que hay existencia",
                    "detalle" => $msg,
                ], 409);
                exit;
            }
            if (stripos($msg, 'Solo se permiten direcciones dentro de Loja') !== false) {
                ob_clean();
                api_responder([
                    "ok" => false,
                    "error" => "Solo se permiten direcciones dentro de Loja",
                    "detalle" => $msg,
                ], 400);
                exit;
            }
            throw $e;
        }

        $emailSent = false;
        $emailError = null;
        $fact = $facturaService->buildFactura((int)$idPedido);
        if (!isset($fact["error"])) {
            $to = (string)($fact["email"] ?? "");
            $subject = (string)($fact["subject"] ?? "Factura MegaSantiago");
            $html = (string)($fact["html"] ?? "");
            if ($to && $html) {
                $send = $emailService->sendHtml($to, $subject, $html);
                $emailSent = (bool)($send["ok"] ?? false);
                $emailError = $send["error"] ?? null;
            } else {
                $emailError = "email o html vacío";
            }
        } else {
            $emailError = (string)($fact["error"] ?? "No se pudo generar factura");
        }

        ob_clean();
        api_responder([
            "ok" => true,
            "paypal_status" => $status,
            "status" => $status,
            "orderId" => $orderId,
            "id_pedido" => (int)$idPedido,
            "email_sent" => $emailSent,
            "email_error" => $emailSent ? null : $emailError,
        ], 200);
        exit;

    } catch (Throwable $e) {
        ob_clean();
        api_responder([
            "error" => "Fallo de inicialización",
            "detalle" => $e->getMessage(),
            "archivo" => basename($e->getFile()),
            "linea" => $e->getLine(),
        ], 500);
        exit;
    }
}

// Si llega aquí es porque la acción no existe
ob_clean();
api_responder(["error" => "Acción no válida"], 400);