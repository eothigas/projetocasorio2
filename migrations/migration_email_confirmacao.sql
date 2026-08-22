-- Adiciona e-mail opcional ao grupo, preenchido pelo responsável ao
-- confirmar presença — usado para enviar e-mail de confirmação.
ALTER TABLE grupos
    ADD COLUMN email VARCHAR(150) NULL AFTER nome_grupo;
