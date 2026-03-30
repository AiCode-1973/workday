<?php

/**
 * MailService — envio de e-mails via SMTP (sem Composer)
 * Usa PHP streams nativos para comunicação SMTP.
 * Para produção, substitua por PHPMailer ou Symfony Mailer.
 */
class MailService
{
    // -------------------------------------------------------
    // Ponto de entrada principal
    // -------------------------------------------------------

    /**
     * Envia um e-mail HTML.
     *
     * @throws RuntimeException em caso de falha SMTP
     */
    public static function send(string $to, string $subject, string $htmlBody): void
    {
        if (MAIL_DRIVER === 'log') {
            self::logMail($to, $subject, $htmlBody);
            return;
        }

        if (MAIL_DRIVER === 'smtp') {
            self::sendSmtp($to, $subject, $htmlBody);
            return;
        }

        // Fallback: mail() nativo
        $headers = implode("\r\n", [
            'MIME-Version: 1.0',
            'Content-Type: text/html; charset=UTF-8',
            'From: ' . MAIL_FROM_NAME . ' <' . MAIL_FROM . '>',
            'Reply-To: ' . MAIL_FROM,
            'X-Mailer: Workday/1.0',
        ]);
        mail($to, $subject, $htmlBody, $headers);
    }

    // -------------------------------------------------------
    // Templates prontos
    // -------------------------------------------------------

