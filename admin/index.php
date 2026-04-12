<?php
require_once dirname(__DIR__) . '/config/config.php';
require_once ROOT_DIR . '/includes/db.php';

session_start();
if (!($_SESSION['admin_ok'] ?? false)) {
    header('Location: ' . BASE_URL . '/admin/login');
    exit;
}

try {
    $db = getDB();

    $totalConf     = (int) $db->query("SELECT COUNT(*) FROM convidados WHERE confirmado = 1")->fetchColumn();
    $totalConv     = (int) $db->query("SELECT COUNT(*) FROM convidados")->fetchColumn();
    $totalPres     = (int) $db->query("SELECT COUNT(*) FROM presentes")->fetchColumn();
    $totalEscolhas = (int) $db->query("SELECT COUNT(*) FROM presentes_escolhas")->fetchColumn();
    $msgPend       = (int) $db->query("SELECT COUNT(*) FROM mensagens WHERE aprovado = 0")->fetchColumn();
    $msgAprov      = (int) $db->query("SELECT COUNT(*) FROM mensagens WHERE aprovado = 1")->fetchColumn();

    $confirmacoes = $db->query(
        "SELECT id, nome, codigo, confirmado_em FROM convidados WHERE confirmado = 1 ORDER BY confirmado_em DESC"
    )->fetchAll();

} catch (Exception $e) {
    $totalConf = $totalConv = $totalPres = $totalEscolhas = $msgPend = $msgAprov = 0;
    $confirmacoes = [];
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
    <link href="https://fonts.googleapis.com/css2?family=Great+Vibes&family=Poppins:wght@400;500;600;700&family=Playfair+Display:wght@500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= BASE_URL ?>/src/css/estilo-padrão.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>/src/css/paginas/admin.css">
</head>
<body class="admin-body">

<!-- Topbar -->
<div class="admin-topbar">
    <div class="admin-topbar-brand">
        <i class="bi bi-heart-fill"></i>
        <?= NOIVA ?> &amp; <?= NOIVO ?> · Admin
    </div>
    <div style="display:flex;gap:16px;align-items:center;">
        <a href="<?= BASE_URL ?>/" target="_blank">
            <i class="bi bi-eye"></i> Ver site
        </a>
        <a href="<?= BASE_URL ?>/admin/logout">
            <i class="bi bi-box-arrow-right"></i> Sair
        </a>
    </div>
</div>

<div class="admin-main">

    <h2 style="font-family:var(--font-serif);color:var(--blue2);margin-bottom:8px;">Painel</h2>
    <p style="color:var(--blue4);font-size:.88rem;margin-bottom:24px;"><?= DATA_BR ?> · <?= DIA_SEMANA ?></p>

    <!-- Stats -->
    <div class="admin-stats">
        <div class="stat-card">
            <div class="stat-icon stat-icon--blue"><i class="bi bi-person-lines-fill"></i></div>
            <div>
                <span class="stat-num"><?= $totalConv ?></span>
                <span class="stat-label">Total de convidados</span>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon stat-icon--green"><i class="bi bi-check-circle-fill"></i></div>
            <div>
                <span class="stat-num"><?= $totalConf ?></span>
                <span class="stat-label">Confirmações recebidas</span>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon stat-icon--blue"><i class="bi bi-gift-fill"></i></div>
            <div>
                <span class="stat-num"><?= $totalEscolhas ?></span>
                <span class="stat-label">Escolhas de presentes</span>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon stat-icon--yellow"><i class="bi bi-chat-heart-fill"></i></div>
            <div>
                <span class="stat-num"><?= $msgPend ?></span>
                <span class="stat-label">Mensagens pendentes</span>
            </div>
        </div>
    </div>

    <!-- Nav tabs -->
    <div class="admin-nav">
        <a href="<?= BASE_URL ?>/admin/index"
           class="admin-tab active">
            <i class="bi bi-people"></i> Confirmações (<?= $totalConf ?>)
        </a>
        <a href="<?= BASE_URL ?>/admin/convidados"
           class="admin-tab">
            <i class="bi bi-person-lines-fill"></i> Convidados (<?= $totalConv ?>)
        </a>
        <a href="<?= BASE_URL ?>/admin/presentes"
           class="admin-tab">
            <i class="bi bi-gift"></i> Presentes (<?= $totalPres ?>) · <?= $totalEscolhas ?> escolha<?= $totalEscolhas !== 1 ? 's' : '' ?>
        </a>
        <a href="<?= BASE_URL ?>/admin/mensagens"
           class="admin-tab">
            <i class="bi bi-chat-heart"></i> Mensagens
            <?php if ($msgPend > 0): ?>
            <span class="badge-pend"><?= $msgPend ?> pendente<?= $msgPend > 1 ? 's' : '' ?></span>
            <?php endif; ?>
        </a>
    </div>

    <!-- Tabela de confirmações -->
    <div class="admin-table-wrap">
        <?php if (empty($confirmacoes)): ?>
        <div style="padding:40px;text-align:center;color:var(--blue4);">
            <i class="bi bi-inbox" style="font-size:2rem;display:block;margin-bottom:8px;"></i>
            Nenhuma confirmação recebida ainda.
        </div>
        <?php else: ?>
        <table class="admin-table">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Nome</th>
                    <th>Código</th>
                    <th>Confirmado em</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($confirmacoes as $i => $c): ?>
                <tr>
                    <td style="color:var(--blue4);"><?= $i + 1 ?></td>
                    <td><strong><?= htmlspecialchars($c['nome']) ?></strong></td>
                    <td style="font-family:monospace;font-size:.88rem;letter-spacing:.08em;color:var(--blue3);">
                        <?= htmlspecialchars($c['codigo']) ?>
                    </td>
                    <td style="font-size:.82rem;white-space:nowrap;color:var(--blue4);">
                        <?= (new DateTime($c['confirmado_em']))->format('d/m/Y H:i') ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php endif; ?>
    </div>

</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
