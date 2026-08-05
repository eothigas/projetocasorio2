<?php
require_once dirname(__DIR__) . '/config/config.php';
require_once ROOT_DIR . '/includes/db.php';

session_start();
if (!($_SESSION['admin_ok'] ?? false)) {
    header('Location: ' . BASE_URL . '/admin/login');
    exit;
}

function gerarCodigo(): string {
    return strtoupper(substr(bin2hex(random_bytes(5)), 0, 8));
}

// Ações POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $acao = $_POST['acao'] ?? '';
    try {
        $db = getDB();
        if ($acao === 'criar_grupo') {
            $nomeResp  = trim(strip_tags($_POST['nome_responsavel'] ?? ''));
            $nomeGrupo = trim(strip_tags($_POST['nome_grupo'] ?? ''));
            if ($nomeGrupo === '') {
                $nomeGrupo = $nomeResp;
            }
            if (strlen($nomeResp) >= 2) {
                $db->prepare("INSERT INTO grupos (nome_grupo) VALUES (?)")->execute([$nomeGrupo]);
                $grupoId = (int) $db->lastInsertId();
                $db->prepare("INSERT INTO convidados (nome, grupo_id, responsavel, codigo) VALUES (?, ?, 1, ?)")
                   ->execute([$nomeResp, $grupoId, gerarCodigo()]);
            }
        } elseif ($acao === 'add_dependente') {
            $grupoId = (int) ($_POST['grupo_id'] ?? 0);
            $nome    = trim(strip_tags($_POST['nome'] ?? ''));
            if ($grupoId > 0 && strlen($nome) >= 2) {
                $db->prepare("INSERT INTO convidados (nome, grupo_id, responsavel, codigo) VALUES (?, ?, 0, ?)")
                   ->execute([$nome, $grupoId, gerarCodigo()]);
            }
        } elseif ($acao === 'excluir_membro') {
            $id = (int) ($_POST['id'] ?? 0);
            if ($id > 0) {
                $db->prepare("DELETE FROM convidados WHERE id = ? AND responsavel = 0")->execute([$id]);
            }
        } elseif ($acao === 'excluir_grupo') {
            $grupoId = (int) ($_POST['grupo_id'] ?? 0);
            if ($grupoId > 0) {
                $db->prepare("DELETE FROM grupos WHERE id = ?")->execute([$grupoId]);
            }
        } elseif ($acao === 'toggle') {
            $atual = $db->query("SELECT valor FROM configuracoes WHERE chave = 'confirmacao_aberta'")->fetchColumn();
            $novo  = ($atual === '1') ? '0' : '1';
            $db->prepare("UPDATE configuracoes SET valor = ? WHERE chave = 'confirmacao_aberta'")->execute([$novo]);
        }
    } catch (Exception $e) {}
    header('Location: ' . BASE_URL . '/admin/convidados');
    exit;
}

