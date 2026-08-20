<?php
header('Content-Type: application/json; charset=utf-8');

require_once dirname(__DIR__) . '/config/config.php';
require_once ROOT_DIR . '/includes/db.php';

session_start();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Método não permitido.']);
    exit;
}

// Rate limit simples por sessão: 1 pedido a cada 5s
$agora = time();
if (($_SESSION['ultimo_confirmar'] ?? 0) > $agora - 5) {
    http_response_code(429);
    echo json_encode(['success' => false, 'message' => 'Aguarde um instante antes de tentar novamente.']);
    exit;
}
$_SESSION['ultimo_confirmar'] = $agora;

$body = json_decode(file_get_contents('php://input'), true);
if (!$body) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Dados inválidos.']);
    exit;
}

$presenteId = (int) ($body['presente_id'] ?? 0);
$nome       = trim(strip_tags($body['nome']   ?? ''));
$email      = trim(strip_tags($body['email']  ?? ''));
$metodo     = in_array($body['metodo'] ?? '', ['loja', 'manual'], true) ? $body['metodo'] : 'manual';
$valorCota  = isset($body['valor']) ? (float) $body['valor'] : null;

if ($presenteId <= 0) {
    echo json_encode(['success' => false, 'message' => 'Presente inválido.']);
    exit;
}
if (strlen($nome) < 3) {
    echo json_encode(['success' => false, 'message' => 'Por favor, informe seu nome completo.']);
    exit;
}
if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode(['success' => false, 'message' => 'E-mail inválido.']);
    exit;
}

try {
    $db = getDB();
    $db->beginTransaction();

    $stmt = $db->prepare("SELECT * FROM presentes WHERE id = ? FOR UPDATE");
    $stmt->execute([$presenteId]);
    $presente = $stmt->fetch();

    if (!$presente) {
        $db->rollBack();
        echo json_encode(['success' => false, 'message' => 'Presente não encontrado.']);
        exit;
    }

    if ($presente['tipo'] === 'cota') {
        $restante = (float) $presente['preco'] - (float) $presente['valor_arrecadado'];
        if ($restante <= 0) {
            $db->rollBack();
            echo json_encode(['success' => false, 'message' => 'Este presente já foi totalmente arrecadado. Obrigado!']);
            exit;
        }
        $valor = $valorCota && $valorCota > 0 ? min($valorCota, $restante) : $restante;
    } else {
        if ((int) $presente['comprado'] === 1) {
            $db->rollBack();
            echo json_encode(['success' => false, 'message' => 'Este presente já foi dado por outra pessoa.']);
            exit;
        }
        $stmt = $db->prepare(
            "SELECT id FROM reservas
             WHERE presente_id = ? AND (status = 'pago' OR (status = 'pendente' AND expira_em > NOW()))
             LIMIT 1"
        );
        $stmt->execute([$presenteId]);
        if ($stmt->fetch()) {
            $db->rollBack();
            echo json_encode(['success' => false, 'message' => 'Este presente já está reservado por outra pessoa.']);
            exit;
        }
        $valor = (float) $presente['preco'];
    }

    if ($valor <= 0) {
        $db->rollBack();
        echo json_encode(['success' => false, 'message' => 'Valor inválido para este presente.']);
        exit;
    }

    $db->prepare(
        "INSERT INTO reservas (presente_id, nome_convidado, email_convidado, valor, status, metodo, external_reference, criado_em, pago_em)
         VALUES (?, ?, ?, ?, 'pago', ?, ?, NOW(), NOW())"
    )->execute([
        $presenteId,
        $nome,
        $email ?: null,
        $valor,
        $metodo,
        uniqid('tmp_', true),
    ]);
    $reservaId = (int) $db->lastInsertId();
    $db->prepare("UPDATE reservas SET external_reference = ? WHERE id = ?")
       ->execute(['manual_' . $reservaId, $reservaId]);

    if ($presente['tipo'] === 'cota') {
        $db->prepare("UPDATE presentes SET valor_arrecadado = valor_arrecadado + ? WHERE id = ?")
           ->execute([$valor, $presenteId]);
    } else {
        $db->prepare(
            "UPDATE presentes SET comprado = 1, comprado_por = ?, comprado_em = NOW() WHERE id = ?"
        )->execute([$nome, $presenteId]);
    }

    $db->commit();
    echo json_encode(['success' => true]);

} catch (Exception $e) {
    if ($db->inTransaction()) {
        $db->rollBack();
    }
    error_log('[confirmar-manual] ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Erro interno. Tente novamente.']);
}
