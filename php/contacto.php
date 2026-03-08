<?php
/**
 * Endpoint de formulario de contacto - Fisiopilates Atlas
 * Recibe datos POST, verifica hCaptcha, guarda en BD y envía email.
 */

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/lib/SmtpMailer.php';

// Solo POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: /contacto');
    exit;
}

// Honeypot anti-spam
if (!empty($_POST['website'])) {
    header('Location: /contacto?status=ok');
    exit;
}

// Verificar hCaptcha
$hcaptchaResponse = $_POST['h-captcha-response'] ?? '';
if (empty($hcaptchaResponse)) {
    header('Location: /contacto?status=captcha');
    exit;
}

$verifyData = http_build_query([
    'secret'   => HCAPTCHA_SECRET,
    'response' => $hcaptchaResponse,
    'remoteip' => $_SERVER['REMOTE_ADDR'] ?? '',
]);

$verifyCtx = stream_context_create([
    'http' => [
        'method'  => 'POST',
        'header'  => 'Content-Type: application/x-www-form-urlencoded',
        'content' => $verifyData,
        'timeout' => 10,
    ],
]);

$verifyResult = @file_get_contents('https://api.hcaptcha.com/siteverify', false, $verifyCtx);
$captchaOk = false;
if ($verifyResult) {
    $decoded = json_decode($verifyResult, true);
    $captchaOk = !empty($decoded['success']);
}

if (!$captchaOk) {
    header('Location: /contacto?status=captcha');
    exit;
}

// Validar campos
$nombre   = trim($_POST['nombre'] ?? '');
$email    = trim($_POST['email'] ?? '');
$telefono = trim($_POST['telefono'] ?? '');
$motivo   = trim($_POST['motivo'] ?? '');
$mensaje  = trim($_POST['mensaje'] ?? '');
$copia    = !empty($_POST['copia']);

if (empty($nombre) || empty($email) || empty($motivo) || empty($mensaje)) {
    header('Location: /contacto?status=error');
    exit;
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    header('Location: /contacto?status=error');
    exit;
}

// Sanitizar para BD
$nombreSafe   = htmlspecialchars($nombre, ENT_QUOTES, 'UTF-8');
$telefonoSafe = htmlspecialchars($telefono, ENT_QUOTES, 'UTF-8');
$motivoSafe   = htmlspecialchars($motivo, ENT_QUOTES, 'UTF-8');
$mensajeSafe  = htmlspecialchars($mensaje, ENT_QUOTES, 'UTF-8');

$ip = $_SERVER['REMOTE_ADDR'] ?? '';

// Motivos legibles
$motivos = [
    'fisioterapia' => 'Cita de fisioterapia',
    'pilates'      => 'Información sobre Pilates',
    'precios'      => 'Consulta de precios',
    'seguros'      => 'Información sobre seguros / mutuas',
    'otro'         => 'Otro motivo',
];
$motivoTexto = $motivos[$motivo] ?? $motivo;

