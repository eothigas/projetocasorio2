<?php
require_once dirname(__DIR__) . '/config/config.php';
session_start();

if ($_SESSION['admin_ok'] ?? false) {
    header('Location: ' . BASE_URL . '/admin/index');
    exit;
}

$erro = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $senha = $_POST['senha'] ?? '';
    if (hash_equals(ADMIN_PASS, $senha)) {
        $_SESSION['admin_ok'] = true;
        header('Location: ' . BASE_URL . '/admin/index');
        exit;
    }
    $erro = 'Senha incorreta.';
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin · <?= NOIVA ?> &amp; <?= NOIVO ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Great+Vibes&family=Poppins:wght@400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= BASE_URL ?>/src/css/estilo-padrão.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>/src/css/paginas/admin.css">
</head>
<body class="admin-body">
<div class="admin-login-wrap">
    <div class="admin-login-card">
        <div class="admin-login-card-header">
            <h1><?= NOIVA ?> &amp; <?= NOIVO ?></h1>
            <p>Área Administrativa</p>
        </div>
        <div class="admin-login-card-body">
            <?php if ($erro): ?>
            <div class="alert-error-custom" style="margin-bottom:18px;"><?= htmlspecialchars($erro) ?></div>
            <?php endif; ?>

            <form method="POST">
                <div class="form-group-custom">
                    <label for="senha">Senha de acesso</label>
                    <input type="password" id="senha" name="senha"
                           class="input-custom" placeholder="••••••••"
                           autofocus required>
                </div>
                <button type="submit" class="btn-primary-custom admin-login-btn">
                    <i class="bi bi-lock-fill"></i> Entrar
                </button>
            </form>

            <a href="<?= BASE_URL ?>/" class="d-block mt-4 text-center" style="font-size:.8rem;color:var(--blue4);">
                <i class="bi bi-arrow-left me-1"></i>Voltar ao site
            </a>
        </div>
    </div>
</div>
</body>
</html>
