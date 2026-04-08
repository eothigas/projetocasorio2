<?php
/**
 * Configuração White-Label — Sistema de Casamento
 * Edite apenas este arquivo para personalizar o site.
 */

// === CASAL ===
define('NOIVO',  'Thiago');
define('NOIVA',  'Carol');

// === DATA E HORA ===
define('DATA_BR',    '19/12/2026');
define('DATA_ISO',   '2026-12-19T19:00:00');
define('DIA',        '19');
define('DIA_SEMANA', 'Sábado');
define('MES',        'Dezembro');
define('ANO',        '2026');
define('HORA',       '19h00');
define('MES_ANO',    'Dezembro · 2026');

// === FAMÍLIA DA NOIVA ===
define('PAI_NOIVA', 'Manoel Cordeiro de Jesus');
define('MAE_NOIVA', 'Danuzia Cordeiro Duarte');

// === FAMÍLIA DO NOIVO ===
define('PAI_NOIVO', 'Luiz Benedito Ferraz');
define('MAE_NOIVO', 'Ivone Freitas Pereira Ferraz');

// === LOCAL ===
define('LOCAL_NOME',   'Espaço Recanto Miami');
define('LOCAL_BAIRRO', 'Água Azul');
define('LOCAL_END',    'Av. Acapulco, 186 - Água Azul, Guarulhos - SP');
define('LOCAL_CEP',    '07159-505');
define('LOCAL_MAPS',   'https://maps.app.goo.gl/ZVJXXXnkGQbavP4T8');
// Substitua pelo src do iframe do Google Maps (Incorporar mapa > copiar HTML > pegar o src do iframe)
define('LOCAL_EMBED',  'https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3659.5!2d-46.5267!3d-23.4411!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x94ce5e2a47671e93%3A0xcd47d8abc04bf4f5!2sEspa%C3%A7o%20Recanto%20Miami!5e0!3m2!1spt-BR!2sbr!4v1234567890');

// === DRESS CODE ===
define('DRESS_CODE', 'Esporte Fino / Festa');

// === BANCO DE DADOS ===
$isLocal = in_array($_SERVER['HTTP_HOST'] ?? '', ['localhost', '127.0.0.1']) 
        || str_starts_with($_SERVER['HTTP_HOST'] ?? '', 'localhost:');

if ($isLocal) {
    define('DB_HOST', 'localhost');
    define('DB_USER', 'root');
    define('DB_PASS', '');
    define('DB_NAME', 'casamento_db');
} else {
    define('DB_HOST', 'localhost');
    define('DB_USER', 'u131316948_casar');
    define('DB_PASS', 'Casar*2026');
    define('DB_NAME', 'u131316948_casamento_db');
}

// === ADMIN ===
define('ADMIN_PASS', 'casamento2026');

// === CAMINHOS ===
// ROOT_DIR: caminho absoluto no filesystem até a raiz do projeto
define('ROOT_DIR', dirname(__DIR__));

// BASE_URL: caminho web base (sem trailing slash)
// Ex: '/_Dev/projetocasorio2' ou '' se estiver na raiz do servidor
// === URLs ===
define('BASE_URL', $isLocal ? '/_Dev/projetocasorio2' : '/casamento');
