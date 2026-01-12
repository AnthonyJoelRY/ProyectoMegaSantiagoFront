<?php
header("Content-Type: application/json; charset=UTF-8");

$data = json_decode(file_get_contents("php://input"), true);
$token = trim($data["token"] ?? "");
$clave = (string)($data["clave"] ?? "");
$clave2 = (string)($data["clave2"] ?? "");

if ($token === "" || $clave === "" || $clave2 === "") {
    echo json_encode(["ok" => false, "error" => "Faltan datos."]);
    exit;
}

if ($clave !== $clave2) {
    echo json_encode(["ok" => false, "error" => "Las contraseñas no coinciden."]);
    exit;
}

if (strlen($clave) < 6) {
    echo json_encode(["ok" => false, "error" => "La contraseña debe tener al menos 6 caracteres."]);
    exit;
}

require_once __DIR__ . "/../Model/DB/DBConnection.php";
require_once __DIR__ . "/../Model/DAO/PasswordResetDAO.php";

$pdo = DBConnection::getInstance();
$resetDAO = new PasswordResetDAO($pdo);

// validar token y expiración
$reset = $resetDAO->findValidByToken($token);
if (!$reset) {
  echo json_encode(["ok" => false, "error" => "Token inválido o expirado."]);
  exit;
}

$email = $reset["email"];
$hash = password_hash($clave, PASSWORD_DEFAULT);

try {
  $pdo->beginTransaction();

  // ✅ Actualizar contraseña por email
  $stmt = $pdo->prepare("UPDATE usuarios SET clave_hash = ? WHERE email = ? LIMIT 1");
  $stmt->execute([$hash, $email]);

  // invalidar token
  $resetDAO->deleteByToken($token);

  $pdo->commit();
  echo json_encode(["ok" => true]);
} catch (Throwable $e) {
  if ($pdo->inTransaction()) $pdo->rollBack();
  http_response_code(500);
  echo json_encode(["ok" => false, "error" => "No se pudo actualizar la contraseña."]);
}
