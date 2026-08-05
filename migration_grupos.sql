-- ============================================================
-- Migração: Grupos/Famílias de convidados
-- Execute em bancos já existentes (setup.sql já rodado antes):
-- mysql -u root -p casamento_db < migration_grupos.sql
-- ============================================================

USE casamento_db;

-- --------------------------------------------------------
-- Tabela de grupos (famílias) de convidados
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS grupos (
    id            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    nome_grupo    VARCHAR(150) NOT NULL,
    respondido    TINYINT(1)   NOT NULL DEFAULT 0,
    respondido_em DATETIME,
    criado_em     DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_respondido (respondido)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Novas colunas em convidados
-- --------------------------------------------------------
ALTER TABLE convidados
    ADD COLUMN grupo_id    INT UNSIGNED NULL AFTER nome,
    ADD COLUMN responsavel TINYINT(1)   NOT NULL DEFAULT 0 AFTER grupo_id;

-- --------------------------------------------------------
-- Backfill: cada convidado existente vira um grupo solo,
-- marcado como responsável do próprio grupo.
-- Usa tag temporária única (#id) pra casar grupo <-> convidado
-- sem depender de window functions.
-- --------------------------------------------------------
INSERT INTO grupos (nome_grupo, respondido, respondido_em)
SELECT CONCAT('#', id), confirmado, confirmado_em FROM convidados;

UPDATE convidados c
JOIN grupos g ON g.nome_grupo = CONCAT('#', c.id)
SET c.grupo_id = g.id, c.responsavel = 1;

UPDATE grupos g
JOIN convidados c ON c.grupo_id = g.id
SET g.nome_grupo = c.nome;

-- --------------------------------------------------------
-- Trava grupo_id como obrigatório + FK
-- --------------------------------------------------------
ALTER TABLE convidados
    MODIFY grupo_id INT UNSIGNED NOT NULL,
    ADD CONSTRAINT fk_convidado_grupo FOREIGN KEY (grupo_id) REFERENCES grupos(id) ON DELETE CASCADE,
    ADD INDEX idx_grupo_id (grupo_id);
