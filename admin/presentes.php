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
        } elseif ($acao === 'excluir_reserva' && $id > 0) {
            $db->prepare("DELETE FROM reservas WHERE id = ?")->execute([$id]);
        } elseif ($acao === 'adicionar') {
            $nome      = trim(strip_tags($_POST['nome']      ?? ''));
            $descricao = trim(strip_tags($_POST['descricao'] ?? ''));
            $categoria = trim(strip_tags($_POST['categoria'] ?? 'Geral'));
            $tipo      = ($_POST['tipo'] ?? 'unico') === 'cota' ? 'cota' : 'unico';
            $preco     = (float) str_replace(',', '.', $_POST['preco'] ?? '0');
            $link      = trim($_POST['link'] ?? '');
            $imagem    = trim($_POST['imagem'] ?? '');
            if ($nome) {
                $db->prepare(
                    "INSERT INTO presentes (nome, descricao, categoria, tipo, preco, link, imagem) VALUES (?, ?, ?, ?, ?, ?, ?)"
                )->execute([$nome, $descricao ?: null, $categoria, $tipo, $preco ?: null, $link ?: null, $imagem ?: null]);
            }
        } elseif ($acao === 'editar' && $id > 0) {
            $nome      = trim(strip_tags($_POST['nome']      ?? ''));
            $descricao = trim(strip_tags($_POST['descricao'] ?? ''));
            $categoria = trim(strip_tags($_POST['categoria'] ?? 'Geral'));
            $tipo      = ($_POST['tipo'] ?? 'unico') === 'cota' ? 'cota' : 'unico';
            $preco     = (float) str_replace(',', '.', $_POST['preco'] ?? '0');
            $link      = trim($_POST['link'] ?? '');
            $imagem    = trim($_POST['imagem'] ?? '');
            if ($nome) {
                $db->prepare(
                    "UPDATE presentes SET nome = ?, descricao = ?, categoria = ?, tipo = ?, preco = ?, link = ?, imagem = ? WHERE id = ?"
                )->execute([$nome, $descricao ?: null, $categoria, $tipo, $preco ?: null, $link ?: null, $imagem ?: null, $id]);
            }
        }
    } catch (Exception $e) {
        error_log('[admin/presentes] ' . $e->getMessage());
    }
    header('Location: ' . BASE_URL . '/admin/presentes');
    exit;
}

require_once ROOT_DIR . '/cron/expirar-reservas.php';

