<?php
require_once dirname(__DIR__) . '/config/config.php';
require_once ROOT_DIR . '/includes/db.php';

$pageTitle = 'Confirmar Presença · ' . NOIVA . ' &amp; ' . NOIVO;
$pageCSS   = ['confirmacao.css'];
$pageJS    = ['confirmacao.js'];

// Contador de confirmações
try {
    $db       = getDB();
    $totalPax = (int) $db->query("SELECT COALESCE(SUM(acompanhantes + 1), 0) FROM confirmacoes")->fetchColumn();
    $totalConf= (int) $db->query("SELECT COUNT(*) FROM confirmacoes")->fetchColumn();
} catch (Exception $e) {
    $totalPax = $totalConf = 0;
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

        <!-- Contador de confirmados -->
        <?php if ($totalConf > 0): ?>
        <div class="conf-counter hidden" data-animation="fadeInUp">
            <i class="bi bi-people-fill conf-counter-icon"></i>
            <div>
                <span class="conf-counter-num"><?= $totalPax ?></span>
                <span class="conf-counter-label">
                    pessoa<?= $totalPax !== 1 ? 's' : '' ?> confirmada<?= $totalPax !== 1 ? 's' : '' ?> até agora
                </span>
            </div>
        </div>
        <?php endif; ?>

        <!-- Formulário -->
        <div class="conf-card hidden" data-animation="fadeInUp">

            <div class="conf-card-header">
                <i class="bi bi-envelope-heart"></i>
                <h2>Preencha o formulário</h2>
                <p>Por favor, confirme até <?= \DateTime::createFromFormat('d/m/Y', DATA_BR)->modify('-15 days')->format('d/m/Y') ?></p>
            </div>

            <div id="conf-alert"></div>

            <form id="form-confirmacao" novalidate>

                <div class="form-row">
                    <div class="form-group-custom">
                        <label for="conf-nome">Nome completo *</label>
                        <input type="text" id="conf-nome" name="nome"
                               class="input-custom" placeholder="Seu nome completo"
                               required maxlength="150">
                        <span class="input-error" id="err-nome"></span>
                    </div>
                    <div class="form-group-custom">
                        <label for="conf-telefone">Telefone / WhatsApp</label>
                        <input type="tel" id="conf-telefone" name="telefone"
                               class="input-custom" placeholder="(11) 99999-9999"
                               maxlength="25">
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group-custom">
                        <label for="conf-email">E-mail</label>
                        <input type="email" id="conf-email" name="email"
                               class="input-custom" placeholder="seu@email.com"
                               maxlength="150">
                    </div>
                    <div class="form-group-custom">
                        <label for="conf-acompanhantes">
                            Acompanhantes
                            <span class="label-hint">(além de você)</span>
                        </label>
                        <select id="conf-acompanhantes" name="acompanhantes" class="input-custom">
                            <option value="0">Virei sozinho(a)</option>
                            <option value="1">+ 1 acompanhante</option>
                            <option value="2">+ 2 acompanhantes</option>
                            <option value="3">+ 3 acompanhantes</option>
                            <option value="4">+ 4 acompanhantes</option>
                            <option value="5">+ 5 acompanhantes</option>
                        </select>
                    </div>
                </div>

                <div class="form-group-custom">
                    <label for="conf-restricoes">Restrições alimentares</label>
                    <input type="text" id="conf-restricoes" name="restricoes"
                           class="input-custom"
                           placeholder="Ex.: vegetariano, alergia a lactose... (opcional)"
                           maxlength="300">
                </div>

                <div class="form-group-custom">
                    <label for="conf-mensagem">Mensagem para os noivos <span class="label-hint">(opcional)</span></label>
                    <textarea id="conf-mensagem" name="mensagem"
                              class="input-custom textarea-custom"
                              rows="3"
                              placeholder="Deixe um recado carinhoso..."
                              maxlength="600"></textarea>
                </div>

                <button type="submit" class="btn-primary-custom conf-submit" id="btn-confirmar">
                    <i class="bi bi-check-circle-fill"></i>
                    <span>Confirmar minha presença</span>
                </button>

            </form>

        </div>

        <!-- Info de prazo -->
        <div class="conf-info hidden" data-animation="fadeInUp">
            <i class="bi bi-info-circle"></i>
            <p>
                Para garantirmos a melhor experiência, pedimos que a confirmação seja feita
                com <strong>pelo menos 15 dias de antecedência</strong>. Em caso de dúvidas,
                entre em contato com os noivos.
            </p>
        </div>

    </div>

</main>

<?php require_once ROOT_DIR . '/includes/_footer.php'; ?>
