<?php
namespace Core;

class Mailer
{
    public static function send(string $to, string $subject, string $html): bool
    {
        if (defined('SMTP_HOST') && trim((string) SMTP_HOST) !== '') {
            try {
                return self::sendSmtp($to, $subject, $html);
            } catch (\Throwable $e) {
                error_log('PodnikAppka SMTP error: ' . $e->getMessage());
                return false;
            }
        }

        $from = self::from();
        $headers = [
            'MIME-Version: 1.0',
            'Content-Type: text/html; charset=UTF-8',
            "From: {$from}",
            "Reply-To: {$from}",
        ];

        return mail($to, self::encodeHeader($subject), $html, implode("\r\n", $headers));
    }

    private static function sendSmtp(string $to, string $subject, string $html): bool
    {
        $host = trim((string) SMTP_HOST);
        $port = defined('SMTP_PORT') ? (int) SMTP_PORT : 587;
        $encryption = defined('SMTP_ENCRYPTION') ? strtolower(trim((string) SMTP_ENCRYPTION)) : 'tls';
        $user = defined('SMTP_USER') ? trim((string) SMTP_USER) : '';
        $pass = defined('SMTP_PASS') ? (string) SMTP_PASS : '';

        $transportHost = $encryption === 'ssl' ? 'ssl://' . $host : $host;
        $errno = 0;
        $errstr = '';
        $socket = @stream_socket_client(
            $transportHost . ':' . $port,
            $errno,
            $errstr,
            15,
            STREAM_CLIENT_CONNECT
        );

        if (!$socket) {
            throw new \RuntimeException("Nelze se připojit k SMTP serveru: {$errstr} ({$errno})");
        }

        stream_set_timeout($socket, 15);

        try {
            self::expect($socket, [220]);
            $hostname = $_SERVER['HTTP_HOST'] ?? 'localhost';
            self::command($socket, 'EHLO ' . preg_replace('/:\\d+$/', '', $hostname), [250]);

            if ($encryption === 'tls') {
                self::command($socket, 'STARTTLS', [220]);
                if (!@stream_socket_enable_crypto($socket, true, STREAM_CRYPTO_METHOD_TLS_CLIENT)) {
                    throw new \RuntimeException('SMTP server odmítl TLS šifrování.');
                }
                self::command($socket, 'EHLO ' . preg_replace('/:\\d+$/', '', $hostname), [250]);
            }

            if ($user !== '') {
                self::command($socket, 'AUTH LOGIN', [334]);
                self::command($socket, base64_encode($user), [334]);
                self::command($socket, base64_encode($pass), [235]);
            }

            $from = self::fromAddress();
            self::command($socket, 'MAIL FROM:<' . $from . '>', [250]);
            self::command($socket, 'RCPT TO:<' . $to . '>', [250, 251]);
            self::command($socket, 'DATA', [354]);

            $headers = [
                'Date: ' . date(DATE_RFC2822),
                'Message-ID: <' . bin2hex(random_bytes(12)) . '@' . preg_replace('/:\\d+$/', '', $hostname) . '>',
                'From: ' . self::from(),
                'To: <' . $to . '>',
                'Subject: ' . self::encodeHeader($subject),
                'MIME-Version: 1.0',
                'Content-Type: text/html; charset=UTF-8',
                'Content-Transfer-Encoding: 8bit',
            ];

            $body = preg_replace('/(?m)^\./', '..', str_replace(["\r\n", "\r"], "\n", $html));
            $message = implode("\r\n", $headers) . "\r\n\r\n" . str_replace("\n", "\r\n", $body) . "\r\n.";
            fwrite($socket, $message . "\r\n");
            self::expect($socket, [250]);
            self::command($socket, 'QUIT', [221]);
            return true;
        } finally {
            if (is_resource($socket)) fclose($socket);
        }
    }

    private static function command($socket, string $command, array $expected): string
    {
        fwrite($socket, $command . "\r\n");
        return self::expect($socket, $expected);
    }

    private static function expect($socket, array $expected): string
    {
        $response = '';
        while (($line = fgets($socket, 515)) !== false) {
            $response .= $line;
            if (strlen($line) >= 4 && $line[3] === ' ') break;
        }
        if ($response === '') throw new \RuntimeException('SMTP server neodpověděl.');
        $code = (int) substr($response, 0, 3);
        if (!in_array($code, $expected, true)) {
            throw new \RuntimeException('SMTP chyba ' . $code . ': ' . trim($response));
        }
        return $response;
    }

    private static function fromAddress(): string
    {
        if (defined('SMTP_USER') && filter_var((string) SMTP_USER, FILTER_VALIDATE_EMAIL)) {
            return (string) SMTP_USER;
        }
        $host = preg_replace('/:\\d+$/', '', $_SERVER['HTTP_HOST'] ?? 'localhost');
        return 'noreply@' . $host;
    }

    private static function from(): string
    {
        return 'PodnikAppka <' . self::fromAddress() . '>';
    }

    private static function encodeHeader(string $value): string
    {
        return '=?UTF-8?B?' . base64_encode($value) . '?=';
    }

    public static function appUrl(string $path): string
    {
        $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
        $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
        return $scheme . '://' . $host . '/' . ltrim($path, '/');
    }
}
