<?php
header('Content-Type: application/json; charset=utf-8');

require_once dirname(__DIR__) . '/config/config.php';
require_once ROOT_DIR . '/includes/db.php';
require_once ROOT_DIR . '/includes/mp-config.php';

session_start();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Método não permitido.']);
    exit;
}

// Rate limit simples por sessão: 1 pedido de Pix a cada 5s
$agora = time();
if (($_SESSION['ultimo_pix'] ?? 0) > $agora - 5) {
    http_response_code(429);
    echo json_encode(['success' => false, 'message' => 'Aguarde um instante antes de tentar novamente.']);
    exit;
}
$_SESSION['ultimo_pix'] = $agora;

$body = json_decode(file_get_contents('php://input'), true);
if (!$body) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Dados inválidos.']);
    exit;
}

$presenteId = (int) ($body['presente_id'] ?? 0);
$nome       = trim(strip_tags($body['nome']  ?? ''));
$email      = trim(strip_tags($body['email'] ?? ''));
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
if (getMpAccessToken() === '') {
    error_log('[gerar-pix] MP_ACCESS_TOKEN não configurado em /admin/configuracoes');
    http_response_code(503);
    echo json_encode(['success' => false, 'message' => 'Pagamento via Pix indisponível no momento. Tente outra forma de presentear.']);
    exit;
}

/**
 * Chama a API do Mercado Pago. Lança Exception em caso de erro de rede
 * ou resposta HTTP fora da faixa 2xx.
 */
function mpRequest(string $method, string $path, ?array $body = null, ?string $idempotencyKey = null): array
{
    $ch = curl_init('https://api.mercadopago.com' . $path);
    $headers = [
        'Authorization: Bearer ' . getMpAccessToken(),
        'Content-Type: application/json',
    ];
    if ($idempotencyKey) {
        $headers[] = 'X-Idempotency-Key: ' . $idempotencyKey;
    }
    curl_setopt_array($ch, [
        CURLOPT_CUSTOMREQUEST  => $method,
        CURLOPT_HTTPHEADER     => $headers,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT        => 15,
    ]);
    if ($body !== null) {
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($body, JSON_PRESERVE_ZERO_FRACTION));
    }
    $raw = curl_exec($ch);
    if ($raw === false) {
        $err = curl_error($ch);
        curl_close($ch);
        throw new RuntimeException('Falha de conexão com Mercado Pago: ' . $err);
    }
    $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    $data = json_decode($raw, true) ?? [];
    if ($status < 200 || $status >= 300) {
        throw new RuntimeException('Mercado Pago retornou erro ' . $status . ': ' . $raw);
    }
    return $data;
}

try {
    $db = getDB();

    $stmt = $db->prepare("SELECT * FROM presentes WHERE id = ?");
    $stmt->execute([$presenteId]);
    $presente = $stmt->fetch();

    if (!$presente) {
        echo json_encode(['success' => false, 'message' => 'Presente não encontrado.']);
        exit;
    }

    if ($presente['tipo'] === 'cota') {
        $restante = (float) $presente['preco'] - (float) $presente['valor_arrecadado'];
        if ($restante <= 0) {
            echo json_encode(['success' => false, 'message' => 'Este presente já foi totalmente arrecadado. Obrigado!']);
            exit;
        }
        $valor = $valorCota && $valorCota > 0 ? min($valorCota, $restante) : $restante;
    } else {
        // Presente único: recusa se já há reserva paga ou pendente ainda válida
        $stmt = $db->prepare(
            "SELECT id FROM reservas
             WHERE presente_id = ? AND (status = 'pago' OR (status = 'pendente' AND expira_em > NOW()))
             LIMIT 1"
        );
        $stmt->execute([$presenteId]);
        if ($stmt->fetch()) {
            echo json_encode(['success' => false, 'message' => 'Este presente já está reservado por outra pessoa.']);
            exit;
        }
        $valor = (float) $presente['preco'];
    }

    if ($valor <= 0) {
        echo json_encode(['success' => false, 'message' => 'Valor inválido para este presente.']);
        exit;
    }

    $expiraEm = new DateTime('+30 minutes');

    // Cria a reserva pendente; external_reference definitivo usa o próprio id
    $db->prepare(
        "INSERT INTO reservas (presente_id, nome_convidado, email_convidado, valor, status, external_reference, criado_em, expira_em)
         VALUES (?, ?, ?, ?, 'pendente', ?, NOW(), ?)"
    )->execute([
        $presenteId,
        $nome,
        $email ?: null,
        $valor,
        uniqid('tmp_', true),
        $expiraEm->format('Y-m-d H:i:s'),
    ]);
    $reservaId = (int) $db->lastInsertId();
    $externalReference = 'reserva_' . $reservaId;
    $db->prepare("UPDATE reservas SET external_reference = ? WHERE id = ?")
       ->execute([$externalReference, $reservaId]);

    // MP rejeita TLDs não roteáveis tipo .local — usa domínio real como placeholder
    $payerEmail = $email ?: ('convidado' . $reservaId . '@example.com');

    try {
        $mp = mpRequest('POST', '/v1/payments', [
            'transaction_amount'  => round($valor, 2),
            'description'         => 'Presente: ' . $presente['nome'],
            'payment_method_id'   => 'pix',
            'external_reference'  => $externalReference,
            'date_of_expiration'  => $expiraEm->format('Y-m-d\TH:i:s.000P'),
            'payer'                => [
                'email'      => $payerEmail,
                'first_name' => $nome,
            ],
        ], $externalReference);
    } catch (RuntimeException $e) {
        // Não deixa reserva órfã pendente se o MP recusou a cobrança
        $db->prepare("UPDATE reservas SET status = 'cancelado' WHERE id = ?")->execute([$reservaId]);
        error_log('[gerar-pix] ' . $e->getMessage());
        http_response_code(502);
        echo json_encode(['success' => false, 'message' => 'Não foi possível gerar o Pix agora. Tente novamente em instantes.']);
        exit;
    }

    $txData  = $mp['point_of_interaction']['transaction_data'] ?? [];
    $qrCode  = $txData['qr_code']        ?? null;
    $qrImg   = $txData['qr_code_base64'] ?? null;
    $mpDateExp = $mp['date_of_expiration'] ?? null;

    if ($mpDateExp) {
        $expiraEm = new DateTime($mpDateExp);
    }

    $db->prepare(
        "UPDATE reservas SET mp_payment_id = ?, pix_qr_code = ?, pix_qr_code_base64 = ?, expira_em = ? WHERE id = ?"
    )->execute([
        (string) ($mp['id'] ?? ''),
        $qrCode,
        $qrImg,
        $expiraEm->format('Y-m-d H:i:s'),
        $reservaId,
    ]);

    echo json_encode([
        'success'            => true,
        'reserva_id'         => $reservaId,
        'external_reference' => $externalReference,
        'valor'              => $valor,
        'pix_qr_code'        => $qrCode,
        'pix_qr_code_base64' => $qrImg,
        'expira_em'          => $expiraEm->format(DateTime::ATOM),
    ]);

} catch (Exception $e) {
    error_log('[gerar-pix] ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Erro interno. Tente novamente.']);
}
