<?php
require_once __DIR__ . '/config/config.php';
require_once ROOT_DIR . '/includes/db.php';

$pageTitle = 'Mensagens aos Noivos · ' . NOIVA . ' &amp; ' . NOIVO;
$pageCSS   = ['mensagens.css'];
$pageJS    = ['mensagens.js'];

// Mensagens aprovadas
try {
    $db   = getDB();
    $msgs = $db->query(
        "SELECT nome, mensagem, criado_em FROM mensagens WHERE aprovado = 1 ORDER BY criado_em DESC"
    )->fetchAll();
} catch (Exception $e) {
    $msgs = [];
}

require_once ROOT_DIR . '/includes/_head.php';
require_once ROOT_DIR . '/includes/_navbar.php';
?>

<main>

    <!-- Hero banner -->
    <div class="page-hero">
        <div class="page-hero-icon"><i class="bi bi-chat-heart"></i></div>
        <h1>Mensagens aos Noivos</h1>
        <p>Deixe seu carinho, seu desejo, sua lembrança</p>
    </div>

    <div class="container msg-container">

        <!-- Formulário de envio -->
        <div class="msg-form-card hidden" data-animation="fadeInUp">

            <div class="msg-form-header">
                <div class="msg-form-header-icon">
                    <i class="bi bi-heart"></i>
                </div>
                <h2>Escreva para o casal</h2>
                <p>Sua mensagem será exibida após aprovação</p>
            </div>

            <div id="msg-alert"></div>

            <form id="form-mensagem" novalidate>
                <div class="form-group-custom">
                    <label for="msg-nome">Seu nome *</label>
                    <input type="text" id="msg-nome" name="nome"
                           class="input-custom" placeholder="Como quer ser identificado(a)"
                           required maxlength="150">
                    <span class="input-error" id="err-msg-nome"></span>
                </div>
                <div class="form-group-custom">
                    <label for="msg-texto">Sua mensagem *</label>
                    <textarea id="msg-texto" name="mensagem"
                              class="input-custom textarea-custom"
                              rows="4"
                              placeholder="Escreva com carinho para <?= NOIVA ?> &amp; <?= NOIVO ?>..."
                              required maxlength="800"></textarea>
                    <span class="input-error" id="err-msg-texto"></span>
                    <span class="char-counter"><span id="char-count">0</span>/800</span>
                </div>
                <button type="submit" class="btn-primary-custom msg-submit" id="btn-enviar">
                    <i class="bi bi-send-heart-fill"></i>
                    <span>Enviar mensagem</span>
                </button>
            </form>

        </div>

        <!-- Mural de mensagens -->
        <?php if (!empty($msgs)): ?>
        <div class="msg-mural-header hidden" data-animation="fadeInUp">
            <h2 class="section-title">Mural de Mensagens</h2>
            <div class="divider-heart">
                <span></span><i class="bi bi-heart-fill"></i><span></span>
            </div>
        </div>

        <div class="msg-grid hidden" data-animation="fadeInUp">
            <?php foreach ($msgs as $m): ?>
            <div class="msg-card">
                <i class="bi bi-quote msg-quote"></i>
                <p class="msg-text"><?= nl2br(htmlspecialchars($m['mensagem'])) ?></p>
                <div class="msg-footer">
                    <span class="msg-author">
                        <i class="bi bi-person-heart me-1"></i><?= htmlspecialchars($m['nome']) ?>
                    </span>
                    <span class="msg-date">
                        <?= (new DateTime($m['criado_em']))->format('d/m/Y') ?>
                    </span>
                </div>
            </div>
            <?php endforeach; ?>
        </div>

        <?php else: ?>
        <div class="msg-empty hidden" data-animation="fadeInUp">
            <i class="bi bi-chat-heart"></i>
            <p>Seja o primeiro a deixar uma mensagem!</p>
        </div>
        <?php endif; ?>

    </div>

</main>

<?php require_once ROOT_DIR . '/includes/_footer.php'; ?>
