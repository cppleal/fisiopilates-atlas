<?php
/**
 * Informe de Consentimiento de Cookies - Fisiopilates Atlas
 * Cumplimiento RGPD/LSSI-CE
 */

session_start();
require_once __DIR__ . '/../api/config.php';
require_once __DIR__ . '/../api/cookies/CookieConsentService.php';

if (!isset($_SESSION['admin_id'])) {
    header('Location: /admin/');
    exit;
}

$pdo = getDB();
$cookieService = new CookieConsentService($pdo);

// Exportar CSV
if (isset($_GET['export']) && $_GET['export'] === 'csv') {
    $filters = [
        'date_from' => $_GET['date_from'] ?? null,
        'date_to'   => $_GET['date_to']   ?? null,
    ];
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="cookies_' . date('Y-m-d') . '.csv"');
    echo $cookieService->exportToCSV($filters);
    exit;
}

$period  = $_GET['period'] ?? 'month';
$stats   = $cookieService->getConsentStats($period);
$page    = max(1, (int)($_GET['page'] ?? 1));
$filters = [
    'action'    => $_GET['action_filter'] ?? null,
    'date_from' => $_GET['date_from']     ?? null,
    'date_to'   => $_GET['date_to']       ?? null,
];
$history = $cookieService->getConsentHistory($page, 25, $filters);

$actionLabels = [
    'accept_all'       => 'Aceptar todo',
    'reject_all'       => 'Solo necesarias',
    'save_preferences' => 'Personalizado',
    'withdraw'         => 'Retirar consentimiento',
];

