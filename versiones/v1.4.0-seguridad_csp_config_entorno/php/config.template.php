<?php
/**
 * config.template.php - Plantilla del api/config.php de cada servidor - Fisiopilates Atlas
 *
 * No se copia a mano: scripts/config-servidor.mjs cambia cada {{CLAVE}} por el
 * valor CLAVE_TEST o CLAVE_PROD del .env local, según el entorno al que se sube.
 * Así el config.php de cada servidor lleva solo las credenciales de ese entorno.
 *
 *   node scripts/config-servidor.mjs test|prod --simular   compara con el del servidor
 *   node scripts/deploy.mjs                                 lo genera y lo sube a TEST
 */

// Credenciales de base de datos
define('DB_HOST', '{{DB_HOST}}');
define('DB_NAME', '{{DB_NAME}}');
define('DB_USER', '{{DB_USER}}');
define('DB_PASS', '{{DB_PASS}}');

// Clave secreta para sesiones admin
define('APP_SECRET', '{{APP_SECRET}}');

// hCaptcha (https://dashboard.hcaptcha.com)
define('HCAPTCHA_SITE_KEY', '{{HCAPTCHA_SITE_KEY}}');
define('HCAPTCHA_SECRET',   '{{HCAPTCHA_SECRET}}');

// SMTP
define('SMTP_HOST',      '{{SMTP_HOST}}');
define('SMTP_PORT',      {{SMTP_PORT}});
define('SMTP_USER',      '{{SMTP_USER}}');
define('SMTP_PASS',      '{{SMTP_PASS}}');
define('SMTP_FROM',      '{{SMTP_FROM}}');
define('SMTP_FROM_NAME', '{{SMTP_FROM_NAME}}');

// Entorno: TEST si el nombre de BD contiene '_TEST' (case-insensitive)
define('IS_TEST_ENV', stripos(DB_NAME, '_TEST') !== false);

// Destinatarios contacto según entorno
define('CONTACT_TO',       IS_TEST_ENV ? 'cppleal@gmail.com'            : 'fisiopilates.atlas@gmail.com');
define('CONTACT_REPLY_TO', IS_TEST_ENV ? ''                              : 'fisiopilates.atlas@gmail.com');
define('CONTACT_BCC',      '');

// Zona horaria
date_default_timezone_set('Europe/Madrid');

/**
 * Conexión PDO a MySQL
 */
function getDB(): PDO {
    static $pdo = null;
    if ($pdo === null) {
        try {
            $dsn = 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4';
            $pdo = new PDO($dsn, DB_USER, DB_PASS, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ]);
        } catch (PDOException $e) {
            error_log('Error de conexión DB: ' . $e->getMessage());
            die('Error de conexión a la base de datos.');
        }
    }
    return $pdo;
}
