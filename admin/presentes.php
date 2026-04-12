<?php
require_once dirname(__DIR__) . '/config/config.php';
require_once ROOT_DIR . '/includes/db.php';

session_start();
if (!($_SESSION['admin_ok'] ?? false)) {
    header('Location: ' . BASE_URL . '/admin/login');
    exit;
}

// Ações POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $acao = $_POST['acao'] ?? '';
    $id   = (int) ($_POST['id'] ?? 0);
    try {
        $db = getDB();
        if ($acao === 'excluir' && $id > 0) {
            $db->prepare("DELETE FROM presentes WHERE id = ?")->execute([$id]);
        } elseif ($acao === 'excluir_escolha' && $id > 0) {
            $db->prepare("DELETE FROM presentes_escolhas WHERE id = ?")->execute([$id]);
        } elseif ($acao === 'adicionar') {
            $nome      = trim(strip_tags($_POST['nome']      ?? ''));
            $descricao = trim(strip_tags($_POST['descricao'] ?? ''));
            $categoria = trim(strip_tags($_POST['categoria'] ?? 'Geral'));
            $preco     = (float) str_replace(',', '.', $_POST['preco'] ?? '0');
            $link      = trim($_POST['link'] ?? '');
            if ($nome) {
                $db->prepare(
                    "INSERT INTO presentes (nome, descricao, categoria, preco, link) VALUES (?, ?, ?, ?, ?)"
                )->execute([$nome, $descricao ?: null, $categoria, $preco ?: null, $link ?: null]);
            }
        }
    } catch (Exception $e) {}
    header('Location: ' . BASE_URL . '/admin/presentes');
    exit;
}

