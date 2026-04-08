<?php
header('Content-Type: application/json; charset=utf-8');

require_once dirname(__DIR__) . '/config/config.php';
require_once ROOT_DIR . '/includes/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Método não permitido.']);
    exit;
}

$body = json_decode(file_get_contents('php://input'), true);
if (!$body) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Dados inválidos.']);
    exit;
}

$id   = (int) ($body['id']   ?? 0);
$nome = trim(strip_tags($body['nome'] ?? ''));

if ($id <= 0) {
    echo json_encode(['success' => false, 'message' => 'Presente inválido.']);
    exit;
}
if (strlen($nome) < 3) {
    echo json_encode(['success' => false, 'message' => 'Por favor, informe seu nome completo.']);
    exit;
}

try {
    $db = getDB();

    // Verifica se o presente existe e está disponível
    $stmt = $db->prepare("SELECT id, nome, comprado FROM presentes WHERE id = ? FOR UPDATE");
    $stmt->execute([$id]);
    $presente = $stmt->fetch();

    if (!$presente) {
        echo json_encode(['success' => false, 'message' => 'Presente não encontrado.']);
        exit;
    }
    if ($presente['comprado']) {
        echo json_encode(['success' => false, 'message' => 'Este presente já foi escolhido por outra pessoa. Escolha outro!']);
        exit;
    }

    // Marca como comprado
    $upd = $db->prepare(
        "UPDATE presentes SET comprado = 1, comprado_por = ?, comprado_em = NOW() WHERE id = ?"
    );
    $upd->execute([$nome, $id]);

    echo json_encode([
        'success' => true,
        'message' => 'Ótima escolha! Obrigado, ' . htmlspecialchars($nome) . '. Os noivos vão adorar! 🎁',
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Erro interno. Tente novamente.']);
}
