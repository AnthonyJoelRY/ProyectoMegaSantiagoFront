<?php
// Controller/_helpers/Api.php
// Helpers mínimos para respuestas JSON consistentes en Controllers.

declare(strict_types=1);

function api_send_json_headers(bool $cors = true): void {
    if (!headers_sent()) {
        header("Content-Type: application/json; charset=utf-8");
        if ($cors) {
            header("Access-Control-Allow-Origin: *");
            header("Access-Control-Allow-Headers: Content-Type, Authorization");
            header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
        }
    }
}

function api_handle_options(): void {
    if (($_SERVER["REQUEST_METHOD"] ?? "") === "OPTIONS") {
        http_response_code(204);
        exit;
    }
}

function api_read_json_body(): array {
    $raw = file_get_contents("php://input");
    if (!$raw) return [];
    $data = json_decode($raw, true);
    return is_array($data) ? $data : [];
}

/**
 * Responde en JSON y termina el script.
 */
function api_responder(array $payload, int $code = 200): void {
    http_response_code($code);

    // En modo debug es útil ver el JSON legible.
    $debug = defined("APP_DEBUG") ? APP_DEBUG : false;
    $flags = JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES;
    if ($debug) $flags |= JSON_PRETTY_PRINT;

    echo json_encode($payload, $flags);
    exit;
}

/**
 * Helpers opcionales (no obligan estructura).
 */
function api_ok(array $data = [], int $code = 200): void {
    api_responder(["ok" => true] + $data, $code);
}

function api_error(string $message, int $code = 400, array $extra = []): void {
    api_responder(["ok" => false, "error" => $message] + $extra, $code);
}
