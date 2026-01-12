<?php
// Model/Service/PayPalService.php

// ✅ Cargar credenciales PayPal (compatible con hosting Linux y tu estructura actual)
$cred1 = __DIR__ . "/../Config/paypal_credentials.php";     // htdocs/Model/Config/paypal_credentials.php ✅
$cred2 = __DIR__ . "/../../Config/paypal_credentials.php";  // htdocs/Config/paypal_credentials.php (fallback)

if (file_exists($cred1)) {
    require_once $cred1;
} elseif (file_exists($cred2)) {
    require_once $cred2;
} else {
    throw new Exception("No se encontró paypal_credentials.php. Busqué en: $cred1 y $cred2");
}

require_once __DIR__ . "/../DAO/ProductoDAO.php";

class PayPalService
{
    private string $baseUrl;
    private string $clientId;
    private string $clientSecret;
    private string $currency;

    public function __construct(private PDO $pdo)
    {
        global $paypal_mode, $paypal_client_id, $paypal_client_secret, $paypal_currency;

        $this->clientId = $paypal_client_id;
        $this->clientSecret = $paypal_client_secret;
        $this->currency = $paypal_currency ?: "USD";

        $this->baseUrl = ($paypal_mode === "live")
            ? "https://api-m.paypal.com"
            : "https://api-m.sandbox.paypal.com";
    }

    public function getPublicConfig(): array
    {
        return [
            "clientId" => $this->clientId,
            "currency" => $this->currency
        ];
    }

    public function createOrderFromCart(array $cartItems): array
    {
        $total = $this->calcularTotalDesdeBD($cartItems);
        if ($total <= 0) return ["error" => "El carrito está vacío o el total es inválido."];

        $accessToken = $this->getAccessToken();
        if (isset($accessToken["error"])) return $accessToken;

        $payload = [
            "intent" => "CAPTURE",
            "purchase_units" => [[
                "amount" => [
                    "currency_code" => $this->currency,
                    "value" => number_format($total, 2, ".", "")
                ]
            ]]
        ];

        $res = $this->curlJson("POST", $this->baseUrl . "/v2/checkout/orders", $accessToken["access_token"], $payload);
        if (isset($res["error"])) return $res;

        if (!isset($res["id"])) return ["error" => "No se pudo crear la orden en PayPal.", "detalle" => $res];
        return ["id" => $res["id"]];
    }

    public function captureOrder(string $orderId): array
    {
        $orderId = trim($orderId);
        if ($orderId === "") return ["error" => "orderId inválido."];

        $accessToken = $this->getAccessToken();
        if (isset($accessToken["error"])) return $accessToken;

        $url = $this->baseUrl . "/v2/checkout/orders/" . rawurlencode($orderId) . "/capture";
        $res = $this->curlJson("POST", $url, $accessToken["access_token"], new stdClass());
        if (isset($res["error"])) return $res;

        return $res;
    }

    /**
     * Obtiene el detalle/estado de una orden.
     * Útil como fallback cuando PayPal responde ORDER_ALREADY_CAPTURED.
     */
    public function getOrder(string $orderId): array
    {
        $orderId = trim($orderId);
        if ($orderId === "") return ["error" => "orderId inválido."];

        $accessToken = $this->getAccessToken();
        if (isset($accessToken["error"])) return $accessToken;

        $url = $this->baseUrl . "/v2/checkout/orders/" . rawurlencode($orderId);
        $res = $this->curlJson("GET", $url, $accessToken["access_token"], null);
        if (isset($res["error"])) return $res;

        return $res;
    }

    // ==========================
    // Helpers internos
    // ==========================
    private function calcularTotalDesdeBD(array $cartItems): float
    {
        $ids = [];
        $qtyById = [];

        foreach ($cartItems as $it) {
            // ✅ soportar nombres de campos alternativos (compatibilidad con distintos formatos de carrito)
            $id = (int)($it["id"] ?? $it["id_producto"] ?? $it["idProducto"] ?? 0);
            $qty = (int)($it["cantidad"] ?? $it["qty"] ?? $it["quantity"] ?? 0);
            if ($id > 0 && $qty > 0) {
                $ids[] = $id;
                $qtyById[$id] = ($qtyById[$id] ?? 0) + $qty;
            }
        }
        $ids = array_values(array_unique($ids));
        if (count($ids) === 0) return 0.0;

        $dao = new ProductoDAO($this->pdo);
        $productos = $dao->obtenerPorIds($ids);

        $total = 0.0;
        foreach ($productos as $p) {
            $id = (int)$p["id"];
            $precio = (float)($p["precio_oferta"] ?? 0);
            if ($precio <= 0) $precio = (float)($p["precio"] ?? 0);

            $qty = (int)($qtyById[$id] ?? 0);
            if ($qty > 0 && $precio > 0) {
                $total += $precio * $qty;
            }
        }
        return (float)$total;
    }

    private function getAccessToken(): array
    {
        if ($this->clientId === "" || $this->clientSecret === "" ||
            $this->clientId === "PAYPAL_SANDBOX_CLIENT_ID" || $this->clientSecret === "PAYPAL_SANDBOX_CLIENT_SECRET") {
            return ["error" => "Configura PAYPAL_SANDBOX_CLIENT_ID y PAYPAL_SANDBOX_CLIENT_SECRET en Model/Config/paypal_credentials.php"];
        }

        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $this->baseUrl . "/v1/oauth2/token",
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_USERPWD => $this->clientId . ":" . $this->clientSecret,
            CURLOPT_HTTPHEADER => ["Accept: application/json", "Accept-Language: en_US"],
            CURLOPT_POSTFIELDS => "grant_type=client_credentials",
            CURLOPT_TIMEOUT => 30
        ]);
        $raw = curl_exec($ch);
        $err = curl_error($ch);
        $code = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($raw === false) return ["error" => "Error cURL al obtener token.", "detalle" => $err];

        $data = json_decode($raw, true);
        if ($code < 200 || $code >= 300) {
            return ["error" => "PayPal token error", "http_code" => $code, "detalle" => $data ?: $raw];
        }
        return $data ?: ["error" => "Respuesta inválida de PayPal al obtener token.", "raw" => $raw];
    }

    private function curlJson(string $method, string $url, string $accessToken, $payload): array
    {
        $ch = curl_init();
        $method = strtoupper(trim($method));

        $headers = [
            "Accept: application/json",
            "Authorization: Bearer " . $accessToken,
        ];

        // En GET no debemos mandar body (algunos hostings/APIs se ponen quisquillosos)
        $options = [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CUSTOMREQUEST => $method,
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_TIMEOUT => 30,
        ];

        if ($method !== "GET") {
            $headers[] = "Content-Type: application/json";
            $options[CURLOPT_HTTPHEADER] = $headers;
            $options[CURLOPT_POSTFIELDS] = json_encode($payload, JSON_UNESCAPED_UNICODE);
        }

        curl_setopt_array($ch, $options);

        $raw = curl_exec($ch);
        $err = curl_error($ch);
        $code = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($raw === false) return ["error" => "Error cURL.", "detalle" => $err];

        $data = json_decode($raw, true);

        if ($code < 200 || $code >= 300) {
            return ["error" => "PayPal API error", "http_code" => $code, "detalle" => $data ?: $raw];
        }

        return $data ?: ["error" => "Respuesta inválida de PayPal.", "raw" => $raw];
    }
}
