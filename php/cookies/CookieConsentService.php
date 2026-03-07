<?php
/**
 * Servicio de gestión de consentimiento de cookies RGPD/LSSI-CE
 * Fisiopilates Atlas
 * - Registra acción, preferencias, IP anonimizada, user-agent y página
 * - Purga automática de registros con más de 13 meses (RGPD art. 5.1.e)
 * - Exportación CSV en español con BOM UTF-8
 */

class CookieConsentService
{
    private PDO $pdo;
    private string $consentVersion = '1.0';

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    /**
     * Registra una acción de consentimiento
     */
    public function logConsent(
        ?string $sessionToken,
        string $action,
        bool $analytics,
        bool $functional
    ): bool {
        // Purga automática RGPD: elimina registros > 13 meses
        $this->purgeOldRecords();

        $stmt = $this->pdo->prepare("
            INSERT INTO cookie_consent_logs
                (session_token, action, necessary, analytics, functional,
                 ip_address, user_agent, page_url, consent_version)
            VALUES
                (?, ?, 1, ?, ?, ?, ?, ?, ?)
        ");

        return $stmt->execute([
            $sessionToken,
            $action,
            $analytics ? 1 : 0,
            $functional ? 1 : 0,
            $this->getRawIp(),
            $this->getBrowserInfo(),
            $_SERVER['HTTP_REFERER'] ?? '',
            $this->consentVersion
        ]);
    }

    /**
     * Estadísticas para el panel de administración
     */
    public function getConsentStats(string $period = 'month'): array
    {
        $dateCondition = match ($period) {
            'today'  => "DATE(created_at) = CURDATE()",
            'week'   => "created_at >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)",
            'month'  => "created_at >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)",
            'year'   => "created_at >= DATE_SUB(CURDATE(), INTERVAL 1 YEAR)",
            default  => "1=1"
        };

        $stmt = $this->pdo->query("
            SELECT
                COUNT(*) AS total_interacciones,
                SUM(CASE WHEN action = 'accept_all'       THEN 1 ELSE 0 END) AS aceptar_todo,
                SUM(CASE WHEN action = 'reject_all'       THEN 1 ELSE 0 END) AS rechazar_todo,
                SUM(CASE WHEN action = 'save_preferences' THEN 1 ELSE 0 END) AS personalizado,
                ROUND(AVG(analytics)  * 100, 1) AS pct_analytics,
                ROUND(AVG(functional) * 100, 1) AS pct_functional
            FROM cookie_consent_logs
            WHERE {$dateCondition}
        ");

        return $stmt->fetch(PDO::FETCH_ASSOC) ?: [];
    }

    /**
     * Historial paginado con filtros opcionales
     */
    public function getConsentHistory(int $page = 1, int $perPage = 25, array $filters = []): array
    {
        $where  = ['1=1'];
        $params = [];

        if (!empty($filters['action'])) {
            $where[]  = 'action = ?';
            $params[] = $filters['action'];
        }
        if (!empty($filters['date_from'])) {
            $where[]  = 'DATE(created_at) >= ?';
            $params[] = $filters['date_from'];
        }
        if (!empty($filters['date_to'])) {
            $where[]  = 'DATE(created_at) <= ?';
            $params[] = $filters['date_to'];
        }

        $whereSQL = implode(' AND ', $where);

        $countStmt = $this->pdo->prepare("SELECT COUNT(*) FROM cookie_consent_logs WHERE {$whereSQL}");
        $countStmt->execute($params);
        $total = (int)$countStmt->fetchColumn();

        $totalPages = max(1, (int)ceil($total / $perPage));
        $offset     = ($page - 1) * $perPage;

        $stmt = $this->pdo->prepare("
            SELECT id, session_token, action, necessary, analytics, functional,
                   ip_address, user_agent, page_url, consent_version, created_at
            FROM cookie_consent_logs
            WHERE {$whereSQL}
            ORDER BY created_at DESC
            LIMIT {$perPage} OFFSET {$offset}
        ");
        $stmt->execute($params);

        return [
            'data'        => $stmt->fetchAll(PDO::FETCH_ASSOC),
            'total'       => $total,
            'page'        => $page,
            'per_page'    => $perPage,
            'total_pages' => $totalPages,
        ];
    }

    /**
     * Exporta registros a CSV (BOM UTF-8, separador punto y coma)
     */
    public function exportToCSV(array $filters = []): string
    {
        $where  = ['1=1'];
        $params = [];

        if (!empty($filters['date_from'])) {
            $where[]  = 'DATE(created_at) >= ?';
            $params[] = $filters['date_from'];
        }
        if (!empty($filters['date_to'])) {
            $where[]  = 'DATE(created_at) <= ?';
            $params[] = $filters['date_to'];
        }

        $whereSQL = implode(' AND ', $where);
        $stmt = $this->pdo->prepare("
            SELECT created_at, session_token, action, necessary, analytics, functional,
                   ip_address, page_url, consent_version
            FROM cookie_consent_logs
            WHERE {$whereSQL}
            ORDER BY created_at DESC
        ");
        $stmt->execute($params);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $actionLabels = [
            'accept_all'       => 'Aceptar todo',
            'reject_all'       => 'Solo necesarias',
            'save_preferences' => 'Personalizado',
            'withdraw'         => 'Retirar consentimiento',
        ];

        $csv  = "\xEF\xBB\xBF"; // BOM UTF-8 para Excel
        $csv .= "Fecha;Token sesión;Acción;Necesarias;Analíticas;Funcionales;IP;Página;Versión\n";

        foreach ($rows as $row) {
            $csv .= date('d/m/Y H:i:s', strtotime($row['created_at'])) . ';';
            $csv .= ($row['session_token'] ?? '-') . ';';
            $csv .= ($actionLabels[$row['action']] ?? $row['action']) . ';';
            $csv .= ($row['necessary']  ? 'Sí' : 'No') . ';';
            $csv .= ($row['analytics']  ? 'Sí' : 'No') . ';';
            $csv .= ($row['functional'] ? 'Sí' : 'No') . ';';
            $csv .= ($row['ip_address'] ?? '-') . ';';
            $csv .= ($row['page_url']   ?? '-') . ';';
            $csv .= ($row['consent_version'] ?? '-') . "\n";
        }

        return $csv;
    }

    /**
     * Purga automática: elimina registros con más de 13 meses (RGPD art. 5.1.e)
     * Solo se ejecuta ~1% de las veces para no penalizar cada request
     */
    private function purgeOldRecords(): void
    {
        if (rand(1, 100) !== 1) return;
        $this->pdo->exec("
            DELETE FROM cookie_consent_logs
            WHERE created_at < DATE_SUB(NOW(), INTERVAL 13 MONTH)
        ");
    }

    /**
     * IP anonimizada: elimina el último octeto en IPv4, últimos 80 bits en IPv6
     * La IP anonimizada NO es dato personal según RGPD (consideración GT29)
     */
    private function getAnonymizedIp(): string
    {
        $ip = $this->getRawIp();

        // IPv4: 123.45.67.89 → 123.45.67.x
        if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
            $parts    = explode('.', $ip);
            $parts[3] = 'x';
            return implode('.', $parts);
        }

        // IPv6: elimina los últimos 80 bits (5 grupos)
        if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)) {
            $parts = explode(':', inet_ntop(inet_pton($ip)));
            for ($i = 3; $i < 8; $i++) {
                $parts[$i] = '0';
            }
            return implode(':', $parts);
        }

        return '0.0.0.x';
    }

    /**
     * Solo tipo de navegador y SO (no el user-agent completo)
     */
    private function getBrowserInfo(): string
    {
        $ua = $_SERVER['HTTP_USER_AGENT'] ?? '';
        if (empty($ua)) return 'Desconocido';

        $browser = 'Otro';
        if (str_contains($ua, 'Edg'))     $browser = 'Edge';
        elseif (str_contains($ua, 'OPR')) $browser = 'Opera';
        elseif (str_contains($ua, 'Chrome')) $browser = 'Chrome';
        elseif (str_contains($ua, 'Firefox')) $browser = 'Firefox';
        elseif (str_contains($ua, 'Safari'))  $browser = 'Safari';

        $os = 'Otro';
        if (str_contains($ua, 'Windows')) $os = 'Windows';
        elseif (str_contains($ua, 'Mac'))     $os = 'Mac';
        elseif (str_contains($ua, 'Android')) $os = 'Android';
        elseif (str_contains($ua, 'iPhone') || str_contains($ua, 'iPad')) $os = 'iOS';
        elseif (str_contains($ua, 'Linux'))   $os = 'Linux';

        return "{$browser} / {$os}";
    }

    /**
     * IP real del cliente (soporta Cloudflare y proxies)
     */
    private function getRawIp(): string
    {
        $headers = [
            'HTTP_CF_CONNECTING_IP',
            'HTTP_X_FORWARDED_FOR',
            'HTTP_X_FORWARDED',
            'HTTP_FORWARDED_FOR',
            'HTTP_FORWARDED',
            'REMOTE_ADDR',
        ];

        foreach ($headers as $header) {
            if (!empty($_SERVER[$header])) {
                $ip = trim(explode(',', $_SERVER[$header])[0]);
                if (filter_var($ip, FILTER_VALIDATE_IP)) {
                    return $ip;
                }
            }
        }

        return '0.0.0.0';
    }
}
