<?php
require_once __DIR__ . '/config/config.php';
require_once ROOT_DIR . '/includes/db.php';

$pageTitle = 'Lista de Presentes · ' . NOIVA . ' &amp; ' . NOIVO;
$pageCSS   = ['presentes.css'];
$pageJS    = ['presentes.js'];

// Filtro de categoria
$categoria = trim($_GET['cat'] ?? '');

try {
    $db = getDB();
    // Categorias disponíveis
    $cats = $db->query("SELECT DISTINCT categoria FROM presentes ORDER BY categoria")->fetchAll();

    // Lista de presentes
    if ($categoria) {
        $stmt = $db->prepare("SELECT * FROM presentes WHERE categoria = ? ORDER BY nome ASC");
        $stmt->execute([$categoria]);
    } else {
        $stmt = $db->query("SELECT * FROM presentes ORDER BY categoria ASC, nome ASC");
    }
    $presentes = $stmt->fetchAll();

    $total     = count($presentes);
    $totalDado = (int) $db->query("SELECT COUNT(*) FROM presentes WHERE comprado = 1")->fetchColumn()
               + (int) $db->query("SELECT COUNT(*) FROM presentes WHERE tipo = 'cota' AND valor_arrecadado > 0")->fetchColumn();

} catch (Exception $e) {
    $presentes = [];
    $cats      = [];
    $total = $totalDado = 0;
}

require_once ROOT_DIR . '/includes/_head.php';
require_once ROOT_DIR . '/includes/_navbar.php';
?>

