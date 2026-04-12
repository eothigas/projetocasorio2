<?php
require_once dirname(__DIR__) . '/config/config.php';
require_once ROOT_DIR . '/includes/db.php';

session_start();
if (!($_SESSION['admin_ok'] ?? false)) {
    header('Location: ' . BASE_URL . '/admin/login');
    exit;
}

// Ações
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $acao = $_POST['acao'] ?? '';
    $id   = (int) ($_POST['id'] ?? 0);
    try {
        $db = getDB();
        if ($acao === 'aprovar' && $id > 0) {
            $db->prepare("UPDATE mensagens SET aprovado = 1 WHERE id = ?")->execute([$id]);
        } elseif ($acao === 'rejeitar' && $id > 0) {
            $db->prepare("DELETE FROM mensagens WHERE id = ?")->execute([$id]);
        }
    } catch (Exception $e) {}
    header('Location: ' . BASE_URL . '/admin/mensagens');
    exit;
}

try {
    $db   = getDB();
    $msgs = $db->query("SELECT * FROM mensagens ORDER BY aprovado ASC, criado_em DESC")->fetchAll();
    $totalConf = (int) $db->query("SELECT COUNT(*) FROM convidados WHERE confirmado = 1")->fetchColumn();
    $totalPres     = (int) $db->query("SELECT COUNT(*) FROM presentes")->fetchColumn();
    $totalEscolhas = (int) $db->query("SELECT COUNT(*) FROM presentes_escolhas")->fetchColumn();
    $msgPend   = (int) $db->query("SELECT COUNT(*) FROM mensagens WHERE aprovado = 0")->fetchColumn();
} catch (Exception $e) {
    $msgs = [];
    $totalConf = $totalPres = $totalEscolhas = $msgPend = 0;
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mensagens · Admin</title>
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
        <?= NOIVA ?> &amp; <?= NOIVO ?> · Admin
    </div>
    <div style="display:flex;gap:16px;align-items:center;">
        <a href="<?= BASE_URL ?>/" target="_blank"><i class="bi bi-eye"></i> Ver site</a>
        <a href="<?= BASE_URL ?>/admin/logout"><i class="bi bi-box-arrow-right"></i> Sair</a>
    </div>
</div>

<div class="admin-main">

    <h2 style="font-family:var(--font-serif);color:var(--blue2);margin-bottom:24px;">Mensagens dos Convidados</h2>

    <!-- Nav tabs -->
    <div class="admin-nav">
        <a href="<?= BASE_URL ?>/admin/index" class="admin-tab">
            <i class="bi bi-people"></i> Confirmações (<?= $totalConf ?>)
        </a>
        <a href="<?= BASE_URL ?>/admin/convidados" class="admin-tab">
            <i class="bi bi-person-lines-fill"></i> Convidados
        </a>
        <a href="<?= BASE_URL ?>/admin/presentes" class="admin-tab">
            <i class="bi bi-gift"></i> Presentes (<?= $totalPres ?>) · <?= $totalEscolhas ?> escolha<?= $totalEscolhas !== 1 ? 's' : '' ?>
        </a>
        <a href="<?= BASE_URL ?>/admin/mensagens" class="admin-tab active">
            <i class="bi bi-chat-heart"></i> Mensagens
            <?php if ($msgPend > 0): ?>
            <span class="badge-pend"><?= $msgPend ?> pendente<?= $msgPend > 1 ? 's' : '' ?></span>
            <?php endif; ?>
        </a>
    </div>

    <div class="admin-table-wrap">
        <?php if (empty($msgs)): ?>
        <div style="padding:40px;text-align:center;color:var(--blue4);">
            <i class="bi bi-chat-heart" style="font-size:2rem;display:block;margin-bottom:8px;"></i>
            Nenhuma mensagem ainda.
        </div>
        <?php else: ?>
        <table class="admin-table">
            <thead>
                <tr>
                    <th>De</th>
                    <th>Mensagem</th>
                    <th>Data</th>
                    <th>Status</th>
                    <th>Ações</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($msgs as $m): ?>
                <tr>
                    <td><strong><?= htmlspecialchars($m['nome']) ?></strong></td>
                    <td style="max-width:360px;font-size:.85rem;color:var(--blue2);">
                        <?= htmlspecialchars($m['mensagem']) ?>
                    </td>
                    <td style="font-size:.8rem;white-space:nowrap;color:var(--blue4);">
                        <?= (new DateTime($m['criado_em']))->format('d/m/Y H:i') ?>
                    </td>
                    <td>
                        <?php if ($m['aprovado']): ?>
                        <span class="badge-ok"><i class="bi bi-check-circle-fill me-1"></i>Aprovada</span>
                        <?php else: ?>
                        <span class="badge-pend"><i class="bi bi-clock me-1"></i>Pendente</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <div style="display:flex;gap:6px;flex-wrap:wrap;">
                            <?php if (!$m['aprovado']): ?>
                            <form method="POST" style="display:inline;">
                                <input type="hidden" name="acao" value="aprovar">
                                <input type="hidden" name="id" value="<?= $m['id'] ?>">
                                <button type="submit" class="btn-action btn-action--approve">
                                    <i class="bi bi-check-circle"></i> Aprovar
                                </button>
                            </form>
                            <?php endif; ?>
                            <form method="POST" style="display:inline;"
                                  onsubmit="return confirm('Excluir esta mensagem?')">
                                <input type="hidden" name="acao" value="rejeitar">
                                <input type="hidden" name="id" value="<?= $m['id'] ?>">
                                <button type="submit" class="btn-action btn-action--delete">
                                    <i class="bi bi-trash"></i> Excluir
                                </button>
                            </form>
                        </div>
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
