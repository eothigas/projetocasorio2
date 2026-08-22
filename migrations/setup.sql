-- ============================================================
-- Sistema de Casamento — Setup do Banco de Dados
-- Execute: mysql -u root -p < setup.sql
-- ============================================================

CREATE DATABASE IF NOT EXISTS casamento_db
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

USE casamento_db;

-- --------------------------------------------------------
-- Confirmações de presença (RSVP)
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS confirmacoes (
    id            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    nome          VARCHAR(150)     NOT NULL,
    email         VARCHAR(150),
    telefone      VARCHAR(25),
    acompanhantes TINYINT UNSIGNED DEFAULT 0 COMMENT 'Número de acompanhantes (sem contar o titular)',
    restricoes    TEXT             COMMENT 'Restrições alimentares ou observações',
    mensagem      TEXT,
    confirmado_em DATETIME         DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_nome          (nome),
    INDEX idx_confirmado_em (confirmado_em)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Lista de presentes
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS presentes (
    id               INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    nome             VARCHAR(200)   NOT NULL,
    descricao        TEXT,
    categoria        VARCHAR(80)    DEFAULT 'Geral',
    tipo             ENUM('unico','cota') NOT NULL DEFAULT 'unico',
    preco            DECIMAL(10,2),
    valor_arrecadado DECIMAL(10,2) NOT NULL DEFAULT 0,
    link             TEXT,
    imagem           VARCHAR(300),
    comprado         TINYINT(1)     DEFAULT 0,
    comprado_por     VARCHAR(150),
    comprado_em      DATETIME,
    INDEX idx_categoria (categoria),
    INDEX idx_comprado  (comprado)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Mensagens para os noivos
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS mensagens (
    id        INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    nome      VARCHAR(150) NOT NULL,
    mensagem  TEXT         NOT NULL,
    aprovado  TINYINT(1)   DEFAULT 0,
    criado_em DATETIME     DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_aprovado  (aprovado),
    INDEX idx_criado_em (criado_em)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Reservas de presente com pagamento Pix
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS reservas (
    id                 INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    presente_id        INT UNSIGNED NOT NULL,
    nome_convidado     VARCHAR(150) NOT NULL,
    email_convidado    VARCHAR(150),
    valor              DECIMAL(10,2) NOT NULL,
    status             ENUM('pendente','pago','expirado','cancelado') NOT NULL DEFAULT 'pendente',
    metodo             ENUM('pix','loja','manual') NOT NULL DEFAULT 'pix',
    mp_payment_id      VARCHAR(50)  NULL,
    external_reference VARCHAR(50)  NOT NULL,
    pix_qr_code        TEXT NULL,
    pix_qr_code_base64 LONGTEXT NULL,
    criado_em          DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    pago_em            DATETIME NULL,
    expira_em          DATETIME NULL,
    UNIQUE KEY uq_external_reference (external_reference),
    FOREIGN KEY (presente_id) REFERENCES presentes(id) ON DELETE CASCADE,
    INDEX idx_status      (status),
    INDEX idx_presente_id (presente_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Log de notificações do webhook, pra depuração
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS webhook_logs (
    id            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    mp_payment_id VARCHAR(50) NULL,
    tipo          VARCHAR(50) NULL,
    payload       TEXT NULL,
    resultado     VARCHAR(255) NULL,
    criado_em     DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_mp_payment_id (mp_payment_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Configurações gerais do sistema
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS configuracoes (
    chave VARCHAR(50)   NOT NULL PRIMARY KEY,
    valor VARCHAR(1000) NOT NULL DEFAULT ''
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT IGNORE INTO configuracoes (chave, valor) VALUES ('confirmacao_aberta', '0');

-- --------------------------------------------------------
-- Grupos/famílias de convidados (1 responsável recebe o
-- convite e confirma pelos dependentes do grupo)
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS grupos (
    id            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    nome_grupo    VARCHAR(150) NOT NULL,
    email         VARCHAR(150),
    respondido    TINYINT(1)   NOT NULL DEFAULT 0,
    respondido_em DATETIME,
    criado_em     DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_respondido (respondido)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Lista de convidados (gerada pelo admin)
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS convidados (
    id            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    nome          VARCHAR(150) NOT NULL,
    grupo_id      INT UNSIGNED NOT NULL,
    responsavel   TINYINT(1)   NOT NULL DEFAULT 0,
    codigo        VARCHAR(10)  NOT NULL,
    confirmado    TINYINT(1)   NOT NULL DEFAULT 0,
    confirmado_em DATETIME,
    criado_em     DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_codigo (codigo),
    FOREIGN KEY (grupo_id) REFERENCES grupos(id) ON DELETE CASCADE,
    INDEX idx_confirmado (confirmado),
    INDEX idx_grupo_id (grupo_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Presentes de exemplo
-- --------------------------------------------------------
INSERT INTO presentes (nome, descricao, categoria, preco, link) VALUES
('Jogo de Panelas Tramontina',   'Conjunto antiaderente 5 peças, compatível com indução',       'Cozinha',  459.90, '#'),
('Liquidificador Mondial',       'Turbo 1000W, copo de vidro 2L',                               'Cozinha',  189.90, '#'),
('Jogo de Cama Queen 300 fios',  'Lençol, 2 fronhas e edredom cobre-leito, cor creme',          'Quarto',   349.90, '#'),
('Cafeteira Nespresso Essenza',  'Cafeteira com 20 cápsulas incluídas, preto fosco',            'Cozinha',  599.90, '#'),
('Aspirador Robô Inteligente',   'Programável via app, Wi-Fi, 120 min de autonomia',            'Casa',    1299.90, '#'),
('Churrasqueira Elétrica Grill', 'Tampa com termômetro, 1800W, antiaderente',                   'Lazer',    299.90, '#'),
('Kit Toalhas de Banho 6 peças', 'Algodão egípcio felpudo — 2 banho, 2 rosto, 2 piso',         'Quarto',   249.90, '#'),
('Micro-ondas 30L Inverter',     'Grill, espeto rotativo, 10 níveis de potência',               'Cozinha',  799.90, '#'),
('Jogo de Jantar 42 peças',      'Porcelana premium, serviço completo para 6 pessoas',          'Mesa',     389.90, '#'),
('Adega Climatizada 8 garrafas', 'Compressor silencioso, display LED, porta espelhada',         'Lazer',    649.90, '#'),
('Air Fryer Digital 6L',         'Touch screen, 10 funções pré-programadas, alça fria',         'Cozinha',  499.90, '#'),
('Jogo de Taças Cristal 12 pç',  'Taças de vinho tinto, branco e champagne em cristal',        'Mesa',     289.90, '#');
