# Pagamento Pix (Mercado Pago) — Setup

## 1. Rodar a migration

```
mysql -u root -p casamento_db < migration_pix.sql
```

Instalação nova? `setup.sql` já vem com o schema completo (não precisa rodar a migration).

## 2. Gerar o Access Token no Mercado Pago

1. Acesse https://www.mercadopago.com.br/developers/panel/app
2. Crie (ou selecione) uma aplicação
3. Em **Credenciais de teste**, copie o **Access Token** (começa com `TEST-`)
4. Cole em `config/config.php`:

```php
define('MP_ACCESS_TOKEN', 'TEST-xxxxxxxx...');
define('MP_SANDBOX', true);
```

Quando for pra produção: troque pelo Access Token de **Credenciais de produção**
(começa com `APP_USR-`) e mude `MP_SANDBOX` pra `false`.

## 3. Registrar o webhook

No painel da aplicação → **Webhooks** → **Configurar notificações**:

- URL: `https://SEU_DOMINIO/casamento/webhook/mercadopago`
- Eventos: marque **Pagamentos**
- Copie a **Chave secreta** (assinatura) gerada e cole em `config/config.php`:

```php
define('MP_WEBHOOK_SECRET', 'sua_chave_secreta_aqui');
```

Sem essa chave, o webhook não valida assinatura (`x-signature`) — funciona em
teste, mas **é obrigatório configurar antes de ir pra produção**.

## 4. Cron de expiração

No painel de hospedagem (Hostinger: hPanel → Avançado → Cron Jobs), agende:

```
*/5 * * * * php /home/SEU_USUARIO/public_html/casamento/cron/expirar-reservas.php
```

Isso libera presentes cuja reserva pendente passou dos 30 minutos sem pagamento.
O admin (`admin/presentes.php`) também roda essa limpeza a cada carregamento,
então o cron é reforço, não obrigatório.

## 5. Testando

Com `MP_SANDBOX = true` e token `TEST-...`, gere um Pix pela página de
presentes — o QR gerado é de teste e não movimenta dinheiro real. Para simular
aprovação, use os pagamentos de teste do painel Mercado Pago (Suas
integrações → Contas de teste) ou aguarde o pagamento real ao trocar pro
token de produção.

## Arquivos

| Arquivo | Função |
|---|---|
| `migration_pix.sql` | Schema: `reservas`, `webhook_logs`, colunas novas em `presentes` |
| `api/gerar-pix.php` | Cria reserva + cobrança Pix no Mercado Pago |
| `api/status-reserva.php` | Consulta de status usada pelo polling no front |
| `webhook/mercadopago.php` | Recebe notificação, valida assinatura, confirma pagamento |
| `cron/expirar-reservas.php` | Expira reservas pendentes vencidas |
