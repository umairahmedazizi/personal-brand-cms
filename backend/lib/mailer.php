<?php
// mail via php mail() by default, or smtp if enabled in config. returns bool, never throws
require_once __DIR__ . '/helpers.php';

function send_mail(string $to, string $subject, string $htmlBody, ?string $replyTo = null): bool
{
    $m = cfg()['mail'];
    $fromAddr = $m['from'];
    $fromName = $m['from_name'];

    try {
        if (!empty($m['smtp']['enabled'])) {
            return smtp_send($m['smtp'], $fromAddr, $fromName, $to, $subject, $htmlBody, $replyTo);
        }

        $headers = [];
        $headers[] = 'MIME-Version: 1.0';
        $headers[] = 'Content-Type: text/html; charset=UTF-8';
        $headers[] = 'From: ' . mb_encode_mimeheader($fromName) . ' <' . $fromAddr . '>';
        if ($replyTo) {
            $headers[] = 'Reply-To: ' . $replyTo;
        }
        return @mail($to, $subject, $htmlBody, implode("\r\n", $headers));
    } catch (Throwable $ex) {
        error_log('send_mail failed: ' . $ex->getMessage());
        return false;
    }
}

// minimal smtp over a socket, supports ssl (465) and starttls
function smtp_send(array $s, string $fromAddr, string $fromName, string $to, string $subject, string $body, ?string $replyTo): bool
{
    $host = $s['host'];
    $port = (int) $s['port'];
    $transport = ($s['secure'] === 'ssl') ? "ssl://$host" : $host;

    $fp = @stream_socket_client("$transport:$port", $errno, $errstr, 15);
    if (!$fp) {
        error_log("SMTP connect failed: $errstr ($errno)");
        return false;
    }
    $read = function () use ($fp) { return fgets($fp, 515); };
    $cmd = function ($line) use ($fp) { fwrite($fp, $line . "\r\n"); };

    $read();
    $cmd('EHLO ' . ($_SERVER['SERVER_NAME'] ?? 'localhost'));
    while (($line = $read()) && substr($line, 3, 1) === '-') {}

    if ($s['secure'] === 'tls') {
        $cmd('STARTTLS');
        $read();
        stream_socket_enable_crypto($fp, true, STREAM_CRYPTO_METHOD_TLS_CLIENT);
        $cmd('EHLO ' . ($_SERVER['SERVER_NAME'] ?? 'localhost'));
        while (($line = $read()) && substr($line, 3, 1) === '-') {}
    }

    $cmd('AUTH LOGIN');           $read();
    $cmd(base64_encode($s['user'])); $read();
    $cmd(base64_encode($s['pass'])); $resp = $read();
    if (strpos($resp, '235') !== 0) { error_log('SMTP auth failed: ' . $resp); fclose($fp); return false; }

    $cmd("MAIL FROM:<$fromAddr>"); $read();
    $cmd("RCPT TO:<$to>");         $read();
    $cmd('DATA');                  $read();

    $headers  = "From: =?UTF-8?B?" . base64_encode($fromName) . "?= <$fromAddr>\r\n";
    $headers .= "To: <$to>\r\n";
    if ($replyTo) $headers .= "Reply-To: <$replyTo>\r\n";
    $headers .= 'Subject: =?UTF-8?B?' . base64_encode($subject) . "?=\r\n";
    $headers .= "MIME-Version: 1.0\r\n";
    $headers .= "Content-Type: text/html; charset=UTF-8\r\n";

    $cmd($headers . "\r\n" . $body . "\r\n.");
    $resp = $read();
    $cmd('QUIT');
    fclose($fp);

    return strpos($resp, '250') === 0;
}
