<?php
// Controller/_helpers/Bootstrap.php
// Inicialización común para endpoints JSON (CORS + errores + debug).
declare(strict_types=1);

require_once __DIR__ . "/Api.php";

// Debug por variable de entorno: APP_DEBUG=1
$__app_debug = (($_ENV["APP_DEBUG"] ?? getenv("APP_DEBUG") ?? "0") === "1");
if (!defined("APP_DEBUG")) {
    define("APP_DEBUG", $__app_debug);
}

error_reporting(E_ALL);
ini_set("display_errors", APP_DEBUG ? "1" : "0");
ini_set("display_startup_errors", APP_DEBUG ? "1" : "0");

// Headers + preflight
api_send_json_headers(true);
api_handle_options();

// Errores como JSON (sin mostrar detalles en prod)
set_exception_handler(function (Throwable $e): void {
    if (APP_DEBUG) {
        api_responder([
            "error" => "Exception",
            "message" => $e->getMessage(),
            "type" => get_class($e),
            "file" => $e->getFile(),
            "line" => $e->getLine(),
        ], 500);
    }
    api_responder(["error" => "Internal Server Error"], 500);
});

set_error_handler(function (int $severity, string $message, string $file, int $line): bool {
    // Convertir warnings/notices en excepción para que pase por el exception handler
    throw new ErrorException($message, 0, $severity, $file, $line);
});


// ✅ Capturar errores fatales (parse error, require missing, etc.) y responder JSON
register_shutdown_function(function (): void {
    $err = error_get_last();
    if (!$err) return;

    $fatalTypes = [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR, E_USER_ERROR];
    if (!in_array($err["type"] ?? 0, $fatalTypes, true)) return;

    // Si ya hubo salida, no hacemos nada
    if (headers_sent()) return;

    api_send_json_headers(true);
    http_response_code(500);

    if (APP_DEBUG) {
        echo json_encode([
            "error" => "FatalError",
            "message" => $err["message"] ?? "Fatal error",
            "file" => basename($err["file"] ?? ""),
            "line" => (int)($err["line"] ?? 0),
        ], JSON_UNESCAPED_UNICODE);
    } else {
        echo json_encode([
            "error" => "Fallo de inicialización",
        ], JSON_UNESCAPED_UNICODE);
    }
});

