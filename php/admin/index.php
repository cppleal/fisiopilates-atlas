<?php
/**
 * Panel de Administración - Fisiopilates Atlas
 * Login, mensajes de contacto, gestión de IPs permitidas y cambio de contraseña.
 */

session_start();
require_once __DIR__ . '/../api/config.php';
require_once __DIR__ . '/ip-check.php';

// Conexión a BD (necesaria para verificación de IP, login y operaciones del panel)
$pdo = getDB();

// Verificación de IP permitida — bloquea si hay IPs configuradas y la actual no está en lista
checkAdminIP($pdo);

// =========================================
// LOGOUT
// =========================================
if (isset($_GET['action']) && $_GET['action'] === 'logout') {
    session_destroy();
    header('Location: /admin/');
    exit;
}

// =========================================
// LOGIN
// =========================================
$loginError = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['login'])) {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if (!empty($username) && !empty($password)) {
        $stmt = $pdo->prepare("SELECT id, username, password, nombre FROM admins WHERE username = ?");
        $stmt->execute([$username]);
        $admin = $stmt->fetch();

        if ($admin && password_verify($password, $admin['password'])) {
            $_SESSION['admin_id'] = $admin['id'];
            $_SESSION['admin_name'] = $admin['nombre'];

            $pdo->prepare("UPDATE admins SET last_login = NOW() WHERE id = ?")->execute([$admin['id']]);

            header('Location: /admin/');
            exit;
        } else {
            $loginError = 'Usuario o contraseña incorrectos.';
        }
    }
}

