# Prompt para Claude Code — Integração Pix (Mercado Pago) na Lista de Presentes

## Contexto

Estou construindo o site do meu casamento em **PHP + MySQL**, hospedado em hosting
compartilhado (padrão Hostinger). O site já tem uma página pública de lista de
presentes e um painel admin simples onde cada presente pode ser reservado por um
convidado (nome + presente escolhido).

Preciso implementar a parte de **pagamento via Pix usando a API do Mercado Pago**,
com confirmação automática por webhook — ou seja, quando o convidado pagar, o
presente deve ser marcado como "pago" no banco sem eu precisar fazer nada manual.

Já tenho conta no Mercado Pago (pessoa física) e vou gerar o Access Token de produção
no painel de desenvolvedores deles.

## O que preciso que você construa

### 1. Schema do banco (MySQL)

Ajuste/crie as tabelas necessárias, mantendo compatível com o que já existe:

```sql
presentes (
  id, nome, descricao, imagem_url, valor, tipo ENUM('unico','cota'),
  valor_arrecadado DEFAULT 0, ativo BOOLEAN DEFAULT true
)

reservas (
  id, presente_id (FK), nome_convidado, email_convidado (opcional),
  valor, status ENUM('pendente','pago','expirado','cancelado') DEFAULT 'pendente',
  mp_payment_id VARCHAR(50) NULL,        -- id do pagamento no Mercado Pago
  external_reference VARCHAR(50) UNIQUE, -- ex: reserva_48
  pix_qr_code TEXT NULL,                 -- copia e cola
  pix_qr_code_base64 TEXT NULL,          -- imagem do QR
  criado_em DATETIME,
  pago_em DATETIME NULL,
  expira_em DATETIME NULL
)
```

Adicione índices necessários (principalmente em `external_reference` e `status`).

### 2. Endpoint: gerar cobrança Pix

Arquivo tipo `api/gerar-pix.php`, chamado quando o convidado confirma a reserva de
um presente:

- Recebe `presente_id` + dados do convidado
- Cria a `reserva` no banco com `status = 'pendente'` e `external_reference` único
  (ex: `reserva_{id}`)
- Chama a API do Mercado Pago (`POST /v1/payments`) com `payment_method_id: pix`,
  o valor do presente e o `external_reference`
- Salva `mp_payment_id`, `pix_qr_code` e `pix_qr_code_base64` no banco
- Define `expira_em` (ex: 30 minutos a partir de agora — usar o campo
  `date_of_expiration` da própria API do Mercado Pago)
- Retorna JSON pro front-end com o QR Code e o código copia-e-cola pra exibir na tela

### 3. Endpoint: webhook de confirmação

Arquivo tipo `webhook/mercadopago.php`, registrado como URL de notificação no
painel do Mercado Pago:

- Recebe o POST de notificação (`{ type: "payment", data: { id: "..." } }`)
- **Valida a assinatura do webhook** usando o header `x-signature` e `x-request-id`
  conforme a documentação oficial do Mercado Pago (não confiar cegamente no payload)
- Após validar, consulta a API (`GET /v1/payments/{id}`) pra confirmar o status real
  do pagamento — nunca usar só o que veio no payload da notificação
- Se `status === "approved"`:
  - Localiza a reserva pelo `external_reference`
  - Atualiza `status = 'pago'`, `pago_em = NOW()`
  - Se o presente for do tipo `cota`, incrementa `valor_arrecadado` na tabela
    `presentes`
- **Operação idempotente**: o Mercado Pago pode reenviar a mesma notificação
  várias vezes — o código não pode duplicar valores nem quebrar se rodar 2x
  pra mesma reserva
- Responder sempre `200 OK` rapidamente (processar de forma que não trave o
  Mercado Pago esperando)
- Logar cada notificação recebida (id, tipo, resultado do processamento) em uma
  tabela `webhook_logs` ou arquivo de log, pra eu conseguir depurar se algo falhar

### 4. Rotina de expiração

Um script simples (pode ser chamado via cron ou verificado a cada carregamento do
admin) que marca como `expirado` toda reserva com `status = 'pendente'` e
`expira_em < NOW()`, liberando o presente pra ser reservado de novo.

### 5. Segurança

- Access Token do Mercado Pago deve vir de variável de ambiente (`.env` ou
  config fora do webroot), nunca hardcoded
- Validar sempre a assinatura do webhook antes de processar qualquer coisa
- Sanitizar `external_reference` e todo input antes de usar em queries (usar
  prepared statements / PDO)
- Endpoint de gerar Pix deve ter alguma proteção básica contra flood (rate limit
  simples por IP ou por sessão), pra evitar gerar cobranças em massa

### 6. Stack e padrões

- PHP puro (sem framework), seguindo padrão simples e direto, sem dependências
  externas além de uma lib HTTP básica (cURL nativo do PHP está OK)
- PDO com prepared statements pra tudo que toca o banco
- Sem exposição de credenciais em nenhum retorno JSON
- Comente o código nos pontos de decisão importantes (validação de assinatura,
  idempotência, etc.), porque preciso entender e manter isso depois

## Entregáveis esperados

1. Migration/SQL das tabelas
2. `api/gerar-pix.php`
3. `webhook/mercadopago.php`
4. Script/rotina de expiração de reservas pendentes
5. Um `README` curto explicando como configurar o Access Token e como registrar
   a URL do webhook no painel do Mercado Pago
