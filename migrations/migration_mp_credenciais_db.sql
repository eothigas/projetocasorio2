-- ============================================================
-- Migração: credenciais Mercado Pago criptografadas no banco
-- Execute: mysql -u root -p casamento_db < migration_mp_credenciais_db.sql
-- Depois rode: php cron/seed-mp-credenciais.php (uma vez, local)
-- pra migrar o token que estava em config.php pro banco.
-- ============================================================

USE casamento_db;

ALTER TABLE configuracoes
    MODIFY COLUMN valor VARCHAR(1000) NOT NULL DEFAULT '';
