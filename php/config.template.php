<?php
/**
 * config.template.php - Plantilla de configuración - Fisiopilates Atlas
 *
 * INSTRUCCIONES:
 *   1. Copiar este fichero como php/config.php
 *   2. Rellenar los valores reales
 *   3. php/config.php NO se sube a git (está en .gitignore)
 */

// Credenciales de base de datos
define('DB_HOST', getenv('DB_HOST') ?: 'PMYSQL168.dns-servicio.com');
define('DB_NAME', getenv('DB_NAME') ?: '10067489_fisiopilates_TEST');
define('DB_USER', getenv('DB_USER') ?: 'TU_USUARIO_BD');
define('DB_PASS', getenv('DB_PASS') ?: 'TU_PASSWORD_BD');

// Clave secreta para sesiones admin
define('APP_SECRET', 'CAMBIA_ESTO_POR_UNA_CLAVE_SEGURA');

// hCaptcha (https://dashboard.hcaptcha.com)
define('HCAPTCHA_SITE_KEY', 'TU_SITE_KEY_HCAPTCHA');
define('HCAPTCHA_SECRET',   'TU_SECRET_HCAPTCHA');

// SMTP
define('SMTP_HOST',      'smtp.servidor-correo.net');
define('SMTP_PORT',      587);
define('SMTP_USER',      'envios@fisiopilatesatlas.es');
define('SMTP_PASS',      'TU_PASSWORD_SMTP');
define('SMTP_FROM',      'envios@fisiopilatesatlas.es');
define('SMTP_FROM_NAME', 'Fisiopilates Atlas');

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
            if (isset($_GET['debug'])) {
                die('Error DB: ' . $e->getMessage());
            }
            die('Error de conexión a la base de datos.');
        }
    }
    return $pdo;
}
