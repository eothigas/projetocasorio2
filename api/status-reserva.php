<?php
header('Content-Type: application/json; charset=utf-8');

require_once dirname(__DIR__) . '/config/config.php';
require_once ROOT_DIR . '/includes/db.php';

$ref = trim($_GET['ref'] ?? '');
if ($ref === '' || !preg_match('/^reserva_\d+$/', $ref)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Referência inválida.']);
    exit;
}

try {
    $db = getDB();
    $stmt = $db->prepare("SELECT status FROM reservas WHERE external_reference = ?");
    $stmt->execute([$ref]);
    $reserva = $stmt->fetch();

    if (!$reserva) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Reserva não encontrada.']);
        exit;
    }

    echo json_encode(['success' => true, 'status' => $reserva['status']]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Erro interno.']);
}
