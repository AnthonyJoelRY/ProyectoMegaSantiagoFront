<?php  
ini_set('display_errors', '1');
ini_set('display_startup_errors', '1');
error_reporting(E_ALL);

header("Content-Type: application/json; charset=UTF-8");
try {
$data = json_decode(file_get_contents("php://input"), true);
$email = trim($data["email"] ?? "");

if ($email === "") {
    echo json_encode(["ok" => false, "error" => "Correo requerido"]);
    exit;
}

require_once __DIR__ . "/../Model/DB/DBConnection.php";
require_once __DIR__ . "/../Model/DAO/PasswordResetDAO.php";
require_once __DIR__ . "/../Model/Service/EmailService.php";

$pdo = DBConnection::getInstance();

/*
  Seguridad: NO revelar si el correo existe.
  Responder "ok:true" siempre, pero solo enviar si existe.
*/
$stmt = $pdo->prepare("
    SELECT id_usuario, nombre, apellido, email
    FROM usuarios
    WHERE email = ?
    LIMIT 1
");
$stmt->execute([$email]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    echo json_encode(["ok" => true]);
    exit;
}

// limpiar expirados (opcional)
$resetDAO = new PasswordResetDAO($pdo);
$resetDAO->cleanupExpired();

// generar token + expira 1h
$token = bin2hex(random_bytes(32));
$expiresAt = date("Y-m-d H:i:s", strtotime("+1 hour"));


// borrar tokens previos de ese email y guardar nuevo
$resetDAO->deleteByEmail($email);
$resetDAO->create($email, $token, $expiresAt);


// URL base (pon tu dominio real)
$baseUrl = "https://mspapeleriaempresa.rf.gd";
$link = $baseUrl . "/View/pages/nueva_clave.html?token=" . urlencode($token);

// enviar correo con tu EmailService
$mail = new EmailService();

$subject = "Recuperar contraseña - MegaSantiago";
$nombreCompleto = trim(($user["nombre"] ?? "") . " " . ($user["apellido"] ?? ""));
if ($nombreCompleto === "") $nombreCompleto = "usuario";

$html = "
  <div style='font-family:Arial,sans-serif;line-height:1.45'>
    <h2>Recuperación de contraseña</h2>
    <p>Hola <b>".htmlspecialchars($nombreCompleto)."</b>,</p>
    <p>Recibimos una solicitud para restablecer tu contraseña.</p>
    <p>
      <a href='{$link}' style='display:inline-block;padding:12px 18px;background:#f1c40f;color:#000;text-decoration:none;border-radius:6px;font-weight:700'>
        Crear nueva contraseña
      </a>
    </p>
    <p>Este enlace es válido por 1 hora.</p>
    <p>Si no realizaste esta solicitud, ignora este mensaje.</p>
  </div>
";

$res = $mail->sendHtml($user["email"], $subject, $html);

// si el mail está deshabilitado o falla, igual devolvemos ok? (tú decides)
if (!$res["ok"]) {
    // útil para debug:
    echo json_encode(["ok" => false, "error" => $res["error"]]);
    exit;
}

echo json_encode(["ok" => true]);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode([
        "ok" => false,
        "error" => $e->getMessage(),
        "file" => $e->getFile(),
        "line" => $e->getLine()
    ]);
    exit;
}