// =========================================
// Si no está logueado, mostrar login
// =========================================
if (!isset($_SESSION['admin_id'])) {
    ?>
    <!DOCTYPE html>
    <html lang="es">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Admin - Fisiopilates Atlas</title>
        <style>
            * { margin: 0; padding: 0; box-sizing: border-box; }
            body { font-family: 'Inter', 'Segoe UI', system-ui, sans-serif; background: #f4f9f9; display: flex; align-items: center; justify-content: center; min-height: 100vh; }
            .login-box { background: white; padding: 3rem; border-radius: 1rem; box-shadow: 0 4px 20px rgb(0 0 0 / 0.08); width: 100%; max-width: 400px; }
            .login-box h1 { font-size: 1.5rem; color: #1B6B6E; margin-bottom: 0.5rem; }
            .login-box p { color: #64748b; font-size: 0.875rem; margin-bottom: 2rem; }
            .form-group { margin-bottom: 1.25rem; }
            label { display: block; font-size: 0.875rem; font-weight: 600; color: #334155; margin-bottom: 0.5rem; }
            input[type="text"], input[type="password"] { width: 100%; padding: 0.75rem 1rem; border: 1px solid #d1e7e7; border-radius: 0.75rem; font-size: 1rem; outline: none; transition: border-color 0.2s; }
            input:focus { border-color: #1B6B6E; box-shadow: 0 0 0 3px rgb(27 107 110 / 0.1); }
            button { width: 100%; background: #1B6B6E; color: white; padding: 0.875rem; border: none; border-radius: 0.75rem; font-size: 1rem; font-weight: 600; cursor: pointer; transition: background 0.2s; }
            button:hover { background: #2D8A8E; }
            .error { background: #fef2f2; color: #dc2626; padding: 0.75rem; border-radius: 0.5rem; font-size: 0.875rem; margin-bottom: 1rem; }
        </style>
    </head>
    <body>
        <div class="login-box">
            <h1>Fisiopilates Atlas</h1>
            <p>Panel de Administración</p>
            <?php if ($loginError): ?>
                <div class="error"><?= htmlspecialchars($loginError) ?></div>
            <?php endif; ?>
            <form method="POST">
                <input type="hidden" name="login" value="1">
                <div class="form-group">
                    <label for="username">Usuario</label>
                    <input type="text" id="username" name="username" required autocomplete="username">
                </div>
                <div class="form-group">
                    <label for="password">Contraseña</label>
                    <input type="password" id="password" name="password" required autocomplete="current-password">
                </div>
                <button type="submit">Iniciar sesión</button>
            </form>
        </div>
    </body>
    </html>
    <?php
    exit;
}

// =========================================
// PANEL ADMIN (usuario autenticado)
// =========================================

// Marcar como leído
if (isset($_GET['action']) && $_GET['action'] === 'read' && isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    $pdo->prepare("UPDATE contacto SET leido = 1 WHERE id = ?")->execute([$id]);
    header('Location: /admin/');
    exit;
}

// Eliminar mensaje
if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    $pdo->prepare("DELETE FROM contacto WHERE id = ?")->execute([$id]);
    header('Location: /admin/');
    exit;
}

// Eliminar IP permitida
if (isset($_GET['action']) && $_GET['action'] === 'delete_ip' && isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    $pdo->prepare("DELETE FROM admin_ips WHERE id = ?")->execute([$id]);
    header('Location: /admin/#ips');
    exit;
}

// Añadir IP permitida
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_ip'])) {
    $ipAddress   = trim($_POST['ip_address']    ?? '');
    $descripcion = trim($_POST['ip_descripcion'] ?? '');
    if (filter_var($ipAddress, FILTER_VALIDATE_IP)) {
        try {
            $pdo->prepare("INSERT INTO admin_ips (ip_address, descripcion) VALUES (?, ?)")
                ->execute([$ipAddress, $descripcion]);
            $ipMsg = 'IP añadida correctamente.';
        } catch (PDOException $e) {
            $ipError = 'Esta IP ya existe en la lista de acceso.';
        }
    } else {
        $ipError = 'Dirección IP no válida.';
    }
}

// Cambiar contraseña
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['change_password'])) {
    $currentPass = $_POST['current_password'] ?? '';
    $newPass     = $_POST['new_password']     ?? '';
    $confirmPass = $_POST['confirm_password'] ?? '';

    if (strlen($newPass) < 8) {
        $passError = 'La nueva contraseña debe tener al menos 8 caracteres.';
    } elseif ($newPass !== $confirmPass) {
        $passError = 'La nueva contraseña y la confirmación no coinciden.';
    } else {
        $stmt = $pdo->prepare("SELECT password FROM admins WHERE id = ?");
        $stmt->execute([$_SESSION['admin_id']]);
        $adminData = $stmt->fetch();

        if (!$adminData || !password_verify($currentPass, $adminData['password'])) {
            $passError = 'La contraseña actual introducida es incorrecta.';
        } else {
            $hashed = password_hash($newPass, PASSWORD_DEFAULT);
            $pdo->prepare("UPDATE admins SET password = ? WHERE id = ?")->execute([$hashed, $_SESSION['admin_id']]);
            $passMsg = 'Contraseña actualizada correctamente.';
        }
    }
}

// Obtener mensajes
$page    = max(1, (int)($_GET['page'] ?? 1));
$perPage = 20;
$offset  = ($page - 1) * $perPage;

$total      = $pdo->query("SELECT COUNT(*) FROM contacto")->fetchColumn();
$totalPages = ceil($total / $perPage);

$stmt = $pdo->prepare("SELECT * FROM contacto ORDER BY created_at DESC LIMIT ? OFFSET ?");
$stmt->execute([$perPage, $offset]);
$mensajes = $stmt->fetchAll();

$noLeidos = $pdo->query("SELECT COUNT(*) FROM contacto WHERE leido = 0")->fetchColumn();

// Obtener IPs permitidas
$allowedIPs      = $pdo->query("SELECT * FROM admin_ips ORDER BY created_at DESC")->fetchAll();
$clientIPCurrent = getClientIP();

// Motivos legibles
$motivos = [
    'fisioterapia' => 'Cita fisioterapia',
    'pilates'      => 'Info Pilates',
    'precios'      => 'Consulta precios',
    'seguros'      => 'Seguros/mutuas',
    'otro'         => 'Otro motivo',
];
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin - Fisiopilates Atlas</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Inter', 'Segoe UI', system-ui, sans-serif; background: #f4f9f9; color: #1e293b; }
        .topbar { background: #1B6B6E; color: white; padding: 1rem 2rem; display: flex; justify-content: space-between; align-items: center; }
        .topbar h1 { font-size: 1.125rem; }
        .topbar a { color: #a7f3d0; text-decoration: none; font-size: 0.875rem; }
        .topbar a:hover { color: white; }
        .container { max-width: 1200px; margin: 2rem auto; padding: 0 1rem; }
        .stats { display: flex; gap: 1rem; margin-bottom: 2rem; flex-wrap: wrap; }
        .stat-card { background: white; padding: 1.5rem; border-radius: 0.75rem; box-shadow: 0 1px 3px rgb(0 0 0 / 0.06); flex: 1; min-width: 160px; }
        .stat-card .number { font-size: 2rem; font-weight: 800; color: #1B6B6E; }
        .stat-card .label { font-size: 0.875rem; color: #64748b; }
        .card { background: white; border-radius: 0.75rem; box-shadow: 0 1px 3px rgb(0 0 0 / 0.06); overflow: hidden; margin-bottom: 2rem; }
        .card-header { padding: 1.25rem 1.5rem; border-bottom: 1px solid #e2e8f0; font-weight: 700; }
        table { width: 100%; border-collapse: collapse; }
        th { text-align: left; padding: 0.75rem 1rem; font-size: 0.75rem; text-transform: uppercase; color: #64748b; background: #f8fafc; border-bottom: 1px solid #e2e8f0; }
        td { padding: 0.75rem 1rem; border-bottom: 1px solid #f1f5f9; font-size: 0.875rem; vertical-align: top; }
        tr:hover td { background: #f8fafc; }
        .unread td { font-weight: 600; background: #f0fdf9; }
        .badge { display: inline-block; padding: 0.2rem 0.6rem; border-radius: 1rem; font-size: 0.7rem; font-weight: 600; }
        .badge-new { background: #ccfbf1; color: #0d9488; }
        .badge-read { background: #f1f5f9; color: #64748b; }
        .badge-current { background: #dbeafe; color: #1d4ed8; }
        .btn { display: inline-block; padding: 0.4rem 0.8rem; border-radius: 0.5rem; font-size: 0.75rem; text-decoration: none; font-weight: 500; transition: all 0.2s; cursor: pointer; border: none; }
        .btn-read { background: #ccfbf1; color: #0d9488; }
        .btn-read:hover { background: #99f6e4; }
        .btn-delete { background: #fee2e2; color: #dc2626; }
        .btn-delete:hover { background: #fecaca; }
        .btn-secondary { background: #f1f5f9; color: #334155; border: 1px solid #d1e7e7; }
        .btn-secondary:hover { background: #e2e8f0; }
        .pagination { display: flex; gap: 0.5rem; justify-content: center; padding: 1rem; }
        .pagination a { padding: 0.5rem 1rem; background: white; border-radius: 0.5rem; text-decoration: none; color: #1B6B6E; font-size: 0.875rem; border: 1px solid #d1e7e7; }
        .pagination a.active { background: #1B6B6E; color: white; }
        .msg-preview { max-width: 280px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
        .success { background: #f0fdf4; color: #16a34a; padding: 0.75rem 1rem; border-radius: 0.5rem; margin-bottom: 1rem; font-size: 0.875rem; }
        .error-msg { background: #fef2f2; color: #dc2626; padding: 0.75rem 1rem; border-radius: 0.5rem; margin-bottom: 1rem; font-size: 0.875rem; }
        .warning-msg { background: #fffbeb; color: #b45309; padding: 0.75rem 1rem; font-size: 0.875rem; border-bottom: 1px solid #e2e8f0; }
        .form-inline { display: flex; gap: 0.75rem; align-items: flex-end; flex-wrap: wrap; padding: 1.5rem; }
        .form-inline label { font-size: 0.875rem; font-weight: 600; display: block; margin-bottom: 0.3rem; color: #334155; }
        .form-inline input[type="text"],
        .form-inline input[type="password"] { padding: 0.5rem 0.75rem; border: 1px solid #d1e7e7; border-radius: 0.5rem; font-size: 0.875rem; outline: none; }
        .form-inline input:focus { border-color: #1B6B6E; box-shadow: 0 0 0 2px rgb(27 107 110 / 0.1); }
        .form-inline button[type="submit"] { background: #1B6B6E; color: white; border: none; padding: 0.5rem 1.25rem; border-radius: 0.5rem; font-size: 0.875rem; font-weight: 600; cursor: pointer; }
        .form-inline button[type="submit"]:hover { background: #2D8A8E; }
        .ip-code { background: #f1f5f9; color: #334155; padding: 0.2rem 0.5rem; border-radius: 0.35rem; font-size: 0.85rem; font-family: monospace; }
        .section-msg { padding: 0 1.5rem; }
        @media (max-width: 768px) {
            table, thead, tbody, th, td, tr { display: block; }
            thead { display: none; }
            td { padding: 0.5rem 1rem; border: none; position: relative; padding-left: 40%; }
            td:before { position: absolute; left: 1rem; font-weight: 700; font-size: 0.75rem; color: #64748b; }
            td:nth-child(1):before { content: 'Estado'; }
            td:nth-child(2):before { content: 'Fecha'; }
            td:nth-child(3):before { content: 'Nombre'; }
            td:nth-child(4):before { content: 'Email'; }
            td:nth-child(5):before { content: 'Motivo'; }
            td:nth-child(6):before { content: 'Mensaje'; }
            td:nth-child(7):before { content: 'Acciones'; }
            tr { border-bottom: 2px solid #e2e8f0; margin-bottom: 0.5rem; padding: 0.5rem 0; }
        }
    </style>
</head>
<body>
    <div class="topbar">
        <h1>Panel de Administración — Fisiopilates Atlas</h1>
        <div>
            <span style="margin-right:1rem; font-size:0.875rem;">Hola, <?= htmlspecialchars($_SESSION['admin_name']) ?></span>
            <a href="/admin/cookies.php">Cookies RGPD</a> &nbsp;|&nbsp;
            <a href="/">Ver web</a> &nbsp;|&nbsp;
            <a href="/admin/?action=logout">Cerrar sesión</a>
        </div>
    </div>

    <div class="container">
        <?php if (isset($passMsg)): ?>
            <div class="success"><?= htmlspecialchars($passMsg) ?></div>
        <?php endif; ?>
        <?php if (isset($passError)): ?>
            <div class="error-msg"><?= htmlspecialchars($passError) ?></div>
        <?php endif; ?>
        <?php if (isset($ipMsg)): ?>
            <div class="success"><?= htmlspecialchars($ipMsg) ?></div>
        <?php endif; ?>
        <?php if (isset($ipError)): ?>
            <div class="error-msg"><?= htmlspecialchars($ipError) ?></div>
        <?php endif; ?>

        <!-- Estadísticas -->
        <div class="stats">
            <div class="stat-card">
                <div class="number"><?= $total ?></div>
                <div class="label">Mensajes totales</div>
            </div>
            <div class="stat-card">
                <div class="number"><?= $noLeidos ?></div>
                <div class="label">Sin leer</div>
            </div>
            <div class="stat-card" style="cursor:pointer;" onclick="window.location='/admin/cookies.php'">
                <div class="number" style="font-size:1.5rem;">🍪</div>
                <div class="label">Cookies RGPD</div>
            </div>
            <div class="stat-card" style="cursor:pointer;" onclick="document.getElementById('ips').scrollIntoView({behavior:'smooth'})">
                <div class="number" style="font-size:1.5rem; color:<?= empty($allowedIPs) ? '#b45309' : '#1B6B6E' ?>;"><?= count($allowedIPs) ?></div>
                <div class="label">IPs permitidas<?= empty($allowedIPs) ? ' ⚠' : '' ?></div>
            </div>
        </div>

        <!-- Mensajes de contacto -->
        <div class="card">
            <div class="card-header">Mensajes de contacto</div>
            <?php if (empty($mensajes)): ?>
                <div style="padding: 2rem; text-align: center; color: #64748b;">
                    No hay mensajes todavía.
                </div>
            <?php else: ?>
                <table>
                    <thead>
                        <tr>
                            <th>Estado</th>
                            <th>Fecha</th>
                            <th>Nombre</th>
                            <th>Email / Tel</th>
                            <th>Motivo</th>
                            <th>Mensaje</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($mensajes as $msg): ?>
                            <tr class="<?= $msg['leido'] ? '' : 'unread' ?>">
                                <td>
                                    <?php if ($msg['leido']): ?>
                                        <span class="badge badge-read">Leído</span>
                                    <?php else: ?>
                                        <span class="badge badge-new">Nuevo</span>
                                    <?php endif; ?>
                                </td>
                                <td><?= date('d/m/Y H:i', strtotime($msg['created_at'])) ?></td>
                                <td><?= htmlspecialchars($msg['nombre']) ?></td>
                                <td>
                                    <a href="mailto:<?= htmlspecialchars($msg['email']) ?>"><?= htmlspecialchars($msg['email']) ?></a>
                                    <?php if (!empty($msg['telefono'])): ?>
                                        <br><a href="tel:<?= htmlspecialchars($msg['telefono']) ?>" style="color:#1B6B6E;font-size:0.8rem;"><?= htmlspecialchars($msg['telefono']) ?></a>
                                    <?php endif; ?>
                                </td>
                                <td><?= htmlspecialchars($motivos[$msg['motivo']] ?? $msg['motivo']) ?></td>
                                <td class="msg-preview" title="<?= htmlspecialchars($msg['mensaje']) ?>"><?= htmlspecialchars($msg['mensaje']) ?></td>
                                <td>
                                    <?php if (!$msg['leido']): ?>
                                        <a href="/admin/?action=read&id=<?= $msg['id'] ?>" class="btn btn-read">Marcar leído</a>
                                    <?php endif; ?>
                                    <a href="/admin/?action=delete&id=<?= $msg['id'] ?>" class="btn btn-delete" onclick="return confirm('¿Eliminar este mensaje?')">Eliminar</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>

                <?php if ($totalPages > 1): ?>
                    <div class="pagination">
                        <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                            <a href="/admin/?page=<?= $i ?>" class="<?= $i === $page ? 'active' : '' ?>"><?= $i ?></a>
                        <?php endfor; ?>
                    </div>
                <?php endif; ?>
            <?php endif; ?>
        </div>

        <!-- Gestión de IPs permitidas -->
        <div class="card" id="ips">
            <div class="card-header">Control de acceso por IP</div>

            <?php if (empty($allowedIPs)): ?>
                <div class="warning-msg">
                    ⚠ Sin restricción de IP activa. El panel es accesible desde cualquier IP. Añade al menos una IP para activar la protección.
                </div>
            <?php else: ?>
                <table>
                    <thead>
                        <tr>
                            <th>Dirección IP</th>
                            <th>Descripción</th>
                            <th>Fecha de alta</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($allowedIPs as $ip): ?>
                            <tr>
                                <td>
                                    <span class="ip-code"><?= htmlspecialchars($ip['ip_address']) ?></span>
                                    <?php if ($ip['ip_address'] === $clientIPCurrent): ?>
                                        <span class="badge badge-current" style="margin-left:0.5rem;">Tu IP actual</span>
                                    <?php endif; ?>
                                </td>
                                <td style="color:#64748b;"><?= htmlspecialchars($ip['descripcion'] ?: '—') ?></td>
                                <td style="color:#94a3b8; font-size:0.8rem;"><?= date('d/m/Y H:i', strtotime($ip['created_at'])) ?></td>
                                <td>
                                    <a href="/admin/?action=delete_ip&id=<?= $ip['id'] ?>"
                                       class="btn btn-delete"
                                       onclick="return confirm('¿Eliminar la IP <?= htmlspecialchars($ip['ip_address']) ?>?\n\n<?= $ip['ip_address'] === $clientIPCurrent ? 'ATENCIÓN: Es tu IP actual. Perderás el acceso al panel si no hay otras IPs permitidas.' : '¿Seguro que deseas eliminarla?' ?>')">
                                        Eliminar
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>

            <!-- Formulario añadir IP -->
            <form method="POST" class="form-inline" style="border-top: 1px solid #e2e8f0;">
                <input type="hidden" name="add_ip" value="1">
                <div>
                    <label for="ip_address">Dirección IP</label>
                    <input type="text" id="ip_address" name="ip_address"
                           value="<?= htmlspecialchars($clientIPCurrent) ?>"
                           style="width:200px;" maxlength="45">
                </div>
                <div>
                    <label for="ip_descripcion">Descripción (opcional)</label>
                    <input type="text" id="ip_descripcion" name="ip_descripcion"
                           placeholder="Ej: Oficina, Casa..." style="width:220px;" maxlength="255">
                </div>
                <button type="submit">Añadir IP</button>
                <button type="button" class="btn btn-secondary"
                        onclick="document.getElementById('ip_address').value='<?= htmlspecialchars($clientIPCurrent) ?>'">
                    Usar mi IP actual (<?= htmlspecialchars($clientIPCurrent) ?>)
                </button>
            </form>
        </div>

        <!-- Cambiar contraseña -->
        <div class="card">
            <div class="card-header">Cambiar contraseña</div>
            <form method="POST" class="form-inline">
                <input type="hidden" name="change_password" value="1">
                <div>
                    <label for="current_password">Contraseña actual</label>
                    <input type="password" id="current_password" name="current_password"
                           required autocomplete="current-password" style="width:200px;">
                </div>
                <div>
                    <label for="new_password">Nueva contraseña</label>
                    <input type="password" id="new_password" name="new_password"
                           required minlength="8" placeholder="Mínimo 8 caracteres"
                           autocomplete="new-password" style="width:220px;">
                </div>
                <div>
                    <label for="confirm_password">Confirmar nueva contraseña</label>
                    <input type="password" id="confirm_password" name="confirm_password"
                           required minlength="8" autocomplete="new-password" style="width:220px;">
                </div>
                <button type="submit">Actualizar contraseña</button>
            </form>
        </div>

    </div>
</body>
</html>
