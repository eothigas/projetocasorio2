<?php
require_once __DIR__ . '/config/config.php';

$pageTitle = 'Local do Casamento · ' . NOIVA . ' &amp; ' . NOIVO;
$pageCSS   = ['local.css'];
$pageJS    = [];

require_once ROOT_DIR . '/includes/_head.php';
require_once ROOT_DIR . '/includes/_navbar.php';
?>

<main>

    <!-- Hero banner -->
    <div class="page-hero">
        <div class="page-hero-icon"><i class="bi bi-geo-alt"></i></div>
        <h1>Local do Casamento</h1>
        <p>Todas as informações para você chegar com tranquilidade</p>
    </div>

    <div class="container local-container">

        <!-- Card principal: info + mapa -->
        <div class="local-grid hidden" data-animation="fadeInUp">

            <!-- Informações -->
            <div class="local-info-card">
                <div class="local-info-header">
                    <i class="bi bi-building"></i>
                    <div>
                        <h2><?= LOCAL_NOME ?></h2>
                        <p><?= LOCAL_BAIRRO ?></p>
                    </div>
                </div>

                <ul class="local-details">
                    <li>
                        <i class="bi bi-geo-alt-fill"></i>
                        <div>
                            <span class="detail-label">Endereço</span>
                            <span class="detail-value"><?= LOCAL_END ?><br><?= LOCAL_CEP ?></span>
                        </div>
                    </li>
                    <li>
                        <i class="bi bi-calendar-event-fill"></i>
                        <div>
                            <span class="detail-label">Data e Hora</span>
                            <span class="detail-value"><?= DIA_SEMANA ?>, <?= DATA_BR ?> às <?= HORA ?></span>
                        </div>
                    </li>
                    <li>
                        <i class="bi bi-stars"></i>
                        <div>
                            <span class="detail-label">Dress Code</span>
                            <span class="detail-value"><?= DRESS_CODE ?></span>
                        </div>
                    </li>
                </ul>

                <a href="<?= LOCAL_MAPS ?>" target="_blank" rel="noopener"
                   class="btn-primary-custom local-maps-btn">
                    <i class="bi bi-map"></i> Abrir no Google Maps
                </a>
            </div>

            <!-- Mapa embed -->
            <div class="local-map-wrapper">
                <iframe
                    src="<?= htmlspecialchars(LOCAL_EMBED) ?>"
                    width="100%"
                    height="100%"
                    style="border:0;"
                    allowfullscreen=""
                    loading="lazy"
                    referrerpolicy="no-referrer-when-downgrade"
                    title="Mapa do local do casamento">
                </iframe>
            </div>

        </div>

        <!-- Cards informativos -->
        <div class="local-extras hidden" data-animation="fadeInUp">

            <div class="local-extra-card">
                <i class="bi bi-car-front-fill"></i>
                <h4>Estacionamento</h4>
                <p>O espaço conta com estacionamento gratuito no local. Basta informar ao manobrista que você é convidado(a) do casamento.</p>
            </div>

            <div class="local-extra-card">
                <i class="bi bi-bus-front-fill"></i>
                <h4>Transporte Público</h4>
                <p>O espaço é acessível via ônibus. Consulte as linhas que atendem o bairro Água Azul em Guarulhos pelo app da SPTrans.</p>
            </div>

            <div class="local-extra-card">
                <i class="bi bi-suit-heart-fill"></i>
                <h4>Dress Code</h4>
                <p><strong><?= DRESS_CODE ?></strong> — Vista-se com elegância e conforto para celebrar conosco. Evite cores que possam se confundir com o traje da noiva.</p>
            </div>

            <div class="local-extra-card">
                <i class="bi bi-telephone-fill"></i>
                <h4>Dúvidas?</h4>
                <p>Em caso de dúvidas ou imprevistos, confirme sua presença pelo site ou entre em contato com os noivos.</p>
                <a href="<?= BASE_URL ?>/confirmacao" class="btn-outline-custom" style="margin-top:12px;">
                    <i class="bi bi-check-circle"></i> Confirmar Presença
                </a>
            </div>

        </div>

    </div>

</main>

<?php require_once ROOT_DIR . '/includes/_footer.php'; ?>
