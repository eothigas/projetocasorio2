<?php
require_once __DIR__ . '/config/config.php';

$pageTitle = NOIVA . ' e ' . NOIVO . ' · ' . DATA_BR;
$pageCSS   = ['home.css'];
$pageJS    = ['home.js'];

require_once ROOT_DIR . '/includes/_head.php';
require_once ROOT_DIR . '/includes/_navbar.php';
?>

<main>

    <!-- ================================================
         HERO
         ================================================ -->
    <section id="hero">
        <div class="hero-content">

            <p class="hero-pre hidden" data-animation="fadeInDown">
                Com a graça de Deus
            </p>

            <h1 class="hero-names hidden" data-animation="fadeInUp">
                <?= NOIVA ?> &amp; <?= NOIVO ?>
            </h1>

            <p class="hero-pre hidden">CONVIDAM PARA SEU CASAMENTO</p>
            
            <div class="hero-divider hidden" data-animation="fadeInUp">
                <span></span>
                <i class="bi bi-heart-fill"></i>
                <span></span>
            </div>

            <p class="hero-date hidden" data-animation="fadeInUp">
                <?= DIA_SEMANA ?> &middot; <?= DATA_BR ?> &middot; <?= HORA ?>
            </p>

            <!-- Countdown -->
            <div class="countdown hidden" id="countdown" data-target="<?= DATA_ISO ?>" data-animation="fadeInUp">
                <div class="countdown-item">
                    <span class="countdown-number" id="cd-days">--</span>
                    <span class="countdown-label">Dias</span>
                </div>
                <div class="countdown-item">
                    <span class="countdown-number" id="cd-hours">--</span>
                    <span class="countdown-label">Horas</span>
                </div>
                <div class="countdown-item">
                    <span class="countdown-number" id="cd-minutes">--</span>
                    <span class="countdown-label">Minutos</span>
                </div>
                <div class="countdown-item">
                    <span class="countdown-number" id="cd-seconds">--</span>
                    <span class="countdown-label">Segundos</span>
                </div>
            </div>

        </div>

        <div class="scroll-indicator">
            <i class="bi bi-chevron-down"></i>
            <span>rolar</span>
        </div>
    </section>

    <!-- ================================================
         BENÇÃOS
         ================================================ -->
    <section id="bencao">
        <div class="container">

            <p class="section-subtitle hidden" data-animation="fadeInUp">com as bençãos de Deus e de seus pais</p>
            <h2 class="section-title hidden" data-animation="fadeInUp">Famílias</h2>
            <div class="divider-heart hidden" data-animation="fadeInUp">
                <span></span><i class="bi bi-heart-fill"></i><span></span>
            </div>

            <div class="bencao-cards">
                <div class="bencao-card hidden" data-animation="fadeInLeft">
                    <i class="bi bi-person-standing-dress"></i>
                    <p class="bencao-tag">Família da Noiva</p>
                    <ul>
                        <li><?= PAI_NOIVA ?></li>
                        <li><?= MAE_NOIVA ?></li>
                    </ul>
                </div>
                <div class="bencao-card hidden" data-animation="fadeInRight">
                    <i class="bi bi-person-standing"></i>
                    <p class="bencao-tag">Família do Noivo</p>
                    <ul>
                        <li><?= PAI_NOIVO ?></li>
                        <li><?= MAE_NOIVO ?></li>
                    </ul>
                </div>
            </div>

        </div>
    </section>

    <!-- ================================================
         CONVITE
         ================================================ -->
    <section id="convite">
        <div class="container">

            <div class="convite-card hidden" data-animation="fadeInUp">

                <h2 class="convite-names">
                    <?= NOIVA ?> 
                    <span style="font-size:1.6rem;color:var(--blue4);">&amp;</span> 
                    <?= NOIVO ?>
                </h2>

                <div class="divider-heart">
                    <span></span><i class="bi bi-heart-fill"></i><span></span>
                </div>

                <p class="convite-invite-text">convidam para o seu casamento</p>

                <div class="convite-info">
                    <div class="convite-info-col">
                        <span class="convite-info-label">Dia</span>
                        <span class="convite-info-value"><?= DIA_SEMANA ?></span>
                    </div>
                    <div class="convite-info-col" style="
                        border-left: 2px solid var(--blue1);
                        border-right: 2px solid var(--blue1);
                    ">
                        <span class="convite-info-label"><?= MES ?> · <?= ANO ?></span>
                        <span class="convite-info-value big"><?= DIA ?></span>
                    </div>
                    <div class="convite-info-col">
                        <span class="convite-info-label">Horário</span>
                        <span class="convite-info-value"><?= HORA ?></span>
                    </div>
                </div>

                <div class="convite-local">
                    <p style="font-size:.88rem;color:var(--blue2);font-weight:600;margin-bottom:4px;">
                        <?= LOCAL_NOME ?> · <?= LOCAL_BAIRRO ?>
                    </p>
                    <a href="<?= LOCAL_MAPS ?>" target="_blank" rel="noopener">
                        <i class="bi bi-geo-alt-fill"></i>
                        <?= LOCAL_END ?>, <?= LOCAL_CEP ?>
                    </a>
                </div>

            </div>

        </div>
    </section>

    <!-- ================================================
         MÓDULOS (Quick Access)
         ================================================ -->
    <section id="modulos">
        <div class="container">

            <h2 class="section-title hidden" data-animation="fadeInUp">Tudo sobre o nosso dia</h2>
            <div class="divider-heart hidden" data-animation="fadeInUp">
                <span></span><i class="bi bi-heart-fill"></i><span></span>
            </div>

            <div class="modulos-grid">

                <a href="<?= BASE_URL ?>/presentes" class="modulo-card hidden" data-animation="fadeInUp">
                    <div class="modulo-icon"><i class="bi bi-gift"></i></div>
                    <p class="modulo-title">Lista de Presentes</p>
                    <p class="modulo-desc">Escolha um presente especial para nos ajudar a começar essa nova vida juntos.</p>
                    <span class="modulo-link">Ver presentes <i class="bi bi-arrow-right"></i></span>
                </a>

                <a href="<?= BASE_URL ?>/local" class="modulo-card hidden" data-animation="fadeInUp">
                    <div class="modulo-icon"><i class="bi bi-geo-alt"></i></div>
                    <p class="modulo-title">Local do Casamento</p>
                    <p class="modulo-desc">Saiba como chegar, estacionamento e todas as informações sobre o espaço.</p>
                    <span class="modulo-link">Ver local <i class="bi bi-arrow-right"></i></span>
                </a>

                <a href="<?= BASE_URL ?>/confirmacao" class="modulo-card hidden" data-animation="fadeInUp">
                    <div class="modulo-icon"><i class="bi bi-check-circle"></i></div>
                    <p class="modulo-title">Confirmar Presença</p>
                    <p class="modulo-desc">Confirme sua presença e a de seus acompanhantes para organizarmos tudo com carinho.</p>
                    <span class="modulo-link">Confirmar agora <i class="bi bi-arrow-right"></i></span>
                </a>

                <a href="<?= BASE_URL ?>/mensagens" class="modulo-card hidden" data-animation="fadeInUp">
                    <div class="modulo-icon"><i class="bi bi-chat-heart"></i></div>
                    <p class="modulo-title">Mensagens aos Noivos</p>
                    <p class="modulo-desc">Deixe uma mensagem especial, um desejo ou uma lembrança para o casal.</p>
                    <span class="modulo-link">Enviar mensagem <i class="bi bi-arrow-right"></i></span>
                </a>

            </div>

        </div>
    </section>

</main>

<?php require_once ROOT_DIR . '/includes/_footer.php'; ?>