<main>

    <!-- Hero banner -->
    <div class="page-hero">
        <div class="page-hero-icon"><i class="bi bi-gift"></i></div>
        <h1>Lista de Presentes</h1>
        <p>Escolha algo especial para o nosso novo lar</p>
    </div>

    <div class="container pres-container">

        <!-- Contador de escolhas -->
        <div class="pres-progress-bar hidden" data-animation="fadeInUp">
            <div class="pres-progress-info">
                <span><i class="bi bi-gift-fill me-1"></i><?= $total ?> presentes na lista</span>
                <span><?= $totalDado ?> presente<?= $totalDado !== 1 ? 's' : '' ?> com contribuição</span>
            </div>
        </div>

        <!-- Filtro de categorias -->
        <div class="pres-filter hidden" data-animation="fadeInUp">
            <a href="<?= BASE_URL ?>/presentes"
               class="filter-btn <?= !$categoria ? 'active' : '' ?>">
                Todos
            </a>
            <?php foreach ($cats as $c): ?>
            <a href="<?= BASE_URL ?>/presentes?cat=<?= urlencode($c['categoria']) ?>"
               class="filter-btn <?= $categoria === $c['categoria'] ? 'active' : '' ?>">
                <?= htmlspecialchars($c['categoria']) ?>
            </a>
            <?php endforeach; ?>
        </div>

        <!-- Grid de presentes -->
        <?php if (empty($presentes)): ?>
        <div class="pres-empty">
            <i class="bi bi-gift"></i>
            <p>Nenhum presente encontrado.</p>
        </div>
        <?php else: ?>
        <div class="pres-grid" id="pres-grid">
            <?php foreach ($presentes as $p): ?>
            <?php
                $ehCota    = $p['tipo'] === 'cota';
                $restante  = $ehCota ? max(0, (float) $p['preco'] - (float) $p['valor_arrecadado']) : 0;
                $completo  = $ehCota ? $restante <= 0 : (bool) $p['comprado'];
                $percCota  = $ehCota && $p['preco'] > 0 ? min(100, round(((float) $p['valor_arrecadado'] / (float) $p['preco']) * 100)) : 0;
            ?>
            <div class="pres-card hidden" data-animation="fadeInUp">

                <div class="pres-badge <?= $completo ? 'pres-badge--done' : 'pres-badge--open' ?>">
                    <?= $completo ? ($ehCota ? 'Arrecadado' : 'Dado com carinho') : ($ehCota ? 'Contribua' : 'Disponível') ?>
                </div>

                <div class="pres-card-media">
                    <?php if ($p['imagem']): ?>
                    <img class="pres-card-img" src="<?= htmlspecialchars($p['imagem']) ?>"
                         alt="<?= htmlspecialchars($p['nome'], ENT_QUOTES) ?>" loading="lazy"
                         onerror="this.remove(); this.parentElement.querySelector('.pres-card-icon').style.display='flex';">
                    <div class="pres-card-icon" style="display:none;">
                        <i class="bi bi-gift"></i>
                    </div>
                    <?php else: ?>
                    <div class="pres-card-icon">
                        <i class="bi bi-gift"></i>
                    </div>
                    <?php endif; ?>
                </div>

                <div class="pres-card-body">
                    <span class="pres-cat"><?= htmlspecialchars($p['categoria']) ?></span>
                    <h3 class="pres-name"><?= htmlspecialchars($p['nome']) ?></h3>
                    <?php if ($p['descricao']): ?>
                    <p class="pres-desc"><?= htmlspecialchars($p['descricao']) ?></p>
                    <?php endif; ?>
                    <?php if ($p['preco']): ?>
                    <p class="pres-price">R$ <?= number_format($p['preco'], 2, ',', '.') ?></p>
                    <?php endif; ?>
                    <?php if ($ehCota): ?>
                    <div class="pres-cota-progress">
                        <div class="pres-cota-bar"><div class="pres-cota-fill" style="width:<?= $percCota ?>%"></div></div>
                        <span class="pres-cota-label">R$ <?= number_format($p['valor_arrecadado'], 2, ',', '.') ?> arrecadado (<?= $percCota ?>%)</span>
                    </div>
                    <?php elseif ($completo && $p['comprado_por']): ?>
                    <p class="pres-desc"><i class="bi bi-heart-fill me-1"></i>Dado por <?= htmlspecialchars($p['comprado_por']) ?></p>
                    <?php endif; ?>
                </div>

                <div class="pres-card-actions">
                    <?php if (!$completo): ?>
                        <?php if ($ehCota): ?>
                        <button class="btn-primary-custom btn-sm-custom btn-escolher"
                                data-id="<?= $p['id'] ?>"
                                data-nome="<?= htmlspecialchars($p['nome'], ENT_QUOTES) ?>"
                                data-tipo="cota"
                                data-restante="<?= $restante ?>">
                            <i class="bi bi-heart"></i> Quero contribuir
                        </button>
                        <?php else: ?>
                            <?php if ($p['link'] && $p['link'] !== '#'): ?>
                            <button class="btn-outline-custom btn-sm-custom btn-visitar-loja"
                                    data-id="<?= $p['id'] ?>"
                                    data-nome="<?= htmlspecialchars($p['nome'], ENT_QUOTES) ?>"
                                    data-link="<?= htmlspecialchars($p['link'], ENT_QUOTES) ?>">
                                <i class="bi bi-bag"></i> Visitar loja
                            </button>
                            <?php endif; ?>
                            <button class="btn-primary-custom btn-sm-custom btn-escolher"
                                    data-id="<?= $p['id'] ?>"
                                    data-nome="<?= htmlspecialchars($p['nome'], ENT_QUOTES) ?>"
                                    data-tipo="unico"
                                    data-restante="<?= $p['preco'] ?>">
                                <i class="bi bi-qr-code"></i> Presentear via Pix
                            </button>
                            <button class="btn-outline-custom btn-sm-custom btn-confirmar-manual"
                                    data-id="<?= $p['id'] ?>"
                                    data-nome="<?= htmlspecialchars($p['nome'], ENT_QUOTES) ?>">
                                <i class="bi bi-check2-circle"></i> Confirmar presente
                            </button>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>

            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>

    </div>

    <!-- Modal: gerar Pix -->
    <div class="modal fade" id="modalPresente" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable">
            <div class="modal-content modal-custom">
                <div class="modal-header modal-header-custom">
                    <h5 class="modal-title"><i class="bi bi-gift-fill me-2"></i>Presentear via Pix</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p class="modal-present-name" id="modal-present-name"></p>
                    <div id="modal-alert"></div>

                    <!-- Passo 1: dados -->
                    <form id="form-presente">
                        <input type="hidden" id="presente-id" name="id">
                        <div class="form-group-custom">
                            <label for="presente-nome">Seu nome completo *</label>
                            <input type="text" id="presente-nome" name="nome"
                                   class="input-custom" placeholder="Digite seu nome" required maxlength="150">
                        </div>
                        <div class="form-group-custom">
                            <label for="presente-email">E-mail (opcional)</label>
                            <input type="email" id="presente-email" name="email"
                                   class="input-custom" placeholder="para receber a confirmação" maxlength="150">
                        </div>
                        <div class="form-group-custom" id="grupo-valor" style="display:none;">
                            <label for="presente-valor">Valor da contribuição (R$) *</label>
                            <input type="text" id="presente-valor" name="valor"
                                   class="input-custom" placeholder="0,00" maxlength="12">
                        </div>
                    </form>

                    <!-- Passo 2: QR Pix -->
                    <div id="pix-resultado" style="display:none;text-align:center;">
                        <img id="pix-qr-img" src="" alt="QR Code Pix" style="max-width:220px;margin:10px auto;display:block;">
                        <p class="modal-text">Escaneie o QR Code ou copie o código abaixo:</p>
                        <textarea id="pix-copia-cola" class="input-custom" rows="3" readonly style="font-size:.75rem;"></textarea>
                        <button type="button" class="btn-outline-custom btn-sm-custom mt-2" id="btn-copiar-pix">
                            <i class="bi bi-clipboard"></i> Copiar código
                        </button>
                        <p class="modal-text mt-2" id="pix-status-text">Aguardando pagamento…</p>
                    </div>
                </div>
                <div class="modal-footer modal-footer-custom">
                    <button type="button" class="btn-outline-custom" data-bs-dismiss="modal">Fechar</button>
                    <button type="button" class="btn-primary-custom" id="btn-confirmar-presente">
                        <i class="bi bi-heart-fill me-1"></i>Gerar Pix
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal: guia de compra na loja -->
    <div class="modal fade" id="modalGuiaLoja" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable">
            <div class="modal-content modal-custom">
                <div class="modal-header modal-header-custom">
                    <h5 class="modal-title"><i class="bi bi-bag-fill me-2"></i>Visitar loja</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p class="modal-present-name" id="guia-present-name"></p>
                    <div id="guia-alert"></div>

                    <!-- Passo 1: instruções + link -->
                    <div id="guia-passo-link">
                        <p class="modal-text">
                            1. Clique em <strong>"Ir para a loja"</strong> e finalize a compra no site do lojista.<br>
                            2. Depois de comprar, volte aqui e clique em <strong>"Já comprei, confirmar"</strong>.<br>
                            3. Ao confirmar, este presente é bloqueado para os demais convidados.
                        </p>
                        <a href="#" target="_blank" rel="noopener" id="guia-link-loja"
                           class="btn-outline-custom btn-sm-custom">
                            <i class="bi bi-box-arrow-up-right"></i> Ir para a loja
                        </a>
                    </div>

                    <!-- Passo 2: form de confirmação -->
                    <form id="form-guia-confirmar" style="display:none;">
                        <input type="hidden" id="guia-presente-id" name="id">
                        <div class="form-group-custom">
                            <label for="guia-nome">Seu nome completo *</label>
                            <input type="text" id="guia-nome" name="nome"
                                   class="input-custom" placeholder="Digite seu nome" required maxlength="150">
                        </div>
                        <div class="form-group-custom">
                            <label for="guia-email">E-mail (opcional)</label>
                            <input type="email" id="guia-email" name="email"
                                   class="input-custom" placeholder="para receber a confirmação" maxlength="150">
                        </div>
                    </form>
                </div>
                <div class="modal-footer modal-footer-custom">
                    <button type="button" class="btn-outline-custom" data-bs-dismiss="modal">Fechar</button>
                    <button type="button" class="btn-primary-custom" id="btn-guia-ir-confirmar">
                        <i class="bi bi-check2-circle me-1"></i>Já comprei, confirmar
                    </button>
                    <button type="button" class="btn-primary-custom" id="btn-guia-confirmar" style="display:none;">
                        <i class="bi bi-check2-circle me-1"></i>Confirmar presente
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal: confirmar presente sem Pix/loja (comprado por fora ou dinheiro) -->
    <div class="modal fade" id="modalConfirmarManual" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable">
            <div class="modal-content modal-custom">
                <div class="modal-header modal-header-custom">
                    <h5 class="modal-title"><i class="bi bi-check2-circle me-2"></i>Confirmar presente</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p class="modal-present-name" id="manual-present-name"></p>
                    <div id="manual-alert"></div>
                    <p class="modal-text">
                        Use esta opção se você já entregou o presente em mãos, comprou em outra
                        loja ou vai dar o valor em dinheiro. Ao confirmar, o presente é bloqueado
                        para os demais convidados.
                    </p>
                    <form id="form-manual-confirmar">
                        <input type="hidden" id="manual-presente-id" name="id">
                        <div class="form-group-custom">
                            <label for="manual-nome">Seu nome completo *</label>
                            <input type="text" id="manual-nome" name="nome"
                                   class="input-custom" placeholder="Digite seu nome" required maxlength="150">
                        </div>
                        <div class="form-group-custom">
                            <label for="manual-email">E-mail (opcional)</label>
                            <input type="email" id="manual-email" name="email"
                                   class="input-custom" placeholder="para receber a confirmação" maxlength="150">
                        </div>
                    </form>
                </div>
                <div class="modal-footer modal-footer-custom">
                    <button type="button" class="btn-outline-custom" data-bs-dismiss="modal">Fechar</button>
                    <button type="button" class="btn-primary-custom" id="btn-manual-confirmar">
                        <i class="bi bi-check2-circle me-1"></i>Confirmar presente
                    </button>
                </div>
            </div>
        </div>
    </div>

</main>

<?php require_once ROOT_DIR . '/includes/_footer.php'; ?>
