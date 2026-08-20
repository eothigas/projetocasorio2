<?php
/**
 * Expira reservas Pix pendentes que passaram do prazo, liberando o
 * presente para nova tentativa. Rodar via cron (ex.: a cada 5 minutos) ou
 * chamar manualmente pela CLI: php cron/expirar-reservas.php
 */
require_once dirname(__DIR__) . '/config/config.php';
require_once ROOT_DIR . '/includes/db.php';

$db = getDB();

$stmt = $db->prepare(
    "UPDATE reservas SET status = 'expirado'
     WHERE status = 'pendente' AND expira_em IS NOT NULL AND expira_em < NOW()"
);
$stmt->execute();

if (PHP_SAPI === 'cli') {
    echo $stmt->rowCount() . " reserva(s) expirada(s).\n";
}
