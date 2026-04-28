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

$nome = trim(strip_tags($body['nome'] ?? ''));

if (strlen($nome) < 2) {
    echo json_encode(['success' => false, 'message' => 'Por favor, informe seu nome.']);
    exit;
}

try {
    $db = getDB();

    // Verifica se as confirmações estão abertas
    $aberta = $db->query("SELECT valor FROM configuracoes WHERE chave = 'confirmacao_aberta'")->fetchColumn();
    if ($aberta !== '1') {
        echo json_encode(['success' => false, 'message' => 'As confirmações ainda não estão abertas. Aguarde a divulgação dos noivos.']);
        exit;
    }

    // Busca convidado pelo nome (tolerante a capitalização e espaços extras)
    $nomeNormalizado = mb_strtolower(preg_replace('/\s+/', ' ', $nome));

    $stmt = $db->query("SELECT id, nome, confirmado FROM convidados");
    $convidado = null;
    foreach ($stmt->fetchAll() as $c) {
        $nomeDB = mb_strtolower(preg_replace('/\s+/', ' ', $c['nome']));
        if ($nomeDB === $nomeNormalizado) {
            $convidado = $c;
            break;
        }
    }

    if (!$convidado) {
        echo json_encode(['success' => false, 'message' => 'Nome não encontrado na lista de convidados. Verifique e tente novamente.']);
        exit;
    }

    if ($convidado['confirmado']) {
        echo json_encode(['success' => false, 'message' => 'Sua presença já foi confirmada anteriormente. Até lá! 💙']);
        exit;
    }

    // Confirma presença
    $db->prepare(
        "UPDATE convidados SET confirmado = 1, confirmado_em = NOW() WHERE id = ?"
    )->execute([$convidado['id']]);

    $totalConf = (int) $db->query("SELECT COUNT(*) FROM convidados WHERE confirmado = 1")->fetchColumn();

    echo json_encode([
        'success'    => true,
        'message'    => 'Presença confirmada com sucesso! Mal podemos esperar para celebrar com você. 💙',
        'total_conf' => $totalConf,
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Erro interno. Tente novamente em instantes.']);
}
