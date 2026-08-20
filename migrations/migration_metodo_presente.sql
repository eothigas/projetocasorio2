-- ============================================================
-- Migração: métodos de presentear (Pix / loja externa / manual)
-- Execute: mysql -u root -p casamento_db < migration_metodo_presente.sql
-- ============================================================

USE casamento_db;

ALTER TABLE reservas
    ADD COLUMN metodo ENUM('pix','loja','manual') NOT NULL DEFAULT 'pix' AFTER status;
