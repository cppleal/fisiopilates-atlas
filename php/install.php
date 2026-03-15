<?php
/**
 * Script de instalación - Fisiopilates Atlas
 * Crea las tablas necesarias en la base de datos.
 * ELIMINAR este archivo del servidor tras la instalación.
 */

require_once __DIR__ . '/config.php';

try {
    $pdo = getDB();

    // Tabla de mensajes de contacto
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS contacto (
            id INT AUTO_INCREMENT PRIMARY KEY,
            nombre VARCHAR(255) NOT NULL,
            email VARCHAR(255) NOT NULL,
            telefono VARCHAR(50) DEFAULT '',
            motivo VARCHAR(100) NOT NULL,
            mensaje TEXT NOT NULL,
            ip VARCHAR(45) DEFAULT '',
            leido TINYINT(1) DEFAULT 0,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    ");

    // Tabla de administradores
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS admins (
            id INT AUTO_INCREMENT PRIMARY KEY,
            username VARCHAR(100) NOT NULL UNIQUE,
            password VARCHAR(255) NOT NULL,
            nombre VARCHAR(255) NOT NULL,
            email VARCHAR(255) DEFAULT '',
            last_login TIMESTAMP NULL DEFAULT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    ");

    // Tabla de IPs permitidas para el panel de administración
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS admin_ips (
            id INT AUTO_INCREMENT PRIMARY KEY,
            ip_address VARCHAR(45) NOT NULL UNIQUE,
            descripcion VARCHAR(255) DEFAULT '',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    ");

    // Registrar la IP del instalador como IP permitida
    $installerIP = $_SERVER['REMOTE_ADDR'] ?? '';
    if ($installerIP) {
        $stmtIP = $pdo->prepare("INSERT IGNORE INTO admin_ips (ip_address, descripcion) VALUES (?, 'IP del instalador')");
        $stmtIP->execute([$installerIP]);
    }

    // Tabla de logs de cookies (RGPD)
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS cookie_consent_logs (
            id INT AUTO_INCREMENT PRIMARY KEY,
            session_token VARCHAR(64) DEFAULT NULL,
            action VARCHAR(50) NOT NULL,
            analytics TINYINT(1) DEFAULT 0,
            functional TINYINT(1) DEFAULT 0,
            necessary TINYINT(1) DEFAULT 1,
            ip_address VARCHAR(45) DEFAULT NULL,
            user_agent TEXT DEFAULT NULL,
            page_url VARCHAR(500) DEFAULT NULL,
            consent_version VARCHAR(10) DEFAULT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    ");

    // Crear admin por defecto (cambiar contraseña inmediatamente)
    $defaultPass = password_hash('atlas2025', PASSWORD_DEFAULT);
    $pdo->exec("
        INSERT IGNORE INTO admins (username, password, nombre, email)
        VALUES ('admin', '{$defaultPass}', 'Administrador', 'fisiopilates.atlas@gmail.com')
    ");

    echo "<h1 style='font-family:sans-serif;color:#1B6B6E;'>Instalación completada</h1>";
    echo "<p style='font-family:sans-serif;'>Tablas creadas correctamente.</p>";
    if ($installerIP) {
        echo "<p style='font-family:sans-serif;color:#0d9488;'>IP registrada como permitida: <code style='background:#f1f5f9;padding:0.1rem 0.4rem;border-radius:0.25rem;'>" . htmlspecialchars($installerIP) . "</code>. Puedes añadir más IPs desde el panel de administración.</p>";
    }
    echo "<p style='font-family:sans-serif;color:#dc2626;'><strong>IMPORTANTE:</strong> Elimina este archivo del servidor ahora.</p>";
    echo "<p style='font-family:sans-serif;'>Credenciales por defecto: <code>admin</code> / <code>atlas2025</code> — <strong>Cámbialas inmediatamente</strong>.</p>";

} catch (Exception $e) {
    echo "<h1 style='font-family:sans-serif;color:#dc2626;'>Error en la instalación</h1>";
    echo "<p style='font-family:sans-serif;'>" . htmlspecialchars($e->getMessage()) . "</p>";
}
