<?php
/**
 * CODEGA Finans - SMTP mail helper
 */
declare(strict_types=1);

require_once __DIR__ . '/functions.php';

function cf_mail_header_encode(string $value): string
{
    return '=?UTF-8?B?' . base64_encode($value) . '?=';
}

function cf_mail_read_line($socket): string
{
    $line = fgets($socket, 515);
    if ($line === false) {
        throw new RuntimeException('SMTP sunucusundan yanit alinamadi.');
    }
    return $line;
}

function cf_mail_expect($socket, array $codes): string
{
    $response = '';
    do {
        $line = cf_mail_read_line($socket);
        $response .= $line;
        $more = isset($line[3]) && $line[3] === '-';
    } while ($more);

    $code = (int)substr($response, 0, 3);
    if (!in_array($code, $codes, true)) {
        throw new RuntimeException('SMTP hata: ' . trim($response));
    }
    return $response;
}

function cf_mail_write($socket, string $command): void
{
    fwrite($socket, $command . "\r\n");
}

function cf_mail_setting(string $key, string $constant, string $default = ''): string
{
    try {
        $row = db_one('SELECT value FROM ' . t('settings') . ' WHERE key_name=:k LIMIT 1', [':k' => $key]);
        if ($row && trim((string)$row['value']) !== '') {
            return trim((string)$row['value']);
        }
    } catch (Throwable $e) {
        // Kurulum oncesi veya settings tablosu yoksa sabitlere dus.
    }

    return defined($constant) ? trim((string)constant($constant)) : $default;
}

function cf_mail_config(): array
{
    $user = cf_mail_setting('mail_user', 'CF_MAIL_USER');
    return [
        'host' => cf_mail_setting('mail_host', 'CF_MAIL_HOST'),
        'port' => (int)(cf_mail_setting('mail_port', 'CF_MAIL_PORT', '587') ?: 587),
        'user' => $user,
        'pass' => cf_mail_setting('mail_pass', 'CF_MAIL_PASS'),
        'secure' => strtolower(cf_mail_setting('mail_secure', 'CF_MAIL_SECURE', 'tls')),
        'from' => cf_mail_setting('mail_from', 'CF_MAIL_FROM', $user),
        'from_name' => cf_mail_setting('mail_from_name', 'CF_MAIL_FROM_NAME', CF_APP_NAME),
        'timeout' => (int)(cf_mail_setting('mail_timeout', 'CF_MAIL_TIMEOUT', '15') ?: 15),
    ];
}