    public static function sendPasswordReset(string $to, string $name, string $token): void
    {
        $link = rtrim(APP_URL, '/') . '/auth/reset-password?token=' . urlencode($token);
        $body = self::wrap("Recuperação de senha", "
            <p>Olá, <strong>" . htmlspecialchars($name) . "</strong>!</p>
            <p>Recebemos uma solicitação para redefinir sua senha no <strong>Workday</strong>.</p>
            <p style='margin:24px 0;text-align:center;'>
              <a href='{$link}' class='btn'>Redefinir senha</a>
            </p>
            <p>Se você não solicitou isso, ignore este e-mail. O link expira em 1 hora.</p>
        ");
        self::send($to, 'Recuperação de senha — Workday', $body);
    }

    public static function sendItemAssigned(string $to, string $name, string $itemName, string $boardName, int $itemId): void
    {
        $link = rtrim(APP_URL, '/') . '/items/' . $itemId;
        $body = self::wrap("Você foi atribuído a uma tarefa", "
            <p>Olá, <strong>" . htmlspecialchars($name) . "</strong>!</p>
            <p>Você foi atribuído à tarefa <strong>" . htmlspecialchars($itemName) . "</strong>
               no quadro <em>" . htmlspecialchars($boardName) . "</em>.</p>
            <p style='margin:24px 0;text-align:center;'>
              <a href='{$link}' class='btn'>Ver tarefa</a>
            </p>
        ");
        self::send($to, "Você foi atribuído: {$itemName}", $body);
    }

    public static function sendComment(string $to, string $name, string $itemName, string $commenterName, string $commentBody, int $itemId): void
    {
        $link = rtrim(APP_URL, '/') . '/items/' . $itemId;
        $body = self::wrap("Novo comentário na sua tarefa", "
            <p>Olá, <strong>" . htmlspecialchars($name) . "</strong>!</p>
            <p><strong>" . htmlspecialchars($commenterName) . "</strong> comentou em
               <strong>" . htmlspecialchars($itemName) . "</strong>:</p>
            <blockquote style='border-left:3px solid #6366f1;padding:8px 16px;color:#555;'>
              " . nl2br(htmlspecialchars($commentBody)) . "
            </blockquote>
            <p style='margin:24px 0;text-align:center;'>
              <a href='{$link}' class='btn'>Ver tarefa</a>
            </p>
        ");
        self::send($to, "Novo comentário: {$itemName}", $body);
    }

    // -------------------------------------------------------
    // Internals
    // -------------------------------------------------------

    private static function sendSmtp(string $to, string $subject, string $htmlBody): void
    {
        $host   = MAIL_HOST;
        $port   = (int) MAIL_PORT;
        $user   = MAIL_USERNAME;
        $pass   = MAIL_PASSWORD;
        $enc    = MAIL_ENCRYPTION; // 'tls' | 'ssl' | ''

        $ctx = stream_context_create();
        if ($enc === 'ssl') {
            $sock = @stream_socket_client("ssl://{$host}:{$port}", $errno, $errstr, 10, STREAM_CLIENT_CONNECT, $ctx);
        } else {
            $sock = @stream_socket_client("tcp://{$host}:{$port}", $errno, $errstr, 10, STREAM_CLIENT_CONNECT, $ctx);
        }

        if (!$sock) throw new RuntimeException("SMTP connect failed: {$errstr} ({$errno})");

        $read = function () use ($sock): string { return fgets($sock, 515); };
        $write = function (string $cmd) use ($sock): void { fwrite($sock, $cmd . "\r\n"); };

        $read(); // greeting
        $write("EHLO " . gethostname());
        $response = '';
        while (true) { $line = $read(); $response .= $line; if ($line[3] === ' ') break; }

        if ($enc === 'tls') {
            $write("STARTTLS");
            $read();
            stream_socket_enable_crypto($sock, true, STREAM_CRYPTO_METHOD_TLS_CLIENT);
            $write("EHLO " . gethostname());
            while (true) { $line = $read(); if ($line[3] === ' ') break; }
        }

        if ($user) {
            $write("AUTH LOGIN");
            $read();
            $write(base64_encode($user));
            $read();
            $write(base64_encode($pass));
            $read();
        }

        $write("MAIL FROM:<" . MAIL_FROM . ">");
        $read();
        $write("RCPT TO:<{$to}>");
        $read();
        $write("DATA");
        $read();

        $boundary = '----=_Part_' . md5(uniqid('', true));
        $headers  = [
            "From: " . MAIL_FROM_NAME . " <" . MAIL_FROM . ">",
            "To: {$to}",
            "Subject: =?UTF-8?B?" . base64_encode($subject) . "?=",
            "MIME-Version: 1.0",
            "Content-Type: multipart/alternative; boundary=\"{$boundary}\"",
            "Date: " . date('r'),
        ];

        $plain = strip_tags($htmlBody);
        $body  = implode("\r\n", $headers) . "\r\n\r\n"
               . "--{$boundary}\r\n"
               . "Content-Type: text/plain; charset=UTF-8\r\n"
               . "Content-Transfer-Encoding: base64\r\n\r\n"
               . chunk_split(base64_encode($plain)) . "\r\n"
               . "--{$boundary}\r\n"
               . "Content-Type: text/html; charset=UTF-8\r\n"
               . "Content-Transfer-Encoding: base64\r\n\r\n"
               . chunk_split(base64_encode($htmlBody)) . "\r\n"
               . "--{$boundary}--\r\n";

        $write($body . "\r\n.");
        $read();
        $write("QUIT");
        fclose($sock);
    }

    private static function logMail(string $to, string $subject, string $htmlBody): void
    {
        $dir  = ROOT . '/storage/logs';
        if (!is_dir($dir)) mkdir($dir, 0755, true);
        $file = $dir . '/mail_' . date('Y-m-d') . '.log';
        $entry = sprintf(
            "[%s] TO:%s | SUBJECT:%s\n%s\n%s\n",
            date('Y-m-d H:i:s'),
            $to,
            $subject,
            str_repeat('-', 60),
            strip_tags($htmlBody)
        );
        file_put_contents($file, $entry, FILE_APPEND | LOCK_EX);
    }

    /** Envolve conteúdo no template HTML de e-mail */
    private static function wrap(string $title, string $content): string
    {
        $appName = APP_NAME ?? 'Workday';
        $appUrl  = rtrim(APP_URL, '/');
        return <<<HTML
<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8"/>
  <style>
    body{margin:0;padding:0;background:#f4f4f5;font-family:Inter,Arial,sans-serif;color:#111827;}
    .wrap{max-width:600px;margin:32px auto;background:#fff;border-radius:12px;overflow:hidden;box-shadow:0 2px 8px rgba(0,0,0,.08);}
    .header{background:#6366f1;padding:24px 32px;color:#fff;}
    .header h1{margin:0;font-size:20px;font-weight:700;letter-spacing:-.3px;}
    .body{padding:32px;}
    p{margin:0 0 16px;line-height:1.6;}
    blockquote{margin:16px 0;}
    .btn{display:inline-block;padding:12px 24px;background:#6366f1;color:#fff!important;border-radius:8px;text-decoration:none;font-weight:600;font-size:14px;}
    .footer{padding:16px 32px;background:#f9fafb;font-size:12px;color:#9ca3af;text-align:center;}
    a{color:#6366f1;}
  </style>
</head>
<body>
  <div class="wrap">
    <div class="header"><h1>{$appName}</h1><p style="margin:4px 0 0;font-size:13px;opacity:.85;">{$title}</p></div>
    <div class="body">{$content}</div>
    <div class="footer">
      &copy; {$appName} · <a href="{$appUrl}">{$appUrl}</a><br/>
      Você está recebendo este e-mail porque tem uma conta no {$appName}.
    </div>
  </div>
</body>
</html>
HTML;
    }
}
