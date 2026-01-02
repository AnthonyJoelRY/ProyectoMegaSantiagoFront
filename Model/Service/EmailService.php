<?php
// Model/Service/EmailService.php

require_once __DIR__ . "/../Lib/SmtpClient.php";

class EmailService
{
    private array $cfg;

    public function __construct()
    {
        $cfgFile = __DIR__ . '/../Config/mail_credentials.php';
        if (!file_exists($cfgFile)) {
            // Si no existe, dejamos deshabilitado.
            $this->cfg = ["mail_enabled" => false];
            return;
        }

        // Carga variables $mail_*
        require $cfgFile;

        $this->cfg = [
            "enabled" => (bool)($mail_enabled ?? false),
            "host" => (string)($mail_host ?? ""),
            "username" => (string)($mail_username ?? ""),
            "password" => (string)($mail_password ?? ""),
            "port" => (int)($mail_port ?? 587),
            "secure" => (string)($mail_secure ?? "tls"),
            "from_email" => (string)($mail_from_email ?? ($mail_username ?? "")),
            "from_name" => (string)($mail_from_name ?? "MegaSantiago"),
        ];
    }

    public function isEnabled(): bool
    {
        return !empty($this->cfg["enabled"]);
    }

    /** Envia un correo HTML. Retorna ["ok"=>bool,"error"=>string|null] */
    public function sendHtml(string $toEmail, string $subject, string $html): array
    {
        if (!$this->isEnabled()) {
            return ["ok" => false, "error" => "mail_disabled"];
        }

        try {
            $smtp = new SmtpClient(
                $this->cfg["host"],
                (int)$this->cfg["port"],
                (string)$this->cfg["secure"]
            );
            $smtp->connect();
            $smtp->authLogin($this->cfg["username"], $this->cfg["password"]);
            $smtp->sendMail(
                $this->cfg["from_email"],
                $this->cfg["from_name"],
                $toEmail,
                $subject,
                $html
            );
            $smtp->quit();

            return ["ok" => true, "error" => null];
        } catch (Throwable $e) {
            return ["ok" => false, "error" => $e->getMessage()];
        }
    }
}
