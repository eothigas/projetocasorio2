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
    id           INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    nome         VARCHAR(200)   NOT NULL,
    descricao    TEXT,
    categoria    VARCHAR(80)    DEFAULT 'Geral',
    preco        DECIMAL(10,2),
    link         TEXT,
    imagem       VARCHAR(300),
    comprado     TINYINT(1)     DEFAULT 0,
    comprado_por VARCHAR(150),
    comprado_em  DATETIME,
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