try {
    $db = getDB();

    $grupos = $db->query("SELECT * FROM grupos ORDER BY criado_em DESC")->fetchAll();
    $membrosPorGrupo = [];
    $membrosStmt = $db->query("SELECT * FROM convidados ORDER BY responsavel DESC, id ASC");
    foreach ($membrosStmt->fetchAll() as $m) {
        $membrosPorGrupo[$m['grupo_id']][] = $m;
    }

    $totalConv = (int) $db->query("SELECT COUNT(*) FROM convidados")->fetchColumn();
    $totalConf = (int) $db->query("SELECT COUNT(*) FROM convidados WHERE confirmado = 1")->fetchColumn();
    $totalPend = $totalConv - $totalConf;
    $confirmAberta = $db->query("SELECT valor FROM configuracoes WHERE chave = 'confirmacao_aberta'")->fetchColumn();

    $totalPres     = (int) $db->query("SELECT COUNT(*) FROM presentes")->fetchColumn();
    $totalEscolhas = (int) $db->query("SELECT COUNT(*) FROM presentes_escolhas")->fetchColumn();
    $msgPend       = (int) $db->query("SELECT COUNT(*) FROM mensagens WHERE aprovado = 0")->fetchColumn();
} catch (Exception $e) {
    $grupos = [];
    $membrosPorGrupo = [];
    $totalConv = $totalConf = $totalPend = $totalPres = $totalEscolhas = $msgPend = 0;
    $confirmAberta = '0';
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Convidados · Admin</title>
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

    <h2 style="font-family:var(--font-serif);color:var(--blue2);margin-bottom:24px;">Lista de Convidados</h2>

    <!-- Nav tabs -->
    <div class="admin-nav">
        <a href="<?= BASE_URL ?>/admin/index" class="admin-tab">
            <i class="bi bi-people"></i> Confirmações (<?= $totalConf ?>)
        </a>
        <a href="<?= BASE_URL ?>/admin/convidados" class="admin-tab active">
            <i class="bi bi-person-lines-fill"></i> Convidados (<?= $totalConv ?>)
        </a>
        <a href="<?= BASE_URL ?>/admin/presentes" class="admin-tab">
            <i class="bi bi-gift"></i> Presentes (<?= $totalPres ?>) · <?= $totalEscolhas ?> escolha<?= $totalEscolhas !== 1 ? 's' : '' ?>
        </a>
        <a href="<?= BASE_URL ?>/admin/mensagens" class="admin-tab">
            <i class="bi bi-chat-heart"></i> Mensagens
            <?php if ($msgPend > 0): ?>
            <span class="badge-pend"><?= $msgPend ?> pendente<?= $msgPend > 1 ? 's' : '' ?></span>
            <?php endif; ?>
        </a>
    </div>

    <!-- Status da confirmação + toggle -->
    <div class="toggle-wrap">
        <?php if ($confirmAberta === '1'): ?>
        <div class="toggle-status toggle-status--open">
            <i class="bi bi-unlock-fill"></i> Confirmações abertas ao público
        </div>
        <form method="POST" style="display:inline;">
            <input type="hidden" name="acao" value="toggle">
            <button type="submit" class="btn-toggle-close"
                    onclick="return confirm('Fechar as confirmações? Os convidados não conseguirão confirmar enquanto estiver fechado.')">
                <i class="bi bi-lock-fill"></i> Fechar confirmações
            </button>
        </form>
        <?php else: ?>
        <div class="toggle-status toggle-status--closed">
            <i class="bi bi-lock-fill"></i> Confirmações fechadas
        </div>
        <form method="POST" style="display:inline;">
            <input type="hidden" name="acao" value="toggle">
            <button type="submit" class="btn-toggle-open">
                <i class="bi bi-unlock-fill"></i> Abrir confirmações
            </button>
        </form>
        <?php endif; ?>
    </div>

    <!-- Mini stats -->
    <div class="stat-mini">
        <div class="stat-mini-item">
            <span class="stat-mini-num"><?= $totalConv ?></span>
            <span class="stat-mini-label">Convidados</span>
        </div>
        <div class="stat-mini-item">
            <span class="stat-mini-num" style="color:#1a7a58;"><?= $totalConf ?></span>
            <span class="stat-mini-label">Confirmados</span>
        </div>
        <div class="stat-mini-item">
            <span class="stat-mini-num" style="color:var(--blue4);"><?= $totalPend ?></span>
            <span class="stat-mini-label">Pendentes</span>
        </div>
    </div>

    <!-- Formulário: criar grupo -->
    <div class="form-add-present" style="margin-bottom:24px;">
        <div class="form-add-present-header">
            <i class="bi bi-person-plus-fill"></i>
            <h4>Novo Grupo / Convidado</h4>
        </div>
        <div class="form-add-present-body">
            <form method="POST" style="display:flex;gap:12px;align-items:flex-end;flex-wrap:wrap;">
                <input type="hidden" name="acao" value="criar_grupo">
                <div class="form-group-custom" style="flex:1;min-width:200px;margin-bottom:0;">
                    <label>Nome do responsável *</label>
                    <input type="text" name="nome_responsavel" class="input-custom"
                           placeholder="Quem recebe o convite" required maxlength="150">
                </div>
                <div class="form-group-custom" style="flex:1;min-width:200px;margin-bottom:0;">
                    <label>Nome do grupo (opcional)</label>
                    <input type="text" name="nome_grupo" class="input-custom"
                           placeholder="Ex: Família Silva" maxlength="150">
                </div>
                <button type="submit" class="btn-primary-custom" style="white-space:nowrap;">
                    <i class="bi bi-plus-lg"></i> Criar grupo
                </button>
            </form>
        </div>
    </div>

    <!-- Grupos -->
    <?php if (empty($grupos)): ?>
    <div class="admin-table-wrap">
        <div style="padding:40px;text-align:center;color:var(--blue4);">
            <i class="bi bi-person-lines-fill" style="font-size:2rem;display:block;margin-bottom:8px;"></i>
            Nenhum grupo cadastrado ainda.
        </div>
    </div>
    <?php else: ?>
    <?php foreach ($grupos as $g): $membros = $membrosPorGrupo[$g['id']] ?? []; ?>
    <div class="grupo-card">
        <div class="grupo-card-header">
            <div>
                <strong><?= htmlspecialchars($g['nome_grupo']) ?></strong>
                <?php if ($g['respondido']): ?>
                <span class="badge-ok"><i class="bi bi-check-circle-fill me-1"></i>Respondido</span>
                <?php else: ?>
                <span class="badge-pend"><i class="bi bi-clock me-1"></i>Aguardando</span>
                <?php endif; ?>
            </div>
            <form method="POST" style="display:inline;"
                  onsubmit="return confirm('Excluir o grupo <?= htmlspecialchars(addslashes($g['nome_grupo'])) ?> e todos os seus integrantes?')">
                <input type="hidden" name="acao" value="excluir_grupo">
                <input type="hidden" name="grupo_id" value="<?= $g['id'] ?>">
                <button type="submit" class="btn-action btn-action--delete">
                    <i class="bi bi-trash"></i> Excluir grupo
                </button>
            </form>
        </div>

        <table class="admin-table">
            <thead>
                <tr>
                    <th>Nome</th>
                    <th>Papel</th>
                    <th>Status</th>
                    <th>Confirmado em</th>
                    <th>Ações</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($membros as $m): ?>
                <tr>
                    <td><strong><?= htmlspecialchars($m['nome']) ?></strong></td>
                    <td><?= $m['responsavel'] ? '<span class="badge-pend">Responsável</span>' : 'Dependente' ?></td>
                    <td>
                        <?php if (!$g['respondido']): ?>
                        <span class="badge-pend"><i class="bi bi-clock me-1"></i>Aguardando</span>
                        <?php elseif ($m['confirmado']): ?>
                        <span class="badge-ok"><i class="bi bi-check-circle-fill me-1"></i>Vai comparecer</span>
                        <?php else: ?>
                        <span class="badge-pend"><i class="bi bi-x-circle me-1"></i>Não vai</span>
                        <?php endif; ?>
                    </td>
                    <td style="font-size:.8rem;white-space:nowrap;color:var(--blue4);">
                        <?= $m['confirmado_em']
                            ? (new DateTime($m['confirmado_em']))->format('d/m/Y H:i')
                            : '—' ?>
                    </td>
                    <td>
                        <?php if (!$m['responsavel']): ?>
                        <form method="POST" style="display:inline;"
                              onsubmit="return confirm('Excluir o dependente <?= htmlspecialchars(addslashes($m['nome'])) ?>?')">
                            <input type="hidden" name="acao" value="excluir_membro">
                            <input type="hidden" name="id" value="<?= $m['id'] ?>">
                            <button type="submit" class="btn-action btn-action--delete">
                                <i class="bi bi-trash"></i>
                            </button>
                        </form>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <form method="POST" class="grupo-add-dependente">
            <input type="hidden" name="acao" value="add_dependente">
            <input type="hidden" name="grupo_id" value="<?= $g['id'] ?>">
            <input type="text" name="nome" class="input-custom" placeholder="Nome do dependente" required maxlength="150">
            <button type="submit" class="btn-primary-custom">
                <i class="bi bi-plus-lg"></i> Adicionar dependente
            </button>
        </form>
    </div>
    <?php endforeach; ?>
    <?php endif; ?>

</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
