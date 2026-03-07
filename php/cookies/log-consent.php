<?php
/**
 * Endpoint API para registrar consentimiento de cookies
 * Fisiopilates Atlas - RGPD/LSSI-CE
 */

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: ' . ($_SERVER['HTTP_ORIGIN'] ?? '*'));
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
header('Access-Control-Allow-Credentials: true');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Método no permitido']);
    exit;
}

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/CookieConsentService.php';

try {
    $data = json_decode(file_get_contents('php://input'), true);

    if (!$data) {
        throw new Exception('Datos de entrada inválidos');
    }

    $validActions = ['accept_all', 'reject_all', 'save_preferences', 'withdraw'];
    $action = $data['action'] ?? '';
    if (!in_array($action, $validActions)) {
        throw new Exception('Acción no válida');
    }

    $service = new CookieConsentService(getDB());
    $result  = $service->logConsent(
        $data['session_token'] ?? null,
        $action,
        (bool)($data['analytics']  ?? false),
        (bool)($data['functional'] ?? false)
    );

    echo json_encode([
        'success' => $result,
        'message' => $result ? 'Preferencias registradas' : 'Error al guardar',
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
