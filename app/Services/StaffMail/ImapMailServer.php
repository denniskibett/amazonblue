<?php

namespace App\Services\StaffMail;

use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\Mailer\Transport\Smtp\EsmtpTransport;
use Symfony\Component\Mime\Email;
use Webklex\PHPIMAP\ClientManager;
use Webklex\PHPIMAP\Exceptions\AuthFailedException;
use Webklex\PHPIMAP\IMAP;

/** The real mail server: Dovecot over IMAP (webklex/php-imap, no ext-imap needed) and Postfix over SMTP. */
class ImapMailServer implements MailServer
{
    public function open(string $email, string $password): Mailbox
    {
        $c = config('staffmail.imap');
        $manager = new ClientManager([
            'options' => [
                'fetch' => IMAP::FT_PEEK,
                'fetch_order' => 'desc',
                'soft_fail' => false,
                'debug' => false,
            ],
        ]);
        $client = $manager->make([
            'host' => $c['host'],
            'port' => (int) $c['port'],
            'encryption' => self::encryption($c['encryption']),
            'validate_cert' => (bool) $c['validate_cert'],
            'username' => $email,
            'password' => $password,
            'protocol' => 'imap',
            'authentication' => null,
            'timeout' => (int) ($c['timeout'] ?? 15),
        ]);

        try {
            $client->connect();
        } catch (AuthFailedException $e) {
            throw new MailAuthFailed('The mail server did not accept that password.');
        } catch (\Throwable $e) {
            // a refused LOGIN surfaces as a connection failure in some versions; tell the two apart by the server's words
            if (preg_match('/AUTHENTICATIONFAILED|authentication failed|invalid credentials|login failed/i', $this->chain($e))) {
                throw new MailAuthFailed('The mail server did not accept that password.');
            }
            // never chain $e: webklex response errors can quote the LOGIN command, password included
            throw new MailUnavailable('The mail server could not be reached (' . class_basename($e) . ').');
        }

        return new ImapMailbox($client);
    }

    public function send(string $email, string $password, Email $message): string
    {
        $c = config('staffmail.smtp');
        $transport = new EsmtpTransport((string) $c['host'], (int) $c['port'], $c['implicit_tls'] ? true : null);
        $transport->setUsername($email);
        $transport->setPassword($password);
        if (! $c['verify_peer']) {
            $transport->getStream()->setStreamOptions(['ssl' => ['verify_peer' => false, 'verify_peer_name' => false, 'allow_self_signed' => true]]);
        }

        try {
            $sent = $transport->send($message);
        } catch (TransportExceptionInterface $e) {
            if (preg_match('/\b535\b|authentication failed/i', $e->getMessage())) {
                throw new MailAuthFailed('The mail server did not accept that password.');
            }
            throw new MailUnavailable('The mail server could not send this message (' . class_basename($e) . ').');
        } finally {
            try { $transport->stop(); } catch (\Throwable) {}
        }

        return $sent?->toString() ?? $message->toString();
    }

    public function probe(): array
    {
        $parts = [];
        $ok = true;
        $c = config('staffmail.imap');
        try {
            $manager = new ClientManager(['options' => ['debug' => false]]);
            $config = $manager->make(['host' => $c['host'], 'port' => (int) $c['port'], 'protocol' => 'imap'])->getConfig();
            $imap = new \Webklex\PHPIMAP\Connection\Protocols\ImapProtocol($config, (bool) $c['validate_cert'], self::encryption($c['encryption']));
            $imap->setConnectionTimeout((int) ($c['timeout'] ?? 15));
            $imap->connect((string) $c['host'], (int) $c['port']);
            $caps = array_map('strtoupper', (array) $imap->getCapabilities()->validatedData());
            try { $imap->logout(); } catch (\Throwable) {}
            $parts[] = 'IMAP ' . $c['host'] . ':' . $c['port'] . ' answered' . (in_array('LOGINDISABLED', $caps, true) ? ' but refuses logins on this connection (use ssl or starttls)' : '');
            $ok = ! in_array('LOGINDISABLED', $caps, true);
        } catch (\Throwable $e) {
            $ok = false;
            $parts[] = 'IMAP ' . $c['host'] . ':' . $c['port'] . ' did not answer (' . class_basename($e) . ')';
        }
        $s = config('staffmail.smtp');
        $transport = new EsmtpTransport((string) $s['host'], (int) $s['port'], $s['implicit_tls'] ? true : null);
        if (! $s['verify_peer']) {
            $transport->getStream()->setStreamOptions(['ssl' => ['verify_peer' => false, 'verify_peer_name' => false, 'allow_self_signed' => true]]);
        }
        try {
            $transport->start();
            $parts[] = 'SMTP ' . $s['host'] . ':' . $s['port'] . ' answered';
        } catch (\Throwable $e) {
            $ok = false;
            $parts[] = 'SMTP ' . $s['host'] . ':' . $s['port'] . ' did not answer (' . class_basename($e) . ')';
        } finally {
            try { $transport->stop(); } catch (\Throwable) {}
        }

        return ['ok' => $ok, 'message' => implode('; ', $parts) . '.' . ($ok ? ' Each staff member signs in with their own mailbox password.' : '')];
    }

    /** Our setting → webklex's: ssl = implicit TLS, starttls = upgrade on 143, none = plain (loopback only). */
    public static function encryption(mixed $value): string|false
    {
        return match (strtolower(trim((string) $value))) {
            'ssl', 'tls' => 'ssl',
            'starttls' => 'starttls',
            default => false,
        };
    }

    private function chain(\Throwable $e): string
    {
        $s = '';
        for ($x = $e; $x; $x = $x->getPrevious()) {
            $s .= ' ' . $x->getMessage();
        }

        return $s;
    }
}
