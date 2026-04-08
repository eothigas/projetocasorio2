<footer id="footer">
    <div class="container">

        <div class="footer-couple">
            <p class="footer-names"><?= NOIVA ?> &amp; <?= NOIVO ?></p>
            <div class="footer-divider">
                <span></span>
                <i class="bi bi-heart-fill"></i>
                <span></span>
            </div>
            <p class="footer-date"><?= DIA_SEMANA ?>, <?= DIA ?> de <?= MES ?> de <?= ANO ?></p>
        </div>

        <nav class="footer-nav">
            <a href="<?= BASE_URL ?>/index.php">Início</a>
            <a href="<?= BASE_URL ?>/pages/presentes.php">Presentes</a>
            <a href="<?= BASE_URL ?>/pages/local.php">Local</a>
            <a href="<?= BASE_URL ?>/pages/confirmacao.php">Confirmar Presença</a>
            <a href="<?= BASE_URL ?>/pages/mensagens.php">Mensagens</a>
        </nav>

        <p class="footer-copy">
            &copy; <?= ANO ?> &middot; <?= NOIVA ?> &amp; <?= NOIVO ?> &middot; Feito com <i class="bi bi-heart-fill" style="color:var(--blue4);font-size:.75rem;"></i>
        </p>

    </div>
</footer>

<!-- Scripts -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="<?= BASE_URL ?>/src/js/base.js"></script>
<?php foreach ($pageJS ?? [] as $js): ?>
<script src="<?= BASE_URL . '/src/js/' . htmlspecialchars($js) ?>"></script>
<?php endforeach; ?>
</body>
</html>