try {
    // Guardar en BD
    $pdo = getDB();
    $stmt = $pdo->prepare("
        INSERT INTO contacto (nombre, email, telefono, motivo, mensaje, ip)
        VALUES (?, ?, ?, ?, ?, ?)
    ");
    $stmt->execute([$nombreSafe, $email, $telefonoSafe, $motivoSafe, $mensajeSafe, $ip]);

    // Enviar email de notificación
    $mailer = new SmtpMailer(
        SMTP_HOST,
        SMTP_PORT,
        SMTP_USER,
        SMTP_PASS,
        SMTP_FROM,
        SMTP_FROM_NAME
    );

    $isTest = IS_TEST_ENV;
    $adminUrl = $isTest
        ? 'https://40749769.servicio-online.net/admin/'
        : 'https://fisiopilatesatlas.es/admin/';

    $testBanner = $isTest
        ? "<div style='background:#b45309;color:white;padding:10px 30px;border-radius:12px 12px 0 0;text-align:center;font-size:13px;font-weight:bold;letter-spacing:1px;'>⚠ ENTORNO DE TEST ⚠</div>"
        : "";

    $subjectPrefix = $isTest ? '[TEST] ' : '';

    $htmlBody = "
    <div style='font-family:Arial,sans-serif;max-width:600px;margin:0 auto;'>
        {$testBanner}
        <div style='background:#1B6B6E;color:white;padding:20px 30px;" . ($isTest ? "" : "border-radius:12px 12px 0 0;") . "'>
            <h2 style='margin:0;font-size:18px;'>Nuevo mensaje de contacto</h2>
            <p style='margin:5px 0 0;font-size:14px;opacity:0.8;'>Fisiopilates Atlas</p>
        </div>
        <div style='background:#ffffff;padding:30px;border:1px solid #d1e7e7;border-top:none;border-radius:0 0 12px 12px;'>
            <table style='width:100%;border-collapse:collapse;'>
                <tr>
                    <td style='padding:8px 0;font-weight:bold;color:#334155;width:120px;vertical-align:top;'>Nombre:</td>
                    <td style='padding:8px 0;color:#1e293b;'>" . htmlspecialchars($nombre, ENT_QUOTES, 'UTF-8') . "</td>
                </tr>
                <tr>
                    <td style='padding:8px 0;font-weight:bold;color:#334155;vertical-align:top;'>Email:</td>
                    <td style='padding:8px 0;'><a href='mailto:" . htmlspecialchars($email, ENT_QUOTES, 'UTF-8') . "' style='color:#1B6B6E;'>" . htmlspecialchars($email, ENT_QUOTES, 'UTF-8') . "</a></td>
                </tr>
                " . ($telefono ? "<tr>
                    <td style='padding:8px 0;font-weight:bold;color:#334155;vertical-align:top;'>Teléfono:</td>
                    <td style='padding:8px 0;color:#1e293b;'><a href='tel:" . htmlspecialchars($telefono, ENT_QUOTES, 'UTF-8') . "' style='color:#1B6B6E;'>" . htmlspecialchars($telefono, ENT_QUOTES, 'UTF-8') . "</a></td>
                </tr>" : "") . "
                <tr>
                    <td style='padding:8px 0;font-weight:bold;color:#334155;vertical-align:top;'>Motivo:</td>
                    <td style='padding:8px 0;color:#1e293b;'>" . htmlspecialchars($motivoTexto, ENT_QUOTES, 'UTF-8') . "</td>
                </tr>
                <tr>
                    <td style='padding:8px 0;font-weight:bold;color:#334155;vertical-align:top;'>Mensaje:</td>
                    <td style='padding:8px 0;color:#1e293b;white-space:pre-wrap;'>" . htmlspecialchars($mensaje, ENT_QUOTES, 'UTF-8') . "</td>
                </tr>
                <tr>
                    <td style='padding:8px 0;font-weight:bold;color:#334155;vertical-align:top;'>IP:</td>
                    <td style='padding:8px 0;color:#94a3b8;font-size:13px;'>{$ip}</td>
                </tr>
                <tr>
                    <td style='padding:8px 0;font-weight:bold;color:#334155;vertical-align:top;'>Fecha:</td>
                    <td style='padding:8px 0;color:#94a3b8;font-size:13px;'>" . date('d/m/Y H:i:s') . "</td>
                </tr>
            </table>
        </div>
        <p style='text-align:center;color:#94a3b8;font-size:12px;margin-top:15px;'>
            Fisiopilates Atlas &mdash; Panel de administración: <a href='{$adminUrl}' style='color:#1B6B6E;'>admin</a>
        </p>
    </div>";

    $mailer->send(
        CONTACT_TO,
        $subjectPrefix . 'Nueva consulta: ' . $motivoTexto . ' — ' . $nombreSafe,
        $htmlBody,
        CONTACT_BCC,
        CONTACT_REPLY_TO
    );

    // Enviar copia al usuario si lo ha solicitado
    if ($copia) {
        $copiaHtml = "
        <div style='font-family:Arial,sans-serif;max-width:600px;margin:0 auto;'>
            <div style='background:#1B6B6E;color:white;padding:20px 30px;border-radius:12px 12px 0 0;'>
                <h2 style='margin:0;font-size:18px;'>Copia de tu mensaje</h2>
                <p style='margin:5px 0 0;font-size:14px;opacity:0.8;'>Fisiopilates Atlas</p>
            </div>
            <div style='background:#ffffff;padding:30px;border:1px solid #d1e7e7;border-top:none;border-radius:0 0 12px 12px;'>
                <p style='color:#334155;margin-top:0;'>Hola <strong>" . htmlspecialchars($nombre, ENT_QUOTES, 'UTF-8') . "</strong>, te enviamos una copia del mensaje que has remitido a través de nuestro formulario de contacto. Nos pondremos en contacto contigo a la mayor brevedad.</p>
                <hr style='border:none;border-top:1px solid #e2e8f0;margin:20px 0;'/>
                <table style='width:100%;border-collapse:collapse;'>
                    <tr>
                        <td style='padding:6px 0;font-weight:bold;color:#334155;width:120px;vertical-align:top;'>Motivo:</td>
                        <td style='padding:6px 0;color:#1e293b;'>" . htmlspecialchars($motivoTexto, ENT_QUOTES, 'UTF-8') . "</td>
                    </tr>
                    <tr>
                        <td style='padding:6px 0;font-weight:bold;color:#334155;vertical-align:top;'>Mensaje:</td>
                        <td style='padding:6px 0;color:#1e293b;white-space:pre-wrap;'>" . htmlspecialchars($mensaje, ENT_QUOTES, 'UTF-8') . "</td>
                    </tr>
                    <tr>
                        <td style='padding:6px 0;font-weight:bold;color:#334155;vertical-align:top;'>Fecha:</td>
                        <td style='padding:6px 0;color:#94a3b8;font-size:13px;'>" . date('d/m/Y H:i:s') . "</td>
                    </tr>
                </table>
                <hr style='border:none;border-top:1px solid #e2e8f0;margin:20px 0;'/>
                <p style='color:#94a3b8;font-size:12px;margin-bottom:0;'>Este es un mensaje automático. No respondas a este correo. Para contactarnos directamente escríbenos a <a href='mailto:fisiopilates.atlas@gmail.com' style='color:#1B6B6E;'>fisiopilates.atlas@gmail.com</a> o llámanos al <a href='tel:691487526' style='color:#1B6B6E;'>691 487 526</a>.</p>
            </div>
        </div>";

        $mailer->send(
            $email,
            'Copia de tu mensaje — Fisiopilates Atlas',
            $copiaHtml
        );
    }

    header('Location: /contacto?status=ok');
} catch (Exception $e) {
    error_log('Error guardando contacto: ' . $e->getMessage());
    header('Location: /contacto?status=error');
}
exit;
