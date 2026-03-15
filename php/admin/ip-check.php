<?php
/**
 * Protección de acceso al panel de administración por IP.
 * Si hay IPs en la tabla admin_ips, solo esas IPs tienen acceso.
 * Si la tabla está vacía, el acceso es libre (estado inicial tras instalación).
 */

function getClientIP(): string {
    return $_SERVER['REMOTE_ADDR'] ?? '';
}

function checkAdminIP(PDO $pdo): void {
    $count = (int)$pdo->query("SELECT COUNT(*) FROM admin_ips")->fetchColumn();

    // Sin IPs configuradas → acceso libre (instalación inicial)
    if ($count === 0) return;

    $clientIP = getClientIP();
    $stmt = $pdo->prepare("SELECT id FROM admin_ips WHERE ip_address = ?");
    $stmt->execute([$clientIP]);

    if ($stmt->fetch()) return;

    http_response_code(403);
    ?>
    <!DOCTYPE html>
    <html lang="es">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Acceso denegado — Admin Fisiopilates Atlas</title>
        <style>
            * { margin: 0; padding: 0; box-sizing: border-box; }
            body { font-family: 'Inter', 'Segoe UI', system-ui, sans-serif; background: #f4f9f9; display: flex; align-items: center; justify-content: center; min-height: 100vh; }
            .box { background: white; padding: 3rem; border-radius: 1rem; box-shadow: 0 4px 20px rgb(0 0 0 / 0.08); text-align: center; max-width: 420px; width: 100%; margin: 1rem; }
            .icon { font-size: 3rem; margin-bottom: 1rem; }
            h1 { color: #dc2626; font-size: 1.5rem; margin-bottom: 0.75rem; }
            p { color: #64748b; font-size: 0.875rem; margin-bottom: 0.5rem; line-height: 1.6; }
            code { background: #f1f5f9; color: #334155; padding: 0.2rem 0.5rem; border-radius: 0.35rem; font-size: 0.85rem; display: inline-block; margin-top: 0.5rem; }
        </style>
    </head>
    <body>
        <div class="box">
            <div class="icon">🔒</div>
            <h1>Acceso denegado</h1>
            <p>Tu dirección IP no está autorizada para acceder a este panel de administración.</p>
            <p>Contacta con el administrador del sistema para añadir tu IP a la lista de acceso.</p>
            <code><?= htmlspecialchars($clientIP) ?></code>
        </div>
    </body>
    </html>
    <?php
    exit;
}
