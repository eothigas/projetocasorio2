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

$nome     = trim(strip_tags($body['nome']     ?? ''));
$mensagem = trim(strip_tags($body['mensagem'] ?? ''));

if (strlen($nome) < 2) {
    echo json_encode(['success' => false, 'message' => 'Por favor, informe seu nome.']);
    exit;
}
if (strlen($mensagem) < 5) {
    echo json_encode(['success' => false, 'message' => 'A mensagem é muito curta. Escreva com carinho!']);
    exit;
}
if (strlen($mensagem) > 800) {
    echo json_encode(['success' => false, 'message' => 'A mensagem é muito longa (máximo 800 caracteres).']);
    exit;
}

try {
    $db   = getDB();
    $stmt = $db->prepare("INSERT INTO mensagens (nome, mensagem) VALUES (?, ?)");
    $stmt->execute([$nome, $mensagem]);

    echo json_encode([
        'success' => true,
        'message' => 'Mensagem enviada com sucesso! Ela aparecerá no mural após aprovação. 💌',
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Erro interno. Tente novamente.']);
}
