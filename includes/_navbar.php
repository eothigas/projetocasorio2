<?php
// Detecta qual página está ativa para highlight no menu
$currentPage = basename($_SERVER['SCRIPT_NAME'], '.php');
$currentDir  = basename(dirname($_SERVER['SCRIPT_NAME']));

function navActive(string $page, string $current): string {
    return ($current === $page) ? 'active' : '';
}
?>
<nav class="navbar navbar-expand-lg" id="menu">
    <div class="container">

        <!-- Logo -->
        <a class="navbar-brand align-items-center text-center" href="<?= BASE_URL ?>/">
            <span class="brand-names"><?= NOIVA ?>  &AMP;  <?= NOIVO ?></span>
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
                    <a class="nav-link <?= navActive('index', $currentPage) ?>"
                       href="<?= BASE_URL ?>/">
                        <i class="bi bi-house-heart me-1"></i>Início
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?= navActive('presentes', $currentPage) ?>"
                       href="<?= BASE_URL ?>/presentes">
                        <i class="bi bi-gift me-1"></i>Presentes
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?= navActive('local', $currentPage) ?>"
                       href="<?= BASE_URL ?>/local">
                        <i class="bi bi-geo-alt me-1"></i>Local
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?= navActive('mensagens', $currentPage) ?>"
                       href="<?= BASE_URL ?>/mensagens">
                        <i class="bi bi-chat-heart me-1"></i>Mensagens
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link nav-cta <?= navActive('confirmacao', $currentPage) ?>"
                       href="<?= BASE_URL ?>/confirmacao">
                        <i class="bi bi-check-circle me-1"></i>Confirmar Presença
                    </a>
                </li>
            </ul>
        </div>

    </div>
</nav>
