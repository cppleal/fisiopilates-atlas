<?php
/**
 * Mailer SMTP ligero para Fisiopilates Atlas
 * Soporta STARTTLS en puerto 587
 * Fallback a mail() nativo si SMTP falla
 */

class SmtpMailer
{
    private string $host;
    private int $port;
    private string $user;
    private string $pass;
    private string $fromEmail;
    private string $fromName;

    public function __construct(
        string $host,
        int $port,
        string $user,
        string $pass,
        string $fromEmail,
        string $fromName = ''
    ) {
        $this->host = $host;
        $this->port = $port;
        $this->user = $user;
        $this->pass = $pass;
        $this->fromEmail = $fromEmail;
        $this->fromName = $fromName;
    }

    /**
     * Envía un email. Intenta SMTP autenticado, fallback a mail().
     */
    public function send(string $to, string $subject, string $bodyHtml, string $bcc = '', string $replyTo = ''): bool
    {
        $result = $this->sendSmtp($to, $subject, $bodyHtml, $bcc, $replyTo);
        if (!$result) {
            error_log("SMTP falló, intentando mail() nativo");
            $result = $this->sendNative($to, $subject, $bodyHtml, $bcc, $replyTo);
        }
        return $result;
    }

    private function sendSmtp(string $to, string $subject, string $bodyHtml, string $bcc, string $replyTo): bool
    {
        $socket = @stream_socket_client(
            "tcp://{$this->host}:{$this->port}",
            $errno,
            $errstr,
            10
        );

        if (!$socket) {
            error_log("SMTP connect error: $errstr ($errno)");
            return false;
        }

        try {
            $this->readResponse($socket);
            $this->sendCommand($socket, "EHLO " . gethostname());

            // STARTTLS
            $this->sendCommand($socket, "STARTTLS");
            stream_context_set_option($socket, 'ssl', 'verify_peer', false);
            stream_context_set_option($socket, 'ssl', 'verify_peer_name', false);
            stream_context_set_option($socket, 'ssl', 'allow_self_signed', true);
            if (!stream_socket_enable_crypto($socket, true, STREAM_CRYPTO_METHOD_TLSv1_2_CLIENT | STREAM_CRYPTO_METHOD_TLSv1_3_CLIENT)) {
                throw new Exception("STARTTLS crypto failed");
            }
            $this->sendCommand($socket, "EHLO " . gethostname());

            // AUTH LOGIN
            $this->sendCommand($socket, "AUTH LOGIN");
            $this->sendCommand($socket, base64_encode($this->user));
            $this->sendCommand($socket, base64_encode($this->pass));

            // Envelope
            $this->sendCommand($socket, "MAIL FROM:<{$this->fromEmail}>");
            $this->sendCommand($socket, "RCPT TO:<{$to}>");
            if ($bcc) {
                $this->sendCommand($socket, "RCPT TO:<{$bcc}>");
            }

            // DATA
            $this->sendCommand($socket, "DATA");

            $fromHeader = $this->fromName
                ? "=?UTF-8?B?" . base64_encode($this->fromName) . "?= <{$this->fromEmail}>"
                : $this->fromEmail;

            $msg = "From: {$fromHeader}\r\n";
            $msg .= "To: {$to}\r\n";
            if ($replyTo) {
                $msg .= "Reply-To: {$replyTo}\r\n";
            }
            if ($bcc) {
                $msg .= "Bcc: {$bcc}\r\n";
            }
            $msg .= "Subject: =?UTF-8?B?" . base64_encode($subject) . "?=\r\n";
            $msg .= "MIME-Version: 1.0\r\n";
            $msg .= "Content-Type: text/html; charset=UTF-8\r\n";
            $msg .= "Content-Transfer-Encoding: base64\r\n";
            $msg .= "Date: " . date('r') . "\r\n";
            $msg .= "Message-ID: <" . uniqid('atlas_', true) . "@fisiopilatesatlas.es>\r\n";
            $msg .= "\r\n";
            $msg .= chunk_split(base64_encode($bodyHtml));
            $msg .= "\r\n.\r\n";

            fwrite($socket, $msg);
            $this->readResponse($socket);
            $this->sendCommand($socket, "QUIT");

            return true;

        } catch (Exception $e) {
            error_log("SMTP error: " . $e->getMessage());
            return false;
        } finally {
            fclose($socket);
        }
    }

    private function sendNative(string $to, string $subject, string $bodyHtml, string $bcc, string $replyTo): bool
    {
        $fromHeader = $this->fromName
            ? "=?UTF-8?B?" . base64_encode($this->fromName) . "?= <{$this->fromEmail}>"
            : $this->fromEmail;

        $headers = "From: {$fromHeader}\r\n";
        $headers .= "Reply-To: " . ($replyTo ?: $this->fromEmail) . "\r\n";
        if ($bcc) {
            $headers .= "Bcc: {$bcc}\r\n";
        }
        $headers .= "MIME-Version: 1.0\r\n";
        $headers .= "Content-Type: text/html; charset=UTF-8\r\n";

        $subjectEncoded = "=?UTF-8?B?" . base64_encode($subject) . "?=";

        return @mail($to, $subjectEncoded, $bodyHtml, $headers);
    }

    private function sendCommand($socket, string $command): string
    {
        fwrite($socket, $command . "\r\n");
        return $this->readResponse($socket);
    }

    private function readResponse($socket): string
    {
        $response = '';
        stream_set_timeout($socket, 10);
        while ($line = fgets($socket, 512)) {
            $response .= $line;
            if (isset($line[3]) && $line[3] === ' ') break;
            if (strlen($line) < 4) break;
        }
        $code = (int)substr($response, 0, 3);
        if ($code >= 400) {
            throw new Exception("SMTP error $code: " . trim($response));
        }
        return $response;
    }
}
