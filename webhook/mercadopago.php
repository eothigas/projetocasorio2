<?php
require_once dirname(__DIR__) . '/config/config.php';
require_once ROOT_DIR . '/includes/db.php';
require_once ROOT_DIR . '/includes/mp-config.php';

/**
 * Loga a notificação (sempre, mesmo em erro) pra depuração posterior.
 */
function logWebhook(PDO $db, ?string $paymentId, ?string $tipo, string $payload, string $resultado): void
{
    try {
        $db->prepare(
            "INSERT INTO webhook_logs (mp_payment_id, tipo, payload, resultado, criado_em) VALUES (?, ?, ?, ?, NOW())"
        )->execute([$paymentId, $tipo, substr($payload, 0, 60000), $resultado]);
    } catch (Exception $e) {
        error_log('[webhook mp] falha ao logar: ' . $e->getMessage());
    }
}

function mpGetPayment(string $paymentId): array
{
    $ch = curl_init('https://api.mercadopago.com/v1/payments/' . urlencode($paymentId));
    curl_setopt_array($ch, [
        CURLOPT_HTTPHEADER     => ['Authorization: Bearer ' . getMpAccessToken()],
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT        => 15,
    ]);
    $raw = curl_exec($ch);
    if ($raw === false) {
        $err = curl_error($ch);
        curl_close($ch);
        throw new RuntimeException('Falha ao consultar pagamento: ' . $err);
    }
    $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    if ($status < 200 || $status >= 300) {
        throw new RuntimeException('Consulta de pagamento retornou ' . $status);
    }
    return json_decode($raw, true) ?? [];
}

/**
 * Valida a assinatura do webhook (header x-signature) conforme doc oficial
 * do Mercado Pago: HMAC-SHA256 de um "manifest" fixo, usando a chave secreta
 * de webhook configurada no painel (MP_WEBHOOK_SECRET).
 * https://www.mercadopago.com.br/developers/pt/docs/checkout-api/webhooks
 */
function validarAssinatura(string $dataId, ?string $xSignature, ?string $xRequestId): bool
{
    $webhookSecret = getMpWebhookSecret();
    if ($webhookSecret === '') {
        // Sem chave configurada (ex.: ambiente local de teste) — não há como validar.
        return true;
    }
    if (!$xSignature || !$xRequestId) {
        return false;
    }

    $ts = null;
    $v1 = null;
    foreach (explode(',', $xSignature) as $part) {
        [$k, $v] = array_pad(explode('=', trim($part), 2), 2, null);
        if ($k === 'ts') $ts = $v;
        if ($k === 'v1') $v1 = $v;
    }
    if (!$ts || !$v1) {
        return false;
    }

    $manifest = "id:{$dataId};request-id:{$xRequestId};ts:{$ts};";
    $esperado = hash_hmac('sha256', $manifest, $webhookSecret);

    return hash_equals($esperado, $v1);
}

$rawBody = file_get_contents('php://input');
$body    = json_decode($rawBody, true) ?? [];

$db = getDB();

// id do pagamento pode vir por query string (?data.id=...&type=payment) ou no corpo
$dataId = $_GET['data_id'] ?? ($body['data']['id'] ?? null);
$tipo   = $_GET['type']    ?? ($body['type']        ?? null);

// Sempre responde 200 rápido — Mercado Pago reenvia se não receber 2xx,
// então erros de negócio são só logados, nunca retornados como falha HTTP.
http_response_code(200);
header('Content-Type: application/json; charset=utf-8');

if ($tipo !== 'payment' || !$dataId) {
    echo json_encode(['ignored' => true]);
    exit;
}

$xSignature = $_SERVER['HTTP_X_SIGNATURE']  ?? null;
$xRequestId = $_SERVER['HTTP_X_REQUEST_ID'] ?? null;

if (!validarAssinatura((string) $dataId, $xSignature, $xRequestId)) {
    logWebhook($db, (string) $dataId, $tipo, $rawBody, 'assinatura_invalida');
    echo json_encode(['ok' => true]); // 200 mesmo assim, mas não processa nada
    exit;
}

try {
    // Nunca confia no payload da notificação — busca o status real na API
    $payment = mpGetPayment((string) $dataId);
} catch (RuntimeException $e) {
    logWebhook($db, (string) $dataId, $tipo, $rawBody, 'erro_consulta: ' . $e->getMessage());
    echo json_encode(['ok' => true]);
    exit;
}

$status             = $payment['status'] ?? null;
$externalReference  = $payment['external_reference'] ?? null;

if ($status !== 'approved' || !$externalReference) {
    logWebhook($db, (string) $dataId, $tipo, $rawBody, 'status=' . ($status ?? 'null') . ' (não processado)');
    echo json_encode(['ok' => true]);
    exit;
}

try {
    $db->beginTransaction();

    $stmt = $db->prepare("SELECT * FROM reservas WHERE external_reference = ? FOR UPDATE");
    $stmt->execute([$externalReference]);
    $reserva = $stmt->fetch();

    if (!$reserva) {
        $db->rollBack();
        logWebhook($db, (string) $dataId, $tipo, $rawBody, 'reserva_nao_encontrada: ' . $externalReference);
        echo json_encode(['ok' => true]);
        exit;
    }

    // Idempotência: só marca como pago se ainda não estava — reprocessar a
    // mesma notificação (ou notificações duplicadas do MP) não deve
    // incrementar valor_arrecadado nem sobrescrever pago_em de novo.
    $stmt = $db->prepare(
        "UPDATE reservas SET status = 'pago', pago_em = NOW(), mp_payment_id = ?
         WHERE id = ? AND status != 'pago'"
    );
    $stmt->execute([(string) $dataId, $reserva['id']]);

    if ($stmt->rowCount() > 0) {
        $stmtP = $db->prepare("SELECT tipo FROM presentes WHERE id = ? FOR UPDATE");
        $stmtP->execute([$reserva['presente_id']]);
        $presente = $stmtP->fetch();

        if ($presente && $presente['tipo'] === 'cota') {
            $db->prepare("UPDATE presentes SET valor_arrecadado = valor_arrecadado + ? WHERE id = ?")
               ->execute([$reserva['valor'], $reserva['presente_id']]);
        } else {
            $db->prepare(
                "UPDATE presentes SET comprado = 1, comprado_por = ?, comprado_em = NOW() WHERE id = ?"
            )->execute([$reserva['nome_convidado'], $reserva['presente_id']]);
        }
        $resultado = 'pago_confirmado';
    } else {
        $resultado = 'ja_processado (idempotente)';
    }

    $db->commit();
    logWebhook($db, (string) $dataId, $tipo, $rawBody, $resultado);
    echo json_encode(['ok' => true]);

} catch (Exception $e) {
    if ($db->inTransaction()) {
        $db->rollBack();
    }
    logWebhook($db, (string) $dataId, $tipo, $rawBody, 'erro: ' . $e->getMessage());
    echo json_encode(['ok' => true]);
}
