<?php
// Detecta qual página está ativa para highlight no menu
$currentPage = basename($_SERVER['SCRIPT_NAME']);
$currentDir  = basename(dirname($_SERVER['SCRIPT_NAME']));

function navActive(string $page, string $current): string {
    return ($current === $page) ? 'active' : '';
}
?>
<nav class="navbar navbar-expand-lg" id="menu">
    <div class="container">

        <!-- Logo -->
        <a class="navbar-brand" href="<?= BASE_URL ?>/index.php">
            <span class="brand-names"><?= NOIVA ?> <i class="bi bi-heart-fill brand-heart"></i> <?= NOIVO ?></span>
            <span class="brand-date"><?= DATA_BR ?></span>
        </a>

        <!-- Toggle mobile -->
        <button class="navbar-toggler" type="button"
                data-bs-toggle="collapse"
                data-bs-target="#navbarNav"
                aria-controls="navbarNav"
                aria-expanded="false"
                aria-label="Abrir menu">
            <span class="navbar-toggler-icon"></span>
        </button>

        <!-- Menu -->
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav ms-auto align-items-lg-center gap-lg-1">
                <li class="nav-item">
                    <a class="nav-link <?= navActive('index.php', $currentPage) ?>"
                       href="<?= BASE_URL ?>/index.php">
                        <i class="bi bi-house-heart me-1"></i>Início
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?= navActive('presentes.php', $currentPage) ?>"
                       href="<?= BASE_URL ?>/pages/presentes.php">
                        <i class="bi bi-gift me-1"></i>Presentes
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?= navActive('local.php', $currentPage) ?>"
                       href="<?= BASE_URL ?>/pages/local.php">
                        <i class="bi bi-geo-alt me-1"></i>Local
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link nav-cta <?= navActive('confirmacao.php', $currentPage) ?>"
                       href="<?= BASE_URL ?>/pages/confirmacao.php">
                        <i class="bi bi-check-circle me-1"></i>Confirmar Presença
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?= navActive('mensagens.php', $currentPage) ?>"
                       href="<?= BASE_URL ?>/pages/mensagens.php">
                        <i class="bi bi-chat-heart me-1"></i>Mensagens
                    </a>
                </li>
            </ul>
        </div>

    </div>
</nav>
