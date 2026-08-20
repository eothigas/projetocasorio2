<?php
/**
 * Credenciais do Mercado Pago guardadas criptografadas na tabela
 * `configuracoes` (chaves mp_access_token / mp_webhook_secret), em vez
 * de texto plano em config.php.
 */
require_once __DIR__ . '/crypto.php';

function getConfiguracaoSegura(string $chave): ?string
{
    $db   = getDB();
    $stmt = $db->prepare("SELECT valor FROM configuracoes WHERE chave = ?");
    $stmt->execute([$chave]);
    $valor = $stmt->fetchColumn();

    return $valor ? decryptSecret($valor) : null;
}

function setConfiguracaoSegura(string $chave, string $valorPlano): void
{
    $db        = getDB();
    $encrypted = encryptSecret($valorPlano);

    $db->prepare(
        "INSERT INTO configuracoes (chave, valor) VALUES (?, ?)
         ON DUPLICATE KEY UPDATE valor = VALUES(valor)"
    )->execute([$chave, $encrypted]);
}

function getMpAccessToken(): string
{
    static $token = null;
    if ($token === null) {
        $token = getConfiguracaoSegura('mp_access_token') ?? '';
    }
    return $token;
}

function getMpWebhookSecret(): string
{
    static $secret = null;
    if ($secret === null) {
        $secret = getConfiguracaoSegura('mp_webhook_secret') ?? '';
    }
    return $secret;
}
