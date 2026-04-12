<?php
require_once __DIR__ . '/config/config.php';
require_once ROOT_DIR . '/includes/db.php';

$pageTitle = 'Confirmar Presença · ' . NOIVA . ' &amp; ' . NOIVO;
$pageCSS   = ['confirmacao.css'];
$pageJS    = ['confirmacao.js'];

try {
    $db            = getDB();
    $confirmAberta = $db->query("SELECT valor FROM configuracoes WHERE chave = 'confirmacao_aberta'")->fetchColumn();
    $totalConf     = (int) $db->query("SELECT COUNT(*) FROM convidados WHERE confirmado = 1")->fetchColumn();
    $totalConv     = (int) $db->query("SELECT COUNT(*) FROM convidados")->fetchColumn();
} catch (Exception $e) {
    $confirmAberta = '0';
    $totalConf = $totalConv = 0;
}

require_once ROOT_DIR . '/includes/_head.php';
require_once ROOT_DIR . '/includes/_navbar.php';
?>

<main>

    <!-- Hero banner -->
    <div class="page-hero">
        <div class="page-hero-icon"><i class="bi bi-check-circle"></i></div>
        <h1>Confirmar Presença</h1>
        <p>Sua presença é o melhor presente que podemos receber</p>
    </div>

    <div class="container conf-container">

        <?php if ($confirmAberta !== '1'): ?>
        <!-- Estado: confirmações fechadas -->
        <div class="conf-card conf-locked hidden" data-animation="fadeInUp">
            <div class="conf-card-header">
                <div class="conf-card-header-icon">
                    <i class="bi bi-lock"></i>
                </div>
                <h2>Em breve</h2>
                <p>As confirmações de presença ainda não foram abertas</p>
            </div>
            <div class="conf-locked-body">
                <p>
                    Assim que as confirmações estiverem disponíveis, você receberá
                    um convite com seu <strong>código de acesso</strong> para confirmar
                    sua presença aqui.
                </p>
                <p style="margin-bottom:0;">
                    Fique atento às novidades! 💙
                </p>
            </div>
        </div>

        <?php else: ?>
        <!-- Estado: confirmações abertas -->

        <?php if ($totalConf > 0): ?>
        <div class="conf-counter hidden" data-animation="fadeInUp">
            <i class="bi bi-people-fill conf-counter-icon"></i>
            <div>
                <span class="conf-counter-num"><?= $totalConf ?></span>
                <span class="conf-counter-label">
                    de <?= $totalConv ?> convidado<?= $totalConv !== 1 ? 's' : '' ?> confirmado<?= $totalConf !== 1 ? 's' : '' ?> até agora
                </span>
            </div>
        </div>
        <?php endif; ?>

        <div class="conf-card hidden" data-animation="fadeInUp">

            <div class="conf-card-header">
                <div class="conf-card-header-icon">
                    <i class="bi bi-envelope-heart"></i>
                </div>
                <h2>Confirme sua presença</h2>
                <p>Use o código que recebeu no seu convite</p>
            </div>

            <div id="conf-alert"></div>

            <form id="form-confirmacao" novalidate>

                <div class="form-group-custom">
                    <label for="conf-nome">Nome completo *</label>
                    <input type="text" id="conf-nome" name="nome"
                           class="input-custom" placeholder="Seu nome completo (como está no convite)"
                           required maxlength="150" autocomplete="name">
                    <span class="input-error" id="err-nome"></span>
                </div>

                <div class="form-group-custom">
                    <label for="conf-codigo">Código do convite *</label>
                    <input type="text" id="conf-codigo" name="codigo"
                           class="input-custom input-codigo"
                           placeholder="Ex.: AB3K7M"
                           required maxlength="10"
                           autocomplete="off"
                           style="letter-spacing:.18em;text-transform:uppercase;font-family:monospace;font-size:1.1rem;">
                    <span class="input-error" id="err-codigo"></span>
                </div>

                <button type="submit" class="btn-primary-custom conf-submit" id="btn-confirmar">
                    <i class="bi bi-check-circle-fill"></i>
                    <span>Confirmar minha presença</span>
                </button>

            </form>

        </div>

        <div class="conf-info hidden" data-animation="fadeInUp">
            <i class="bi bi-info-circle"></i>
            <p>
                O código está no seu convite impresso ou digital. Em caso de dúvidas,
                entre em contato com os noivos.
            </p>
        </div>

        <?php endif; ?>

    </div>

</main>

<?php require_once ROOT_DIR . '/includes/_footer.php'; ?>