try {
    $db        = getDB();
    $presentes = $db->query("SELECT * FROM presentes ORDER BY categoria ASC, nome ASC")->fetchAll();
    $cats      = $db->query("SELECT DISTINCT categoria FROM presentes ORDER BY categoria")->fetchAll(PDO::FETCH_COLUMN);
    $totalConf = (int) $db->query("SELECT COUNT(*) FROM convidados WHERE confirmado = 1")->fetchColumn();
    $totalPres = count($presentes);
    $msgPend   = (int) $db->query("SELECT COUNT(*) FROM mensagens WHERE aprovado = 0")->fetchColumn();

    // Reservas por presente (todas — inclui pendente/pago/expirado/cancelado)
    $reservasRaw = $db->query(
        "SELECT id, presente_id, nome_convidado, valor, status, metodo, criado_em, pago_em
         FROM reservas ORDER BY presente_id, criado_em DESC"
    )->fetchAll();
    $reservasPorPresente = [];
    foreach ($reservasRaw as $r) {
        $reservasPorPresente[$r['presente_id']][] = $r;
    }
    $totalPagas = (int) $db->query("SELECT COUNT(*) FROM reservas WHERE status = 'pago'")->fetchColumn();
} catch (Exception $e) {
    $presentes = $cats = $reservasRaw = [];
    $reservasPorPresente = [];
    $totalConf = $totalPres = $msgPend = $totalPagas = 0;
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

    <h2 style="font-family:var(--font-serif);color:var(--blue2);margin-bottom:20px;">Lista de Presentes</h2>

    <div class="admin-layout">
    <div class="admin-content">

    <!-- Nav tabs -->
    <div class="admin-nav">
        <a href="<?= BASE_URL ?>/admin/index" class="admin-tab">
            <i class="bi bi-people"></i> Confirmações (<?= $totalConf ?>)
        </a>
        <a href="<?= BASE_URL ?>/admin/convidados" class="admin-tab">
            <i class="bi bi-person-lines-fill"></i> Convidados
        </a>
        <a href="<?= BASE_URL ?>/admin/presentes" class="admin-tab active">
            <i class="bi bi-gift"></i> Presentes (<?= $totalPres ?>) · <?= $totalPagas ?> paga<?= $totalPagas !== 1 ? 's' : '' ?>
        </a>
        <a href="<?= BASE_URL ?>/admin/mensagens" class="admin-tab">
            <i class="bi bi-chat-heart"></i> Mensagens
            <?php if ($msgPend > 0): ?>
            <span class="badge-pend"><?= $msgPend ?> pendente<?= $msgPend > 1 ? 's' : '' ?></span>
            <?php endif; ?>
        </a>
        <a href="<?= BASE_URL ?>/admin/configuracoes" class="admin-tab">
            <i class="bi bi-gear"></i> Configurações
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

            <div class="admin-form-section">
                <h5 class="admin-form-section-title"><i class="bi bi-info-circle-fill"></i> Informações básicas</h5>
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
                <div class="form-group-custom" style="margin-top:12px;">
                    <label>Tipo</label>
                    <select name="tipo" class="input-custom">
                        <option value="unico">Único (1 pessoa dá o presente inteiro)</option>
                        <option value="cota">Cota (vários convidados dividem o valor)</option>
                    </select>
                </div>
            </div>

            <div class="admin-form-section">
                <h5 class="admin-form-section-title"><i class="bi bi-link-45deg"></i> Link e imagem</h5>
                <div class="form-grid-2">
                    <div class="form-group-custom">
                        <label>Link da loja (opcional)</label>
                        <input type="url" name="link" class="input-custom" placeholder="https://..." maxlength="500">
                    </div>
                    <div class="form-group-custom">
                        <label>URL da imagem (opcional)</label>
                        <input type="url" name="imagem" class="input-custom" placeholder="https://.../foto.jpg" maxlength="300">
                    </div>
                </div>
            </div>

            <div class="admin-form-section">
                <h5 class="admin-form-section-title"><i class="bi bi-card-text"></i> Descrição</h5>
                <div class="form-group-custom">
                    <input type="text" name="descricao" class="input-custom" placeholder="Detalhes sobre o presente..." maxlength="300">
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
                    <th>Tipo</th>
                    <th>Reservas / Pix</th>
                    <th>Ações</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $statusMeta = [
                    'pago'      => ['badge-ok',   'bi-check-circle-fill'],
                    'pendente'  => ['badge-pend', 'bi-hourglass-split'],
                    'expirado'  => ['badge-off',  'bi-clock-history'],
                    'cancelado' => ['badge-off',  'bi-x-circle'],
                ];
                ?>
                <?php foreach ($presentes as $i => $p): ?>
                <?php $reservas = $reservasPorPresente[$p['id']] ?? []; ?>
                <tr>
                    <td style="color:var(--blue4);"><?= $i + 1 ?></td>
                    <td>
                        <div style="display:flex;align-items:center;gap:8px;">
                            <?php if ($p['imagem']): ?>
                            <img src="<?= htmlspecialchars($p['imagem']) ?>" alt=""
                                 style="width:36px;height:36px;object-fit:cover;border-radius:6px;flex-shrink:0;"
                                 onerror="this.style.display='none';">
                            <?php endif; ?>
                            <div>
                                <strong><?= htmlspecialchars($p['nome']) ?></strong>
                                <?php if ($p['descricao']): ?>
                                <div style="font-size:.78rem;color:var(--blue4);"><?= htmlspecialchars($p['descricao']) ?></div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </td>
                    <td style="font-size:.82rem;"><?= htmlspecialchars($p['categoria']) ?></td>
                    <td style="font-size:.88rem;font-weight:600;white-space:nowrap;">
                        <?= $p['preco'] ? 'R$ ' . number_format($p['preco'], 2, ',', '.') : '—' ?>
                    </td>
                    <td style="font-size:.82rem;">
                        <?php if ($p['tipo'] === 'cota'): ?>
                            Cota
                            <div style="font-size:.72rem;color:var(--blue4);">
                                R$ <?= number_format($p['valor_arrecadado'], 2, ',', '.') ?> arrecadado
                            </div>
                        <?php else: ?>
                            Único
                        <?php endif; ?>
                    </td>
                    <td style="font-size:.82rem;">
                        <?php if (empty($reservas)): ?>
                            <span style="color:var(--blue4);">—</span>
                        <?php else: ?>
                            <?php
                            $metodoLabel = ['pix' => 'Pix', 'loja' => 'Loja', 'manual' => 'Manual'];
                            ?>
                            <?php foreach ($reservas as $r): ?>
                            <?php [$cls, $icon] = $statusMeta[$r['status']] ?? ['badge-off', 'bi-question-circle']; ?>
                            <div style="display:flex;align-items:center;gap:6px;margin-bottom:4px;">
                                <span class="<?= $cls ?>" style="white-space:nowrap;">
                                    <i class="bi <?= $icon ?> me-1"></i><?= htmlspecialchars($r['nome_convidado']) ?>
                                    · R$ <?= number_format($r['valor'], 2, ',', '.') ?>
                                    · <?= $metodoLabel[$r['metodo']] ?? $r['metodo'] ?>
                                </span>
                                <span style="color:var(--blue4);font-size:.72rem;white-space:nowrap;">
                                    <?= (new DateTime($r['criado_em']))->format('d/m/Y') ?>
                                </span>
                                <form method="POST" style="display:inline;"
                                      onsubmit="return confirm('Remover este registro?')">
                                    <input type="hidden" name="acao" value="excluir_reserva">
                                    <input type="hidden" name="id" value="<?= $r['id'] ?>">
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
                            <button type="button" class="btn-action btn-editar"
                                    style="border:1px solid var(--whiteblue3);color:var(--blue3);"
                                    data-id="<?= $p['id'] ?>"
                                    data-nome="<?= htmlspecialchars($p['nome'], ENT_QUOTES) ?>"
                                    data-categoria="<?= htmlspecialchars($p['categoria'], ENT_QUOTES) ?>"
                                    data-preco="<?= htmlspecialchars((string) $p['preco'], ENT_QUOTES) ?>"
                                    data-tipo="<?= $p['tipo'] ?>"
                                    data-link="<?= htmlspecialchars($p['link'] ?? '', ENT_QUOTES) ?>"
                                    data-imagem="<?= htmlspecialchars($p['imagem'] ?? '', ENT_QUOTES) ?>"
                                    data-descricao="<?= htmlspecialchars($p['descricao'] ?? '', ENT_QUOTES) ?>"
                                    title="Editar">
                                <i class="bi bi-pencil"></i>
                            </button>
                            <form method="POST" style="display:inline;"
                                  onsubmit="return confirm('Excluir este presente da lista?')">
                                <input type="hidden" name="acao" value="excluir">
                                <input type="hidden" name="id" value="<?= $p['id'] ?>">
                                <button type="submit" class="btn-action btn-action--delete" title="Excluir presente">
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

    </div><!-- /.admin-content -->

    <aside class="admin-guide">
        <div class="admin-guide-header">
            <i class="bi bi-lightbulb-fill"></i>
            <h4>Guia rápido</h4>
        </div>
        <div class="admin-guide-body">
            <p>Cadastre os presentes que os convidados poderão dar. Tipo <strong>Único</strong> =
            1 pessoa dá o presente inteiro; <strong>Cota</strong> = vários convidados dividem o valor.</p>
            <ol class="admin-guide-steps">
                <li>Preencha nome, categoria e preço estimado.</li>
                <li>Link da loja e URL da imagem são opcionais — sem imagem, o card mostra um ícone.</li>
                <li>Use o lápis na tabela pra editar um presente já cadastrado.</li>
                <li>Presentes dados (Pix, loja ou confirmação manual) ficam bloqueados automaticamente.</li>
            </ol>
        </div>
    </aside>
    </div><!-- /.admin-layout -->

</div>

<!-- Modal: editar presente -->
<div class="modal fade" id="modalEditarPresente" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable modal-lg">
        <div class="modal-content" style="border-radius:var(--radius);border:none;overflow:hidden;">
            <div class="modal-header" style="background:linear-gradient(135deg, var(--blue1), var(--blue2));color:var(--white);border:none;">
                <h5 class="modal-title"><i class="bi bi-pencil-fill me-2"></i>Editar Presente</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form method="POST" id="form-editar-presente">
                    <input type="hidden" name="acao" value="editar">
                    <input type="hidden" name="id" id="editar-id">

                    <div class="admin-form-section">
                        <h5 class="admin-form-section-title"><i class="bi bi-info-circle-fill"></i> Informações básicas</h5>
                        <div class="form-grid">
                            <div class="form-group-custom">
                                <label>Nome do presente *</label>
                                <input type="text" name="nome" id="editar-nome" class="input-custom" required maxlength="200">
                            </div>
                            <div class="form-group-custom">
                                <label>Categoria</label>
                                <input type="text" name="categoria" id="editar-categoria" class="input-custom"
                                       list="cats-list" maxlength="80">
                            </div>
                            <div class="form-group-custom">
                                <label>Preço estimado (R$)</label>
                                <input type="text" name="preco" id="editar-preco" class="input-custom" placeholder="0,00" maxlength="12">
                            </div>
                        </div>
                        <div class="form-group-custom" style="margin-top:12px;">
                            <label>Tipo</label>
                            <select name="tipo" id="editar-tipo" class="input-custom">
                                <option value="unico">Único (1 pessoa dá o presente inteiro)</option>
                                <option value="cota">Cota (vários convidados dividem o valor)</option>
                            </select>
                        </div>
                    </div>

                    <div class="admin-form-section">
                        <h5 class="admin-form-section-title"><i class="bi bi-link-45deg"></i> Link e imagem</h5>
                        <div class="form-grid-2">
                            <div class="form-group-custom">
                                <label>Link da loja (opcional)</label>
                                <input type="url" name="link" id="editar-link" class="input-custom" placeholder="https://..." maxlength="500">
                            </div>
                            <div class="form-group-custom">
                                <label>URL da imagem (opcional)</label>
                                <input type="url" name="imagem" id="editar-imagem" class="input-custom" placeholder="https://.../foto.jpg" maxlength="300">
                            </div>
                        </div>
                    </div>

                    <div class="admin-form-section">
                        <h5 class="admin-form-section-title"><i class="bi bi-card-text"></i> Descrição</h5>
                        <div class="form-group-custom">
                            <input type="text" name="descricao" id="editar-descricao" class="input-custom" placeholder="Detalhes..." maxlength="300">
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer" style="border-top:1px solid var(--whiteblue4);">
                <button type="button" class="btn-outline-custom" data-bs-dismiss="modal">Cancelar</button>
                <button type="submit" form="form-editar-presente" class="btn-primary-custom">
                    <i class="bi bi-save me-1"></i>Salvar alterações
                </button>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
(function () {
    const modalEditar = new bootstrap.Modal(document.getElementById('modalEditarPresente'));
    document.querySelectorAll('.btn-editar').forEach(btn => {
        btn.addEventListener('click', () => {
            document.getElementById('editar-id').value        = btn.dataset.id;
            document.getElementById('editar-nome').value      = btn.dataset.nome;
            document.getElementById('editar-categoria').value = btn.dataset.categoria;
            document.getElementById('editar-preco').value     = btn.dataset.preco;
            document.getElementById('editar-tipo').value      = btn.dataset.tipo;
            document.getElementById('editar-link').value      = btn.dataset.link;
            document.getElementById('editar-imagem').value    = btn.dataset.imagem;
            document.getElementById('editar-descricao').value = btn.dataset.descricao;
            modalEditar.show();
        });
    });
})();
</script>
</body>
</html>
