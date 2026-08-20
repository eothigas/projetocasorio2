<?php
require_once dirname(__DIR__) . '/config/config.php';
require_once ROOT_DIR . '/includes/db.php';
require_once ROOT_DIR . '/includes/mp-config.php';

session_start();
if (!($_SESSION['admin_ok'] ?? false)) {
    header('Location: ' . BASE_URL . '/admin/login');
    exit;
}

$salvo = false;
$erro  = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $novoToken  = trim($_POST['mp_access_token']  ?? '');
    $novoWebhook = trim($_POST['mp_webhook_secret'] ?? '');
    try {
        if ($novoToken !== '') {
            setConfiguracaoSegura('mp_access_token', $novoToken);
        }
        if ($novoWebhook !== '') {
            setConfiguracaoSegura('mp_webhook_secret', $novoWebhook);
        }
        $salvo = true;
    } catch (Exception $e) {
        $erro = 'Erro ao salvar credenciais.';
    }
}

function mascarar(string $valor): string
{
    if ($valor === '') {
        return '';
    }
    $tam = strlen($valor);
    return $tam <= 4 ? str_repeat('•', $tam) : str_repeat('•', $tam - 4) . substr($valor, -4);
}

$tokenAtual   = getMpAccessToken();
$webhookAtual = getMpWebhookSecret();
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Configurações · Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600&family=Playfair+Display:wght@500&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= BASE_URL ?>/src/css/estilo-padrão.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>/src/css/paginas/admin.css">
</head>
<body class="admin-body">

<div class="admin-topbar">
    <div class="admin-topbar-brand">
        <i class="bi bi-heart-fill"></i>
        <?= NOIVA ?> &amp; <?= NOIVO ?><span class="brand-label"> · Admin</span>
    </div>
    <div class="admin-topbar-actions">
        <a href="<?= BASE_URL ?>/" target="_blank"><i class="bi bi-eye"></i> Ver site</a>
        <a href="<?= BASE_URL ?>/admin/logout"><i class="bi bi-box-arrow-right"></i> Sair</a>
    </div>
</div>

<div class="admin-main">

    <h2 style="font-family:var(--font-serif);color:var(--blue2);margin-bottom:20px;">Configurações</h2>

    <div class="admin-layout">
    <div class="admin-content">

    <div class="admin-nav">
        <a href="<?= BASE_URL ?>/admin/index" class="admin-tab"><i class="bi bi-people"></i> Confirmações</a>
        <a href="<?= BASE_URL ?>/admin/convidados" class="admin-tab"><i class="bi bi-person-lines-fill"></i> Convidados</a>
        <a href="<?= BASE_URL ?>/admin/presentes" class="admin-tab"><i class="bi bi-gift"></i> Presentes</a>
        <a href="<?= BASE_URL ?>/admin/mensagens" class="admin-tab"><i class="bi bi-chat-heart"></i> Mensagens</a>
        <a href="<?= BASE_URL ?>/admin/configuracoes" class="admin-tab active"><i class="bi bi-gear"></i> Configurações</a>
    </div>

    <?php if ($salvo): ?>
    <div class="alert-success-custom" style="margin-bottom:20px;"><i class="bi bi-check-circle-fill me-2"></i>Credenciais salvas (criptografadas no banco).</div>
    <?php endif; ?>
    <?php if ($erro): ?>
    <div class="alert-error-custom" style="margin-bottom:20px;"><i class="bi bi-exclamation-triangle-fill me-2"></i><?= htmlspecialchars($erro) ?></div>
    <?php endif; ?>

    <div class="form-add-present">
        <div class="form-add-present-header">
            <i class="bi bi-shield-lock-fill"></i>
            <h4>Credenciais Mercado Pago (Pix)</h4>
        </div>
        <div class="form-add-present-body">
            <form method="POST">
                <div class="admin-form-section">
                    <h5 class="admin-form-section-title"><i class="bi bi-key-fill"></i> Access Token</h5>
                    <div class="form-group-custom">
                        <input type="password" name="mp_access_token" class="input-custom"
                               placeholder="<?= $tokenAtual ? 'Atual: ' . mascarar($tokenAtual) : 'Nenhum token configurado' ?>"
                               autocomplete="off" maxlength="255">
                    </div>
                </div>
                <div class="admin-form-section">
                    <h5 class="admin-form-section-title"><i class="bi bi-shield-lock-fill"></i> Chave secreta do Webhook</h5>
                    <div class="form-group-custom">
                        <input type="password" name="mp_webhook_secret" class="input-custom"
                               placeholder="<?= $webhookAtual ? 'Atual: ' . mascarar($webhookAtual) : 'Nenhuma chave configurada' ?>"
                               autocomplete="off" maxlength="255">
                    </div>
                </div>
                <p style="font-size:.78rem;color:var(--blue4);margin-bottom:16px;">
                    <i class="bi bi-lock-fill me-1"></i>Criptografadas com AES-256-GCM antes de ir pro banco.
                    Deixe um campo em branco pra manter o valor atual.
                </p>
                <button type="submit" class="btn-primary-custom btn-add-present">
                    <i class="bi bi-save"></i> Salvar credenciais
                </button>
            </form>
        </div>
    </div>

    </div><!-- /.admin-content -->

    <aside class="admin-guide">
        <div class="admin-guide-header">
            <i class="bi bi-lightbulb-fill"></i>
            <h4>Guia rápido</h4>
        </div>
        <div class="admin-guide-body">
            <p>Cole o Access Token e a chave secreta do webhook do Mercado Pago pra habilitar
            pagamentos via Pix no site.</p>
            <ol class="admin-guide-steps">
                <li>No painel MP: Suas integrações → sua aplicação → Credenciais.</li>
                <li>Cole o Access Token de produção (ou teste) aqui.</li>
                <li>A chave do webhook valida notificações de pagamento — opcional, mas recomendada.</li>
                <li>Sem token, "Presentear via Pix" fica indisponível — Visitar loja e Confirmar presente continuam funcionando.</li>
            </ol>
        </div>
    </aside>
    </div><!-- /.admin-layout -->

</div>
</body>
</html>
