<?php

/*
 * Staff mail (owner, 16 Nov 2026): staff whose account email is on this domain read and send their own mail
 * from the app. It is a thin front end to the mail server on this VPS (Dovecot IMAP + Postfix SMTP): nothing
 * about messages is written to our database or disk. The mailbox password is typed once per app login and kept
 * only as ciphertext in the session, with the key in a browser cookie (App\Services\StaffMail\MailCredentials).
 *
 * Switched on and set up as an integration on /staff/integrations (Support\Integrations, slug staff-mail): the
 * values saved there override these .env defaults, and the mail pages exist only while it is switched on.
 *
 * Mail never leaves the machine (app and mail server share the VPS), so the defaults talk to 127.0.0.1 without
 * certificate checks: the certificate is for mail.<domain>, not for 127.0.0.1.
 */
return [
    'enabled' => (bool) env('STAFF_MAIL_ENABLED', false),
    'domain' => strtolower((string) env('STAFF_MAIL_DOMAIN', 'sharet.africa')),

    'imap' => [
        'host' => env('STAFF_MAIL_IMAP_HOST', '127.0.0.1'),
        'port' => (int) env('STAFF_MAIL_IMAP_PORT', 993),
        'encryption' => env('STAFF_MAIL_IMAP_ENCRYPTION', 'ssl'), // ssl (implicit TLS, 993) | starttls (143) | none
        'validate_cert' => (bool) env('STAFF_MAIL_IMAP_VALIDATE_CERT', false),
        'timeout' => (int) env('STAFF_MAIL_IMAP_TIMEOUT', 15),
    ],

    'smtp' => [
        'host' => env('STAFF_MAIL_SMTP_HOST', '127.0.0.1'),
        'port' => (int) env('STAFF_MAIL_SMTP_PORT', 587),
        // true = implicit TLS (port 465); otherwise STARTTLS is used when the server offers it
        'implicit_tls' => (bool) env('STAFF_MAIL_SMTP_IMPLICIT_TLS', false),
        'verify_peer' => (bool) env('STAFF_MAIL_SMTP_VERIFY_PEER', false),
    ],

    // the mailbox locks again after this many minutes without opening the mail page
    'idle_minutes' => (int) env('STAFF_MAIL_IDLE_MINUTES', 60),
    'per_page' => 25,
    'max_attachment_kb' => 10240,
    'max_total_attachment_kb' => 20480,
];
