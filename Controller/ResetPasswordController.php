<?php
ini_set('display_errors', '1');
ini_set('display_startup_errors', '1');
error_reporting(E_ALL);

header("Content-Type: application/json; charset=UTF-8");

try {
    $data = json_decode(file_get_contents("php://input"), true);

    $token = trim($data["token"] ?? "");
    $newPass = (string)($data["password"] ?? "");
    $confirm = (string)($data["confirm"] ?? "");

    if ($token === "" || $newPass === "" || $confirm === "") {
        echo json_encode(["ok" => false, "error" => "Datos incompletos."]);
        exit;
    }

    if (strlen($newPass) < 6) {
        echo json_encode(["ok" => false, "error" => "La contraseña debe tener al menos 6 caracteres."]);
        exit;
    }

    if ($newPass !== $confirm) {
        echo json_encode(["ok" => false, "error" => "Las contraseñas no coinciden."]);
        exit;
    }

    require_once __DIR__ . "/../Model/DB/DBConnection.php";
    require_once __DIR__ . "/../Model/DAO/PasswordResetDAO.php";

    $pdo = DBConnection::getInstance();
    $resetDAO = new PasswordResetDAO($pdo);

    // 1) token válido?
    $reset = $resetDAO->findValidByToken($token);
    if (!$reset) {
        echo json_encode(["ok" => false, "error" => "Enlace inválido o expirado."]);
        exit;
    }

    $email = $reset["email"];

    // 2) actualizar contraseña (bcrypt recomendado)
    $hash = password_hash($newPass, PASSWORD_BCRYPT);
    $stmt = $pdo->prepare("UPDATE usuarios SET clave_hash  = ? WHERE email = ? LIMIT 1");
    $stmt->execute([$hash, $email]);

    if ($stmt->rowCount() <= 0) {
        echo json_encode(["ok" => false, "error" => "No se pudo actualizar la contraseña."]);
        exit;
    }

    // 3) invalidar token (borrándolo)
    $resetDAO->deleteById((int)$reset["id"]);

    echo json_encode(["ok" => true, "msg" => "Contraseña actualizada. Ya puedes iniciar sesión."]);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode([
        "ok" => false,
        "error" => $e->getMessage(),
        "file" => $e->getFile(),
        "line" => $e->getLine()
    ]);
}
