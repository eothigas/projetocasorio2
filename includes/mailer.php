<?php
/**
 * Envio de e-mail de agradecimento por presente, via mail() nativo do PHP
 * (sendmail da hospedagem — sem SMTP/caixa de e-mail própria).
 * Layout segue a identidade visual do site (gradiente azul, Great Vibes,
 * Playfair Display, Poppins — ver src/css/estilo-padrão.css).
 */

/**
 * @param string      $emailConvidado E-mail de destino. Chamador deve garantir não-vazio.
 * @param string      $nomeConvidado  Nome de quem presenteou.
 * @param string      $nomePresente   Nome do presente/cota.
 * @param float|null  $valor          Valor da contribuição (cota) ou null (presente único).
 */
function enviarEmailAgradecimentoPresente(
    string $emailConvidado,
    string $nomeConvidado,
    string $nomePresente,
    ?float $valor = null
): bool {
    $host = preg_replace('/:\d+$/', '', $_SERVER['HTTP_HOST'] ?? 'localhost');
    $from = 'agradecimento@' . $host;

    $primeiroNome = trim(explode(' ', trim($nomeConvidado))[0]);
    $assunto      = 'Obrigado pelo presente, ' . $primeiroNome . '! 💙';

    $detalheValor = $valor !== null
        ? '<p style="margin:0 0 22px;font-family:\'Poppins\',Arial,sans-serif;font-size:14px;color:#336fa5;">Sua contribuição: <strong>R$ ' . number_format($valor, 2, ',', '.') . '</strong></p>'
        : '';

    $nomeEsc     = htmlspecialchars($primeiroNome, ENT_QUOTES);
    $presenteEsc = htmlspecialchars($nomePresente, ENT_QUOTES);
    $noivaEsc    = htmlspecialchars(NOIVA, ENT_QUOTES);
    $noivoEsc    = htmlspecialchars(NOIVO, ENT_QUOTES);

    $corpo = <<<HTML
<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>{$assunto}</title>
</head>
<body style="margin:0;padding:0;background-color:#f0f8ff;font-family:'Poppins',Arial,sans-serif;">
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background-color:#f0f8ff;padding:32px 16px;">
  <tr>
    <td align="center">
      <table role="presentation" width="600" cellpadding="0" cellspacing="0" style="max-width:600px;width:100%;background:#ffffff;border-radius:16px;overflow:hidden;box-shadow:0 4px 24px rgba(4,35,66,0.10);">

        <!-- Header: gradiente da identidade -->
        <tr>
          <td align="center" style="background:linear-gradient(135deg,#042342 0%,#144776 60%,#336fa5 100%);padding:26px 24px;">
            <div style="font-family:'Great Vibes',cursive,Georgia,serif;font-size:40px;line-height:1.1;color:#ffffff;">
              Querido(a) {$nomeEsc},
            </div>
          </td>
        </tr>

        <!-- Corpo -->
        <tr>
          <td style="padding:40px 40px 8px;">
            <p style="margin:0 0 18px;font-family:'Poppins',Arial,sans-serif;font-size:15px;line-height:1.75;color:#042342;">
              Hoje, ao recebermos a confirmação do seu presente,
              <span style="font-family:'Poppins',Arial,sans-serif;color:#336fa5;font-weight:600;">{$presenteEsc}</span>,
              nosso coração se encheu de gratidão. ❤️
            </p>

            {$detalheValor}

            <p style="margin:0 0 18px;font-family:'Poppins',Arial,sans-serif;font-size:15px;line-height:1.75;color:#042342;">
              Mais do que o presente em si, queremos agradecer pelo carinho, pelo cuidado e por
              escolher fazer parte de um dos momentos mais especiais das nossas vidas.
            </p>

            <p style="margin:0 0 18px;font-family:'Poppins',Arial,sans-serif;font-size:15px;line-height:1.75;color:#042342;">
              Estamos vivendo a alegria de começar um novo capítulo, cheio de sonhos, expectativas
              e planos. E saber que temos pessoas tão especiais ao nosso lado, celebrando, torcendo
              e compartilhando conosco esse momento, torna tudo ainda mais bonito e significativo.
            </p>

            <p style="margin:0 0 18px;font-family:'Poppins',Arial,sans-serif;font-size:15px;line-height:1.75;color:#042342;">
              Cada gesto de carinho ficará guardado em nossa memória e em nosso coração. Somos
              verdadeiramente gratos por termos você fazendo parte da nossa história.
            </p>

            <p style="margin:0 0 8px;font-family:'Poppins',Arial,sans-serif;font-size:15px;line-height:1.75;color:#042342;">
              Oramos para que Deus retribua todo esse carinho, abençoe profundamente a sua vida e
              esteja presente em cada capítulo da sua história, assim como tem cuidado da nossa.
            </p>
          </td>
        </tr>

        <!-- Versículo -->
        <tr>
          <td style="padding:16px 40px 8px;" align="center">
            <p style="margin:0 0 6px;font-family:'Playfair Display',Georgia,serif;font-style:italic;font-size:19px;line-height:1.5;color:#144776;">
              &ldquo;Nós amamos porque ele nos amou primeiro.&rdquo;
            </p>
            <p style="margin:0;font-family:'Poppins',Arial,sans-serif;font-size:11px;letter-spacing:.14em;text-transform:uppercase;color:#4e8bb8;">
              1 João 4:19
            </p>
          </td>
        </tr>

        <!-- Assinatura -->
        <tr>
          <td align="center" style="padding:28px 40px 44px;">
            <p style="margin:0 0 6px;font-family:'Poppins',Arial,sans-serif;font-size:13px;color:#336fa5;">
              Com todo nosso carinho e gratidão,
            </p>
            <p style="margin:0;font-family:'Great Vibes',cursive,Georgia,serif;font-size:38px;color:#042342;">
              {$noivaEsc} &amp; {$noivoEsc}
            </p>
          </td>
        </tr>

        <!-- Rodapé -->
        <tr>
          <td align="center" style="background:#042342;padding:20px 24px;">
            <p style="margin:0;font-family:'Poppins',Arial,sans-serif;font-size:11px;color:#8aabd6;">
              Mensagem automática — por favor, não responda este e-mail.
            </p>
          </td>
        </tr>

      </table>
    </td>
  </tr>
</table>
</body>
</html>
HTML;

    $headers  = "MIME-Version: 1.0\r\n";
    $headers .= "Content-Type: text/html; charset=UTF-8\r\n";
    $headers .= "From: {$noivaEsc} & {$noivoEsc} <{$from}>\r\n";
    $headers .= "Reply-To: {$from}\r\n";

    try {
        return @mail($emailConvidado, '=?UTF-8?B?' . base64_encode($assunto) . '?=', $corpo, $headers);
    } catch (Throwable $e) {
        error_log('[mailer] falha ao enviar e-mail: ' . $e->getMessage());
        return false;
    }
}