$periodLabels = [
    'today' => 'Hoy',
    'week'  => 'Última semana',
    'month' => 'Último mes',
    'year'  => 'Último año',
    'all'   => 'Todo el historial',
];
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cookies RGPD — Admin Fisiopilates Atlas</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Inter', 'Segoe UI', system-ui, sans-serif; background: #f4f9f9; color: #1e293b; }
        .topbar { background: #1B6B6E; color: white; padding: 1rem 2rem; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 0.5rem; }
        .topbar h1 { font-size: 1.125rem; }
        .topbar a { color: #a7f3d0; text-decoration: none; font-size: 0.875rem; }
        .topbar a:hover { color: white; }
        .container { max-width: 1400px; margin: 2rem auto; padding: 0 1rem; }

        .page-header { display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 1.5rem; flex-wrap: wrap; gap: 1rem; }
        .page-header h2 { font-size: 1.5rem; color: #1B6B6E; }
        .page-header p { font-size: 0.875rem; color: #64748b; margin-top: 0.25rem; }
        .header-actions { display: flex; gap: 0.75rem; flex-wrap: wrap; }

        .btn { display: inline-flex; align-items: center; gap: 0.5rem; padding: 0.625rem 1.25rem; border-radius: 0.75rem; font-size: 0.875rem; font-weight: 600; cursor: pointer; transition: all 0.2s; border: none; text-decoration: none; }
        .btn-secondary { background: white; color: #334155; border: 1px solid #d1e7e7; }
        .btn-secondary:hover { background: #f4f9f9; }
        .btn-primary { background: #1B6B6E; color: white; }
        .btn-primary:hover { background: #2D8A8E; }
        .btn-export { background: #059669; color: white; }
        .btn-export:hover { background: #047857; }

        .stats-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 1rem; margin-bottom: 1.5rem; }
        .stat-card { background: white; padding: 1.25rem; border-radius: 0.75rem; box-shadow: 0 1px 3px rgb(0 0 0 / 0.06); text-align: center; }
        .stat-card .stat-value { font-size: 2rem; font-weight: 800; color: #1B6B6E; display: block; margin-bottom: 0.25rem; }
        .stat-card .stat-label { font-size: 0.8rem; color: #64748b; }
        .stat-card.highlight { border: 2px solid #1B6B6E; }
        .stat-card.green .stat-value { color: #16a34a; }
        .stat-card.red .stat-value { color: #dc2626; }
        .stat-card.blue .stat-value { color: #2563eb; }

        .card { background: white; border-radius: 0.75rem; box-shadow: 0 1px 3px rgb(0 0 0 / 0.06); overflow: hidden; margin-bottom: 1.5rem; }
        .card-header { padding: 1rem 1.5rem; border-bottom: 1px solid #e2e8f0; font-weight: 700; color: #1B6B6E; font-size: 0.95rem; }

        .pct-section { padding: 1.25rem 1.5rem; display: grid; gap: 0.75rem; }
        .pct-bar { display: flex; align-items: center; gap: 0.75rem; }
        .pct-label { width: 100px; font-size: 0.875rem; color: #334155; font-weight: 500; }
        .pct-track { flex: 1; height: 10px; background: #f1f5f9; border-radius: 5px; overflow: hidden; }
        .pct-fill { height: 100%; border-radius: 5px; }
        .pct-fill.analytics  { background: #1B6B6E; }
        .pct-fill.functional { background: #E07B39; }
        .pct-value { font-size: 0.875rem; font-weight: 700; color: #1B6B6E; min-width: 50px; text-align: right; }

        .filters { background: white; border-radius: 0.75rem; box-shadow: 0 1px 3px rgb(0 0 0 / 0.06); padding: 1.25rem 1.5rem; margin-bottom: 1.5rem; }
        .filters-row { display: flex; flex-wrap: wrap; gap: 1rem; align-items: flex-end; }
        .filter-group { flex: 1; min-width: 140px; }
        .filter-group label { display: block; font-size: 0.8rem; font-weight: 600; color: #64748b; margin-bottom: 0.35rem; text-transform: uppercase; letter-spacing: 0.3px; }
        .filter-group select, .filter-group input { width: 100%; padding: 0.5rem 0.75rem; border: 1px solid #d1e7e7; border-radius: 0.5rem; font-size: 0.875rem; background: white; color: #1e293b; }
        .filter-group select:focus, .filter-group input:focus { outline: none; border-color: #1B6B6E; box-shadow: 0 0 0 3px rgb(27 107 110 / 0.1); }

        .table-scroll { overflow-x: auto; }
        table { width: 100%; border-collapse: collapse; }
        thead { background: #f4f9f9; }
        th { padding: 0.75rem 1rem; text-align: left; font-size: 0.75rem; text-transform: uppercase; color: #64748b; border-bottom: 1px solid #e2e8f0; letter-spacing: 0.3px; }
        td { padding: 0.75rem 1rem; font-size: 0.875rem; border-bottom: 1px solid #f1f5f9; vertical-align: middle; }
        tr:hover td { background: #f4f9f9; }

        .badge { display: inline-block; padding: 0.2rem 0.6rem; border-radius: 1rem; font-size: 0.7rem; font-weight: 600; }
        .badge-accept   { background: #dcfce7; color: #16a34a; }
        .badge-reject   { background: #fee2e2; color: #dc2626; }
        .badge-custom   { background: #ccfbf1; color: #0d9488; }
        .badge-withdraw { background: #fef3c7; color: #b45309; }

        .chk { display: inline-flex; align-items: center; justify-content: center; width: 24px; height: 24px; border-radius: 50%; font-size: 0.8rem; font-weight: 700; }
        .chk.yes { background: #dcfce7; color: #16a34a; }
        .chk.no  { background: #fee2e2; color: #dc2626; }

        .pagination { display: flex; justify-content: center; align-items: center; gap: 0.4rem; padding: 1.25rem; }
        .pagination a, .pagination span { padding: 0.5rem 0.875rem; border-radius: 0.5rem; font-size: 0.875rem; text-decoration: none; }
        .pagination a { background: white; color: #1B6B6E; border: 1px solid #d1e7e7; }
        .pagination a:hover { background: #f4f9f9; }
        .pagination .current { background: #1B6B6E; color: white; font-weight: 600; }
        .empty-state { padding: 3rem; text-align: center; color: #94a3b8; }

        @media (max-width: 768px) {
            .stats-grid { grid-template-columns: repeat(2, 1fr); }
            table { min-width: 600px; }
        }
    </style>
</head>
<body>
<div class="topbar">
    <h1>Cookies RGPD — Fisiopilates Atlas</h1>
    <div>
        <span style="margin-right:1rem; font-size:0.875rem;">Hola, <?= htmlspecialchars($_SESSION['admin_name']) ?></span>
        <a href="/admin/">Panel principal</a> &nbsp;|&nbsp;
        <a href="/">Ver web</a> &nbsp;|&nbsp;
        <a href="/admin/?action=logout">Cerrar sesión</a>
    </div>
</div>

<div class="container">
    <div class="page-header">
        <div>
            <h2>Consentimiento de Cookies</h2>
            <p>Registro RGPD/LSSI-CE &mdash; IPs anonimizadas &mdash; Retención: 13 meses</p>
        </div>
        <div class="header-actions">
            <a href="/admin/" class="btn btn-secondary">&larr; Volver al Panel</a>
            <a href="?export=csv&date_from=<?= htmlspecialchars($filters['date_from'] ?? '') ?>&date_to=<?= htmlspecialchars($filters['date_to'] ?? '') ?>" class="btn btn-export">&#8659; Descargar CSV</a>
        </div>
    </div>

    <!-- Selector período -->
    <div class="filters" style="margin-bottom:1rem;">
        <div class="filters-row">
            <div class="filter-group" style="max-width:250px;">
                <label>Período de estadísticas</label>
                <select onchange="window.location.href='?period='+this.value">
                    <?php foreach ($periodLabels as $key => $label): ?>
                        <option value="<?= $key ?>" <?= $period === $key ? 'selected' : '' ?>><?= $label ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>
    </div>

    <!-- Estadísticas -->
    <div class="stats-grid">
        <div class="stat-card highlight">
            <span class="stat-value"><?= number_format($stats['total_interacciones'] ?? 0) ?></span>
            <span class="stat-label">Total Interacciones</span>
        </div>
        <div class="stat-card green">
            <span class="stat-value"><?= number_format($stats['aceptar_todo'] ?? 0) ?></span>
            <span class="stat-label">Aceptar Todo</span>
        </div>
        <div class="stat-card red">
            <span class="stat-value"><?= number_format($stats['rechazar_todo'] ?? 0) ?></span>
            <span class="stat-label">Solo Necesarias</span>
        </div>
        <div class="stat-card blue">
            <span class="stat-value"><?= number_format($stats['personalizado'] ?? 0) ?></span>
            <span class="stat-label">Personalizado</span>
        </div>
    </div>

    <!-- Porcentajes por categoría -->
    <div class="card">
        <div class="card-header">Aceptación por Categoría</div>
        <div class="pct-section">
            <div class="pct-bar">
                <span class="pct-label">Analíticas</span>
                <div class="pct-track"><div class="pct-fill analytics" style="width:<?= $stats['pct_analytics'] ?? 0 ?>%"></div></div>
                <span class="pct-value"><?= $stats['pct_analytics'] ?? 0 ?>%</span>
            </div>
            <div class="pct-bar">
                <span class="pct-label">Funcionales</span>
                <div class="pct-track"><div class="pct-fill functional" style="width:<?= $stats['pct_functional'] ?? 0 ?>%"></div></div>
                <span class="pct-value"><?= $stats['pct_functional'] ?? 0 ?>%</span>
            </div>
        </div>
    </div>

    <!-- Filtros historial -->
    <form class="filters" method="get">
        <div class="filters-row">
            <div class="filter-group">
                <label>Acción</label>
                <select name="action_filter">
                    <option value="">Todas</option>
                    <?php foreach ($actionLabels as $key => $label): ?>
                        <option value="<?= $key ?>" <?= ($filters['action'] ?? '') === $key ? 'selected' : '' ?>><?= $label ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="filter-group">
                <label>Desde</label>
                <input type="date" name="date_from" value="<?= htmlspecialchars($filters['date_from'] ?? '') ?>">
            </div>
            <div class="filter-group">
                <label>Hasta</label>
                <input type="date" name="date_to" value="<?= htmlspecialchars($filters['date_to'] ?? '') ?>">
            </div>
            <div class="filter-group" style="flex:0;">
                <label>&nbsp;</label>
                <button type="submit" class="btn btn-primary">Filtrar</button>
            </div>
        </div>
    </form>

    <!-- Tabla historial -->
    <div class="card">
        <div class="card-header">
            Registro de consentimientos
            <span style="font-weight:400; color:#64748b; font-size:0.8rem; margin-left:0.5rem;">(<?= number_format($history['total']) ?> registros)</span>
        </div>
        <div class="table-scroll">
            <table>
                <thead>
                    <tr>
                        <th>Fecha</th>
                        <th>Acción</th>
                        <th>Analíticas</th>
                        <th>Funcionales</th>
                        <th>IP</th>
                        <th>Navegador</th>
                        <th>Página</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($history['data'])): ?>
                        <tr><td colspan="7" class="empty-state">No hay registros de consentimiento todavía</td></tr>
                    <?php else: ?>
                        <?php foreach ($history['data'] as $row): ?>
                            <?php
                            $badgeClass = match($row['action']) {
                                'accept_all'       => 'badge-accept',
                                'reject_all'       => 'badge-reject',
                                'save_preferences' => 'badge-custom',
                                'withdraw'         => 'badge-withdraw',
                                default            => '',
                            };
                            ?>
                            <tr>
                                <td style="white-space:nowrap;"><?= date('d/m/Y H:i', strtotime($row['created_at'])) ?></td>
                                <td><span class="badge <?= $badgeClass ?>"><?= $actionLabels[$row['action']] ?? $row['action'] ?></span></td>
                                <td><span class="chk <?= $row['analytics']  ? 'yes' : 'no' ?>"><?= $row['analytics']  ? '✓' : '✗' ?></span></td>
                                <td><span class="chk <?= $row['functional'] ? 'yes' : 'no' ?>"><?= $row['functional'] ? '✓' : '✗' ?></span></td>
                                <td style="font-size:0.8rem; color:#94a3b8;"><?= htmlspecialchars($row['ip_address'] ?? '-') ?></td>
                                <td style="font-size:0.8rem; color:#64748b;"><?= htmlspecialchars($row['user_agent'] ?? '-') ?></td>
                                <td style="font-size:0.8rem; max-width:180px; overflow:hidden; text-overflow:ellipsis; white-space:nowrap;" title="<?= htmlspecialchars($row['page_url'] ?? '') ?>">
                                    <?= htmlspecialchars(parse_url($row['page_url'] ?? '', PHP_URL_PATH) ?: '-') ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <?php if ($history['total_pages'] > 1): ?>
            <div class="pagination">
                <?php if ($page > 1): ?>
                    <a href="?page=1&<?= http_build_query(array_filter($filters)) ?>">«</a>
                    <a href="?page=<?= $page - 1 ?>&<?= http_build_query(array_filter($filters)) ?>">‹</a>
                <?php endif; ?>
                <?php
                $start = max(1, $page - 2);
                $end   = min($history['total_pages'], $page + 2);
                for ($i = $start; $i <= $end; $i++):
                ?>
                    <?php if ($i === $page): ?>
                        <span class="current"><?= $i ?></span>
                    <?php else: ?>
                        <a href="?page=<?= $i ?>&<?= http_build_query(array_filter($filters)) ?>"><?= $i ?></a>
                    <?php endif; ?>
                <?php endfor; ?>
                <?php if ($page < $history['total_pages']): ?>
                    <a href="?page=<?= $page + 1 ?>&<?= http_build_query(array_filter($filters)) ?>">›</a>
                    <a href="?page=<?= $history['total_pages'] ?>&<?= http_build_query(array_filter($filters)) ?>">»</a>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>
</div>
</body>
</html>