try {
    $db        = getDB();
    $presentes = $db->query("SELECT * FROM presentes ORDER BY categoria ASC, nome ASC")->fetchAll();
    $cats      = $db->query("SELECT DISTINCT categoria FROM presentes ORDER BY categoria")->fetchAll(PDO::FETCH_COLUMN);
    $totalConf = (int) $db->query("SELECT COUNT(*) FROM convidados WHERE confirmado = 1")->fetchColumn();
    $totalPres = count($presentes);
    $msgPend   = (int) $db->query("SELECT COUNT(*) FROM mensagens WHERE aprovado = 0")->fetchColumn();

    // Escolhas por presente
    $escolhasRaw = $db->query(
        "SELECT pe.id, pe.presente_id, pe.nome, pe.criado_em FROM presentes_escolhas pe ORDER BY pe.presente_id, pe.criado_em ASC"
    )->fetchAll();
    $escolhasPorPresente = [];
    foreach ($escolhasRaw as $e) {
        $escolhasPorPresente[$e['presente_id']][] = $e;
    }
    $totalEscolhas = count($escolhasRaw);
} catch (Exception $e) {
    $presentes = $cats = $escolhasRaw = [];
    $escolhasPorPresente = [];
    $totalConf = $totalPres = $msgPend = $totalEscolhas = 0;
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Presentes · Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600&family=Playfair+Display:wght@500&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= BASE_URL ?>/src/css/estilo-padrão.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>/src/css/paginas/admin.css">
</head>
<body class="admin-body">

<!-- Topbar -->
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

    <h2 style="font-family:var(--font-serif);color:var(--blue2);margin-bottom:24px;">Lista de Presentes</h2>

    <!-- Nav tabs -->
    <div class="admin-nav">
        <a href="<?= BASE_URL ?>/admin/index" class="admin-tab">
            <i class="bi bi-people"></i> Confirmações (<?= $totalConf ?>)
        </a>
        <a href="<?= BASE_URL ?>/admin/convidados" class="admin-tab">
            <i class="bi bi-person-lines-fill"></i> Convidados
        </a>
        <a href="<?= BASE_URL ?>/admin/presentes" class="admin-tab active">
            <i class="bi bi-gift"></i> Presentes (<?= $totalPres ?>) · <?= $totalEscolhas ?> escolha<?= $totalEscolhas !== 1 ? 's' : '' ?>
        </a>
        <a href="<?= BASE_URL ?>/admin/mensagens" class="admin-tab">
            <i class="bi bi-chat-heart"></i> Mensagens
            <?php if ($msgPend > 0): ?>
            <span class="badge-pend"><?= $msgPend ?> pendente<?= $msgPend > 1 ? 's' : '' ?></span>
            <?php endif; ?>
        </a>
    </div>

    <!-- Formulário: adicionar presente -->
    <div class="form-add-present">
        <div class="form-add-present-header">
            <i class="bi bi-plus-circle-fill"></i>
            <h4>Adicionar Presente</h4>
        </div>
        <div class="form-add-present-body">
        <form method="POST">
            <input type="hidden" name="acao" value="adicionar">
            <div class="form-grid">
                <div class="form-group-custom">
                    <label>Nome do presente *</label>
                    <input type="text" name="nome" class="input-custom" placeholder="Ex.: Jogo de panelas" required maxlength="200">
                </div>
                <div class="form-group-custom">
                    <label>Categoria</label>
                    <input type="text" name="categoria" class="input-custom"
                           placeholder="Ex.: Cozinha"
                           list="cats-list" maxlength="80">
                    <datalist id="cats-list">
                        <?php foreach ($cats as $c): ?>
                        <option value="<?= htmlspecialchars($c) ?>">
                        <?php endforeach; ?>
                    </datalist>
                </div>
                <div class="form-group-custom">
                    <label>Preço estimado (R$)</label>
                    <input type="text" name="preco" class="input-custom" placeholder="0,00" maxlength="12">
                </div>
            </div>
            <div class="form-grid-2">
                <div class="form-group-custom">
                    <label>Link da loja (opcional)</label>
                    <input type="url" name="link" class="input-custom" placeholder="https://..." maxlength="500">
                </div>
                <div class="form-group-custom">
                    <label>Descrição (opcional)</label>
                    <input type="text" name="descricao" class="input-custom" placeholder="Detalhes..." maxlength="300">
                </div>
            </div>
            <button type="submit" class="btn-primary-custom btn-add-present">
                <i class="bi bi-plus-lg"></i> Adicionar
            </button>
        </form>
        </div>
    </div>

    <!-- Tabela de presentes -->
    <div class="admin-table-wrap">
        <?php if (empty($presentes)): ?>
        <div style="padding:40px;text-align:center;color:var(--blue4);">
            <i class="bi bi-gift" style="font-size:2rem;display:block;margin-bottom:8px;"></i>
            Nenhum presente cadastrado.
        </div>
        <?php else: ?>
        <table class="admin-table">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Nome</th>
                    <th>Categoria</th>
                    <th>Preço</th>
                    <th>Quem vai dar</th>
                    <th>Ações</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($presentes as $i => $p): ?>
                <?php $escolhas = $escolhasPorPresente[$p['id']] ?? []; ?>
                <tr>
                    <td style="color:var(--blue4);"><?= $i + 1 ?></td>
                    <td>
                        <strong><?= htmlspecialchars($p['nome']) ?></strong>
                        <?php if ($p['descricao']): ?>
                        <div style="font-size:.78rem;color:var(--blue4);"><?= htmlspecialchars($p['descricao']) ?></div>
                        <?php endif; ?>
                    </td>
                    <td style="font-size:.82rem;"><?= htmlspecialchars($p['categoria']) ?></td>
                    <td style="font-size:.88rem;font-weight:600;white-space:nowrap;">
                        <?= $p['preco'] ? 'R$ ' . number_format($p['preco'], 2, ',', '.') : '—' ?>
                    </td>
                    <td style="font-size:.82rem;">
                        <?php if (empty($escolhas)): ?>
                            <span style="color:var(--blue4);">—</span>
                        <?php else: ?>
                            <?php foreach ($escolhas as $e): ?>
                            <div style="display:flex;align-items:center;gap:6px;margin-bottom:4px;">
                                <span class="badge-ok" style="white-space:nowrap;">
                                    <i class="bi bi-person-heart me-1"></i><?= htmlspecialchars($e['nome']) ?>
                                </span>
                                <span style="color:var(--blue4);font-size:.72rem;white-space:nowrap;">
                                    <?= (new DateTime($e['criado_em']))->format('d/m/Y') ?>
                                </span>
                                <form method="POST" style="display:inline;"
                                      onsubmit="return confirm('Remover este registro?')">
                                    <input type="hidden" name="acao" value="excluir_escolha">
                                    <input type="hidden" name="id" value="<?= $e['id'] ?>">
                                    <button type="submit" class="btn-action btn-action--delete"
                                            style="padding:2px 7px;font-size:.7rem;" title="Remover">
                                        <i class="bi bi-x"></i>
                                    </button>
                                </form>
                            </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </td>
                    <td>
                        <div style="display:flex;gap:6px;flex-wrap:wrap;">
                            <?php if ($p['link'] && $p['link'] !== '#'): ?>
                            <a href="<?= htmlspecialchars($p['link']) ?>" target="_blank"
                               class="btn-action" style="border:1px solid var(--whiteblue3);color:var(--blue3);">
                                <i class="bi bi-box-arrow-up-right"></i>
                            </a>
                            <?php endif; ?>
                            <form method="POST" style="display:inline;"
                                  onsubmit="return confirm('Excluir este presente da lista?')">
                                <input type="hidden" name="acao" value="excluir">
                                <input type="hidden" name="id" value="<?= $p['id'] ?>">
                                <button type="submit" class="btn-action btn-action--delete">
                                    <i class="bi bi-trash"></i>
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
