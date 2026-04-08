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

// Sanitização
$nome          = trim(strip_tags($body['nome']          ?? ''));
$email         = trim(filter_var($body['email'] ?? '', FILTER_SANITIZE_EMAIL));
$telefone      = trim(strip_tags($body['telefone']      ?? ''));
$acompanhantes = max(0, (int) ($body['acompanhantes']   ?? 0));
$restricoes    = trim(strip_tags($body['restricoes']    ?? ''));
$mensagem      = trim(strip_tags($body['mensagem']      ?? ''));

// Validação
if (strlen($nome) < 3) {
    echo json_encode(['success' => false, 'message' => 'Por favor, informe seu nome completo.']);
    exit;
}
if ($email && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode(['success' => false, 'message' => 'E-mail inválido.']);
    exit;
}
if ($acompanhantes < 0 || $acompanhantes > 10) {
    echo json_encode(['success' => false, 'message' => 'Número de acompanhantes inválido.']);
    exit;
}

try {
    $db = getDB();

    // Impede confirmação dupla pelo mesmo nome (tolerante a capitalização)
    $dup = $db->prepare("SELECT id FROM confirmacoes WHERE LOWER(nome) = LOWER(?) LIMIT 1");
    $dup->execute([$nome]);
    if ($dup->fetch()) {
        echo json_encode(['success' => false, 'message' => 'Já existe uma confirmação com esse nome. Se precisar alterar, entre em contato com os noivos.']);
        exit;
    }

    $stmt = $db->prepare(
        "INSERT INTO confirmacoes (nome, email, telefone, acompanhantes, restricoes, mensagem)
         VALUES (?, ?, ?, ?, ?, ?)"
    );
    $stmt->execute([$nome, $email ?: null, $telefone ?: null, $acompanhantes, $restricoes ?: null, $mensagem ?: null]);

    $totalPax = (int) $db->query("SELECT COALESCE(SUM(acompanhantes + 1), 0) FROM confirmacoes")->fetchColumn();

    echo json_encode([
        'success'   => true,
        'message'   => 'Presença confirmada com sucesso! Mal podemos esperar para celebrar com você. 💙',
        'total_pax' => $totalPax,
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Erro interno. Tente novamente em instantes.']);
}