function cf_send_mail(string $to, string $subject, string $html, ?string $text = null, array $attachments = []): bool
{
    $to = trim($to);
    if (!filter_var($to, FILTER_VALIDATE_EMAIL)) {
        throw new InvalidArgumentException('Gecerli bir alici e-posta adresi yok.');
    }

    $config = cf_mail_config();
    $host = $config['host'];
    $port = $config['port'];
    $user = $config['user'];
    $pass = $config['pass'];
    $secure = $config['secure'];
    $from = $config['from'];
    $fromName = $config['from_name'];
    $timeout = max(5, min(60, (int)$config['timeout']));

    if ($host === '' || $user === '' || $pass === '' || $from === '' || str_contains($pass, 'GMAIL-UYGULAMA')) {
        throw new RuntimeException('SMTP ayarlari eksik. Ayarlar > Mail / SMTP Ayarlari bolumunde sunucu, kullanici ve uygulama sifresini girin.');
    }

    $transport = $secure === 'ssl' ? 'ssl://' : '';
    $target = $transport . $host . ':' . $port;
    $socket = @stream_socket_client($target, $errno, $errstr, $timeout, STREAM_CLIENT_CONNECT);
    if (!$socket) {
        $reason = $errstr ?: 'baglanti zaman asimi';
        $hint = ' Denenen adres: ' . $host . ':' . $port . ' / ' . $secure . '. Gmail icin 587/tls veya 465/ssl deneyin. Hata surerse hosting firmanizdan dis SMTP cikis izni isteyin.';
        throw new RuntimeException('SMTP baglantisi kurulamadi: ' . $reason . '.' . $hint);
    }

    stream_set_timeout($socket, $timeout);
    try {
        cf_mail_expect($socket, [220]);
        cf_mail_write($socket, 'EHLO ' . (parse_url(CF_APP_URL, PHP_URL_HOST) ?: 'localhost'));
        cf_mail_expect($socket, [250]);

        if ($secure === 'tls') {
            cf_mail_write($socket, 'STARTTLS');
            cf_mail_expect($socket, [220]);
            if (!stream_socket_enable_crypto($socket, true, STREAM_CRYPTO_METHOD_TLS_CLIENT)) {
                throw new RuntimeException('SMTP TLS baslatilamadi.');
            }
            cf_mail_write($socket, 'EHLO ' . (parse_url(CF_APP_URL, PHP_URL_HOST) ?: 'localhost'));
            cf_mail_expect($socket, [250]);
        }

        cf_mail_write($socket, 'AUTH LOGIN');
        cf_mail_expect($socket, [334]);
        cf_mail_write($socket, base64_encode($user));
        cf_mail_expect($socket, [334]);
        cf_mail_write($socket, base64_encode($pass));
        cf_mail_expect($socket, [235]);

        cf_mail_write($socket, 'MAIL FROM:<' . $from . '>');
        cf_mail_expect($socket, [250]);
        cf_mail_write($socket, 'RCPT TO:<' . $to . '>');
        cf_mail_expect($socket, [250, 251]);
        cf_mail_write($socket, 'DATA');
        cf_mail_expect($socket, [354]);

        $altBoundary = 'cf_alt_' . bin2hex(random_bytes(12));
        $mixedBoundary = 'cf_mix_' . bin2hex(random_bytes(12));
        $text = $text ?: trim(strip_tags(str_replace(['<br>', '<br/>', '<br />'], "\n", $html)));
        $headers = [
            'Date: ' . date(DATE_RFC2822),
            'From: ' . cf_mail_header_encode($fromName) . ' <' . $from . '>',
            'To: <' . $to . '>',
            'Subject: ' . cf_mail_header_encode($subject),
            'MIME-Version: 1.0',
            'Content-Type: ' . ($attachments ? 'multipart/mixed; boundary="' . $mixedBoundary . '"' : 'multipart/alternative; boundary="' . $altBoundary . '"'),
        ];
        $message = implode("\r\n", $headers) . "\r\n\r\n";
        if ($attachments) {
            $message .= '--' . $mixedBoundary . "\r\n";
            $message .= 'Content-Type: multipart/alternative; boundary="' . $altBoundary . '"' . "\r\n\r\n";
        }
        $message .= '--' . $altBoundary . "\r\nContent-Type: text/plain; charset=UTF-8\r\nContent-Transfer-Encoding: base64\r\n\r\n" . chunk_split(base64_encode($text));
        $message .= "\r\n--" . $altBoundary . "\r\nContent-Type: text/html; charset=UTF-8\r\nContent-Transfer-Encoding: base64\r\n\r\n" . chunk_split(base64_encode($html));
        $message .= "\r\n--" . $altBoundary . "--\r\n";
        foreach ($attachments as $attachment) {
            $filename = (string)($attachment['filename'] ?? 'ek.pdf');
            $content = (string)($attachment['content'] ?? '');
            $contentType = (string)($attachment['content_type'] ?? 'application/octet-stream');
            if ($content === '') {
                continue;
            }
            $message .= "\r\n--" . $mixedBoundary . "\r\n";
            $message .= 'Content-Type: ' . $contentType . '; name="' . addcslashes($filename, "\"\\") . '"' . "\r\n";
            $message .= 'Content-Transfer-Encoding: base64' . "\r\n";
            $message .= 'Content-Disposition: attachment; filename="' . addcslashes($filename, "\"\\") . '"' . "\r\n\r\n";
            $message .= chunk_split(base64_encode($content));
        }
        if ($attachments) {
            $message .= "\r\n--" . $mixedBoundary . "--\r\n";
        }
        $message = preg_replace('/^\./m', '..', $message);

        fwrite($socket, $message . "\r\n.\r\n");
        cf_mail_expect($socket, [250]);
        cf_mail_write($socket, 'QUIT');
        return true;
    } finally {
        fclose($socket);
    }
}