/**
 * Notifica os noivos (EMAILS_NOTIFICACAO_PRESENTE) que um presente foi
 * confirmado. Corpo diferente do e-mail de agradecimento — aviso interno,
 * não a carta pro convidado.
 *
 * @param string      $nomeConvidado
 * @param string      $emailConvidado E-mail do convidado, ou '' se não informado.
 * @param string      $nomePresente
 * @param float|null  $valor          Valor da contribuição (cota) ou null (presente único).
 * @param string      $metodo         'pix', 'loja' ou 'manual'.
 */
function enviarEmailNotificacaoNoivos(
    string $nomeConvidado,
    string $emailConvidado,
    string $nomePresente,
    ?float $valor,
    string $metodo
): bool {
    $destinatarios = EMAILS_NOTIFICACAO_PRESENTE;
    if (empty($destinatarios)) {
        return false;
    }

    $host = preg_replace('/:\d+$/', '', $_SERVER['HTTP_HOST'] ?? 'localhost');
    $from = 'agradecimento@' . $host;

    $noivaEsc    = htmlspecialchars(NOIVA, ENT_QUOTES);
    $noivoEsc    = htmlspecialchars(NOIVO, ENT_QUOTES);
    $nomeEsc     = htmlspecialchars($nomeConvidado, ENT_QUOTES);
    $presenteEsc = htmlspecialchars($nomePresente, ENT_QUOTES);
    $emailEsc    = $emailConvidado !== '' ? htmlspecialchars($emailConvidado, ENT_QUOTES) : 'não informado';

    $metodoLabel = [
        'pix'    => 'Pix',
        'loja'   => 'Comprado na loja',
        'manual' => 'Confirmado manualmente (em mãos / dinheiro)',
    ][$metodo] ?? ucfirst($metodo);

    $linhaValor = $valor !== null
        ? '<tr><td style="padding:6px 0;color:#4c5e70;font-size:13px;">Valor</td><td style="padding:6px 0;color:#042342;font-size:13px;font-weight:600;">R$ ' . number_format($valor, 2, ',', '.') . '</td></tr>'
        : '';

    $assunto = 'Novo presente confirmado: ' . $nomePresente;

    $corpo = <<<HTML
<!DOCTYPE html>
<html lang="pt-BR">
<head><meta charset="UTF-8"><title>{$assunto}</title></head>
<body style="margin:0;padding:0;background-color:#f0f8ff;font-family:'Poppins',Arial,sans-serif;">
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background-color:#f0f8ff;padding:32px 16px;">
  <tr>
    <td align="center">
      <table role="presentation" width="560" cellpadding="0" cellspacing="0" style="max-width:560px;width:100%;background:#ffffff;border-radius:12px;overflow:hidden;box-shadow:0 4px 24px rgba(4,35,66,0.10);">

        <tr>
          <td style="background:#144776;padding:20px 28px;">
            <p style="margin:0;font-family:'Poppins',Arial,sans-serif;font-size:12px;letter-spacing:.1em;text-transform:uppercase;color:#9ecae3;">Lista de presentes</p>
            <p style="margin:4px 0 0;font-family:'Poppins',Arial,sans-serif;font-size:18px;font-weight:600;color:#ffffff;">Novo presente confirmado 🎁</p>
          </td>
        </tr>

        <tr>
          <td style="padding:24px 28px 8px;">
            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="border-collapse:collapse;">
              <tr><td style="padding:6px 0;color:#4c5e70;font-size:13px;width:120px;">Presente</td><td style="padding:6px 0;color:#042342;font-size:13px;font-weight:600;">{$presenteEsc}</td></tr>
              <tr><td style="padding:6px 0;color:#4c5e70;font-size:13px;">Convidado</td><td style="padding:6px 0;color:#042342;font-size:13px;font-weight:600;">{$nomeEsc}</td></tr>
              <tr><td style="padding:6px 0;color:#4c5e70;font-size:13px;">E-mail</td><td style="padding:6px 0;color:#042342;font-size:13px;">{$emailEsc}</td></tr>
              <tr><td style="padding:6px 0;color:#4c5e70;font-size:13px;">Método</td><td style="padding:6px 0;color:#042342;font-size:13px;">{$metodoLabel}</td></tr>
              {$linhaValor}
            </table>
          </td>
        </tr>

        <tr>
          <td style="padding:20px 28px 26px;">
            <p style="margin:0;font-family:'Poppins',Arial,sans-serif;font-size:12px;color:#8098ac;">
              O convidado já recebeu o e-mail de agradecimento automaticamente.
            </p>
          </td>
        </tr>

      </table>
    </td>
  </tr>
</table>
</body>
</html>
HTML;

    $headers  = "MIME-Version: 1.0\r\n";
    $headers .= "Content-Type: text/html; charset=UTF-8\r\n";
    $headers .= "From: {$noivaEsc} & {$noivoEsc} <{$from}>\r\n";
    $headers .= "Reply-To: {$from}\r\n";

    try {
        return @mail(implode(',', $destinatarios), '=?UTF-8?B?' . base64_encode($assunto) . '?=', $corpo, $headers);
    } catch (Throwable $e) {
        error_log('[mailer] falha ao notificar noivos: ' . $e->getMessage());
        return false;
    }
}
