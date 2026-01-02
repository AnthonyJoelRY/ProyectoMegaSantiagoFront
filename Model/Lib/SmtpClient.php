<?php
// Model/Lib/SmtpClient.php
// Cliente SMTP liviano (sin librerías externas) para hosting compartido.
// Soporta AUTH LOGIN y TLS (STARTTLS) / SSL.
// Nota: esto NO es un reemplazo completo de PHPMailer, pero sirve para envíos básicos.

class SmtpClient
{
    private $socket;
    private string $host;
    private int $port;
    private string $secure; // tls|ssl|none
    private int $timeout;

    public function __construct(string $host, int $port = 587, string $secure = "tls", int $timeout = 20)
    {
        $this->host = $host;
        $this->port = $port;
        $this->secure = strtolower($secure);
        $this->timeout = $timeout;
    }

    public function connect(): void
    {
        $remote = $this->host;
        if ($this->secure === "ssl") {
            $remote = "ssl://" . $this->host;
        }

        $this->socket = @fsockopen($remote, $this->port, $errno, $errstr, $this->timeout);
        if (!$this->socket) {
            throw new Exception("No se pudo conectar a SMTP: $errstr ($errno)");
        }

        stream_set_timeout($this->socket, $this->timeout);

        $this->expect(220);
        $this->send("EHLO localhost");
        $this->expect(250);

        if ($this->secure === "tls") {
            $this->send("STARTTLS");
            $this->expect(220);

            $cryptoOk = @stream_socket_enable_crypto($this->socket, true, STREAM_CRYPTO_METHOD_TLS_CLIENT);
            if ($cryptoOk !== true) {
                throw new Exception("No se pudo iniciar TLS (STARTTLS).");
            }

            // EHLO otra vez tras STARTTLS
            $this->send("EHLO localhost");
            $this->expect(250);
        }
    }

    public function authLogin(string $username, string $password): void
    {
        $this->send("AUTH LOGIN");
        $this->expect(334);

        $this->send(base64_encode($username));
        $this->expect(334);

        $this->send(base64_encode($password));
        $this->expect(235);
    }

    public function sendMail(string $fromEmail, string $fromName, string $toEmail, string $subject, string $htmlBody): void
    {
        $subjectEnc = "=?UTF-8?B?" . base64_encode($subject) . "?=";

        $headers = [];
        $headers[] = "MIME-Version: 1.0";
        $headers[] = "Content-Type: text/html; charset=UTF-8";
        $headers[] = "From: " . $this->formatAddress($fromEmail, $fromName);
        $headers[] = "To: " . $toEmail;
        $headers[] = "Subject: " . $subjectEnc;

        $message = implode("\r\n", $headers) . "\r\n\r\n" . $htmlBody;

        $this->send("MAIL FROM:<{$fromEmail}>");
        $this->expect(250);

        $this->send("RCPT TO:<{$toEmail}>");
        $this->expect([250, 251]);

        $this->send("DATA");
        $this->expect(354);

        // Dot-stuffing: líneas que empiezan con "." deben ser ".."
        $safeMessage = preg_replace('/\r\n\./', "\r\n..", $message);

        $this->write($safeMessage . "\r\n.");
        $this->expect(250);
    }

    public function quit(): void
    {
        if ($this->socket) {
            $this->send("QUIT");
            // no importa si falla
            @fclose($this->socket);
            $this->socket = null;
        }
    }

    private function formatAddress(string $email, string $name): string
    {
        $name = trim($name);
        if ($name === "") return $email;
        $nameEnc = "=?UTF-8?B?" . base64_encode($name) . "?=";
        return $nameEnc . " <{$email}>";
    }

    private function send(string $cmd): void
    {
        $this->write($cmd);
    }

    private function write(string $data): void
    {
        if (!$this->socket) throw new Exception("Socket SMTP no inicializado.");
        @fwrite($this->socket, $data . "\r\n");
    }

    private function readLine(): string
    {
        if (!$this->socket) return "";
        $line = "";
        while (($chunk = fgets($this->socket, 515)) !== false) {
            $line .= $chunk;
            // Respuesta multi-línea termina cuando el código va seguido de espacio
            if (preg_match('/^\d{3} /', $chunk)) break;
        }
        return $line;
    }

    private function expect($codes): void
    {
        $codes = is_array($codes) ? $codes : [$codes];
        $resp = $this->readLine();
        if ($resp === "") {
            throw new Exception("Respuesta SMTP vacía.");
        }

        $code = (int)substr($resp, 0, 3);
        if (!in_array($code, $codes, true)) {
            throw new Exception("SMTP error ($code): " . trim($resp));
        }
    }
}
