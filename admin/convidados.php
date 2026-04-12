<?php
require_once dirname(__DIR__) . '/config/config.php';
require_once ROOT_DIR . '/includes/db.php';

session_start();
if (!($_SESSION['admin_ok'] ?? false)) {
    header('Location: ' . BASE_URL . '/admin/login');
    exit;
}

function gerarCodigo(PDO $db): string {
    $chars = 'ABCDEFGHJKMNPQRSTUVWXYZ23456789'; // sem caracteres ambíguos
    do {
        $code = '';
        for ($i = 0; $i < 6; $i++) {
            $code .= $chars[random_int(0, strlen($chars) - 1)];
        }
        $existe = $db->prepare("SELECT id FROM convidados WHERE codigo = ? LIMIT 1");
        $existe->execute([$code]);
    } while ($existe->fetch());
    return $code;
}

// Ações POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $acao = $_POST['acao'] ?? '';
    try {
        $db = getDB();
        if ($acao === 'adicionar') {
            $nome = trim(strip_tags($_POST['nome'] ?? ''));
            if (strlen($nome) >= 2) {
                $codigo = gerarCodigo($db);
                $db->prepare("INSERT INTO convidados (nome, codigo) VALUES (?, ?)")
                   ->execute([$nome, $codigo]);
            }
        } elseif ($acao === 'excluir') {
            $id = (int) ($_POST['id'] ?? 0);
            if ($id > 0) {
                $db->prepare("DELETE FROM convidados WHERE id = ?")->execute([$id]);
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
    $db         = getDB();
    $convidados = $db->query("SELECT * FROM convidados ORDER BY criado_em DESC")->fetchAll();
    $totalConv  = count($convidados);
    $totalConf  = (int) $db->query("SELECT COUNT(*) FROM convidados WHERE confirmado = 1")->fetchColumn();
    $totalPend  = $totalConv - $totalConf;
    $confirmAberta = $db->query("SELECT valor FROM configuracoes WHERE chave = 'confirmacao_aberta'")->fetchColumn();

    $totalPres     = (int) $db->query("SELECT COUNT(*) FROM presentes")->fetchColumn();
    $totalEscolhas = (int) $db->query("SELECT COUNT(*) FROM presentes_escolhas")->fetchColumn();
    $msgPend       = (int) $db->query("SELECT COUNT(*) FROM mensagens WHERE aprovado = 0")->fetchColumn();
} catch (Exception $e) {
    $convidados = [];
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
    <style>
        .toggle-status {
            display: inline-flex;
            align-items: center;
            gap: 10px;
            padding: 12px 20px;
            border-radius: 10px;
            font-size: .88rem;
            font-weight: 600;
            margin-bottom: 24px;
        }
        .toggle-status--open {
            background: rgba(34,139,100,.1);
            border: 1.5px solid rgba(34,139,100,.3);
            color: #1a7a58;
        }
        .toggle-status--closed {
            background: rgba(200,60,60,.08);
            border: 1.5px solid rgba(200,60,60,.22);
            color: #a03030;
        }
        .toggle-status i { font-size: 1.1rem; }
        .btn-toggle-open {
            background: linear-gradient(135deg, #228b64, #1a6e4f);
            color: #fff;
            border: none;
            border-radius: 8px;
            padding: 8px 20px;
            font-size: .85rem;
            font-weight: 600;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 7px;
        }
        .btn-toggle-open:hover { opacity: .88; }
        .btn-toggle-close {
            background: rgba(180,50,50,.1);
            color: #a03030;
            border: 1.5px solid rgba(180,50,50,.25);
            border-radius: 8px;
            padding: 8px 20px;
            font-size: .85rem;
            font-weight: 600;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 7px;
        }
        .btn-toggle-close:hover { background: rgba(180,50,50,.17); }
        .code-badge {
            font-family: monospace;
            font-size: .95rem;
            font-weight: 700;
            letter-spacing: .12em;
            background: var(--whiteblue5);
            color: var(--blue2);
            border: 1px solid var(--whiteblue3);
            border-radius: 6px;
            padding: 3px 10px;
        }
        .stat-mini { display: flex; gap: 20px; margin-bottom: 20px; flex-wrap: wrap; }
        .stat-mini-item {
            background: var(--white);
            border: 1px solid var(--whiteblue4);
            border-radius: 10px;
            padding: 12px 20px;
            text-align: center;
            min-width: 100px;
        }
        .stat-mini-num { display: block; font-size: 1.5rem; font-weight: 700; color: var(--blue2); }
        .stat-mini-label { font-size: .72rem; color: var(--blue4); text-transform: uppercase; letter-spacing: .06em; }
    </style>
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
    <div style="display:flex;align-items:center;gap:16px;flex-wrap:wrap;margin-bottom:20px;">
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

    <!-- Formulário: adicionar convidado -->
    <div class="form-add-present" style="margin-bottom:24px;">
        <div class="form-add-present-header">
            <i class="bi bi-person-plus-fill"></i>
            <h4>Adicionar Convidado</h4>
        </div>
        <div class="form-add-present-body">
            <form method="POST" style="display:flex;gap:12px;align-items:flex-end;flex-wrap:wrap;">
                <input type="hidden" name="acao" value="adicionar">
                <div class="form-group-custom" style="flex:1;min-width:220px;margin-bottom:0;">
                    <label>Nome do convidado *</label>
                    <input type="text" name="nome" class="input-custom"
                           placeholder="Nome completo" required maxlength="150">
                </div>
                <button type="submit" class="btn-primary-custom" style="white-space:nowrap;">
                    <i class="bi bi-plus-lg"></i> Adicionar e gerar código
                </button>
            </form>
            <p style="font-size:.78rem;color:var(--blue4);margin-top:10px;margin-bottom:0;">
                <i class="bi bi-info-circle me-1"></i>
                O código é gerado automaticamente e deve ser enviado ao convidado no convite.
            </p>
        </div>
    </div>

    <!-- Tabela de convidados -->
    <div class="admin-table-wrap">
        <?php if (empty($convidados)): ?>
        <div style="padding:40px;text-align:center;color:var(--blue4);">
            <i class="bi bi-person-lines-fill" style="font-size:2rem;display:block;margin-bottom:8px;"></i>
            Nenhum convidado cadastrado ainda.
        </div>
        <?php else: ?>
        <table class="admin-table">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Nome</th>
                    <th>Código</th>
                    <th>Status</th>
                    <th>Confirmado em</th>
                    <th>Ações</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($convidados as $i => $c): ?>
                <tr>
                    <td style="color:var(--blue4);"><?= $i + 1 ?></td>
                    <td><strong><?= htmlspecialchars($c['nome']) ?></strong></td>
                    <td><span class="code-badge"><?= htmlspecialchars($c['codigo']) ?></span></td>
                    <td>
                        <?php if ($c['confirmado']): ?>
                        <span class="badge-ok"><i class="bi bi-check-circle-fill me-1"></i>Confirmado</span>
                        <?php else: ?>
                        <span class="badge-pend"><i class="bi bi-clock me-1"></i>Pendente</span>
                        <?php endif; ?>
                    </td>
                    <td style="font-size:.8rem;white-space:nowrap;color:var(--blue4);">
                        <?= $c['confirmado_em']
                            ? (new DateTime($c['confirmado_em']))->format('d/m/Y H:i')
                            : '—' ?>
                    </td>
                    <td>
                        <form method="POST" style="display:inline;"
                              onsubmit="return confirm('Excluir o convidado <?= htmlspecialchars(addslashes($c['nome'])) ?>?')">
                            <input type="hidden" name="acao" value="excluir">
                            <input type="hidden" name="id" value="<?= $c['id'] ?>">
                            <button type="submit" class="btn-action btn-action--delete">
                                <i class="bi bi-trash"></i>
                            </button>
                        </form>
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
