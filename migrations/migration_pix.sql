-- --------------------------------------------------------
-- Novas colunas em presentes: tipo do presente e valor já
-- arrecadado (usado quando tipo = 'cota', presente pago em
-- partes por vários convidados)
-- --------------------------------------------------------
ALTER TABLE presentes
    ADD COLUMN tipo             ENUM('unico','cota') NOT NULL DEFAULT 'unico' AFTER categoria,
    ADD COLUMN valor_arrecadado DECIMAL(10,2) NOT NULL DEFAULT 0 AFTER preco;

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
    id           INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    mp_payment_id VARCHAR(50) NULL,
    tipo         VARCHAR(50) NULL,
    payload      TEXT NULL,
    resultado    VARCHAR(255) NULL,
    criado_em    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_mp_payment_id (mp_payment_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Remove sistema antigo de escolha sem pagamento (substituído
-- por reservas)
-- --------------------------------------------------------
DROP TABLE IF EXISTS presentes_escolhas;
