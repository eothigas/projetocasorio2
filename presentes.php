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

    $total         = count($presentes);
    $totalEscolhas = (int) $db->query("SELECT COUNT(*) FROM presentes_escolhas")->fetchColumn();

} catch (Exception $e) {
    $presentes = [];
    $cats      = [];
    $total = $totalEscolhas = 0;
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
                <span><?= $totalEscolhas ?> escolha<?= $totalEscolhas !== 1 ? 's' : '' ?> registrada<?= $totalEscolhas !== 1 ? 's' : '' ?></span>
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
            <div class="pres-card hidden" data-animation="fadeInUp">

                <div class="pres-badge pres-badge--open">Disponível</div>

                <div class="pres-card-icon">
                    <i class="bi bi-gift"></i>
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
                </div>

                <div class="pres-card-actions">
                    <?php if ($p['link'] && $p['link'] !== '#'): ?>
                    <a href="<?= htmlspecialchars($p['link']) ?>" target="_blank" rel="noopener"
                       class="btn-outline-custom btn-sm-custom">
                        <i class="bi bi-bag"></i> Ver produto
                    </a>
                    <?php endif; ?>
                    <button class="btn-primary-custom btn-sm-custom btn-escolher"
                            data-id="<?= $p['id'] ?>"
                            data-nome="<?= htmlspecialchars($p['nome'], ENT_QUOTES) ?>">
                        <i class="bi bi-heart"></i> Quero dar este
                    </button>
                </div>

            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>

    </div>

    <!-- Modal: confirmar presente -->
    <div class="modal fade" id="modalPresente" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content modal-custom">
                <div class="modal-header modal-header-custom">
                    <h5 class="modal-title"><i class="bi bi-gift-fill me-2"></i>Confirmar presente</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p class="modal-present-name" id="modal-present-name"></p>
                    <p class="modal-text">Informe seu nome para reservarmos este presente para você:</p>
                    <div id="modal-alert"></div>
                    <form id="form-presente">
                        <input type="hidden" id="presente-id" name="id">
                        <div class="form-group-custom">
                            <label for="presente-nome">Seu nome completo *</label>
                            <input type="text" id="presente-nome" name="nome"
                                   class="input-custom" placeholder="Digite seu nome" required maxlength="150">
                        </div>
                    </form>
                </div>
                <div class="modal-footer modal-footer-custom">
                    <button type="button" class="btn-outline-custom" data-bs-dismiss="modal">Cancelar</button>
                    <button type="button" class="btn-primary-custom" id="btn-confirmar-presente">
                        <i class="bi bi-heart-fill me-1"></i>Confirmar
                    </button>
                </div>
            </div>
        </div>
    </div>

</main>

<?php require_once ROOT_DIR . '/includes/_footer.php'; ?>
