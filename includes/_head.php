<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="<?= htmlspecialchars(NOIVA . ' &amp; ' . NOIVO . ' — ' . DIA_SEMANA . ', ' . DATA_BR) ?>">
    <meta name="base-url" content="<?= BASE_URL ?>">
    <title><?= htmlspecialchars($pageTitle ?? (NOIVA . ' &amp; ' . NOIVO . ' · ' . DATA_BR)) ?></title>

    <!-- Favicon -->
    <link rel="shortcut icon" type="image/ico" href="<?= BASE_URL ?>/images/icon.ico">

    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Great+Vibes&family=Playfair+Display:wght@400;500;600&family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">

    <!-- Bootstrap 5 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">

    <!-- CSS Base -->
    <link rel="stylesheet" href="<?= BASE_URL ?>/src/css/estilo-padrão.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>/src/css/elementos/navbar.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>/src/css/elementos/animacoes.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>/src/css/elementos/footer.css">

    <!-- CSS da Página -->
    <?php foreach ($pageCSS ?? [] as $css): ?>
    <link rel="stylesheet" href="<?= BASE_URL . '/src/css/paginas/' . htmlspecialchars($css) ?>">
    <?php endforeach; ?>
</head>
<body>
