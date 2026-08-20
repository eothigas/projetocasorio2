<?php
/**
 * Criptografia simétrica (AES-256-GCM) para segredos guardados no banco
 * (ex.: token do Mercado Pago). A chave mestra fica em APP_SECRET_KEY
 * (config/config.php) — nunca no banco. Se ela mudar, segredos já
 * salvos deixam de poder ser descriptografados.
 */

function encryptSecret(string $plaintext): string
{
    $key = hash('sha256', APP_SECRET_KEY, true);
    $iv  = random_bytes(12);
    $tag = '';

    $ciphertext = openssl_encrypt($plaintext, 'aes-256-gcm', $key, OPENSSL_RAW_DATA, $iv, $tag);
    if ($ciphertext === false) {
        throw new RuntimeException('Falha ao criptografar segredo.');
    }

    return base64_encode($iv . $tag . $ciphertext);
}

function decryptSecret(?string $encoded): ?string
{
    if (!$encoded) {
        return null;
    }

    $raw = base64_decode($encoded, true);
    if ($raw === false || strlen($raw) < 28) {
        return null;
    }

    $iv         = substr($raw, 0, 12);
    $tag        = substr($raw, 12, 16);
    $ciphertext = substr($raw, 28);
    $key        = hash('sha256', APP_SECRET_KEY, true);

    $plaintext = openssl_decrypt($ciphertext, 'aes-256-gcm', $key, OPENSSL_RAW_DATA, $iv, $tag);
    return $plaintext === false ? null : $plaintext;
}
