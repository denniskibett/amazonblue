<?php

namespace App\Services\StaffMail;

use Symfony\Component\Mime\Email;

/**
 * The mail server: opens a staff mailbox over IMAP and sends over SMTP, both as the staff member themself.
 * Bound in the container so tests can swap in a fake.
 */
interface MailServer
{
    /** @throws MailAuthFailed when the address/password pair is refused; MailUnavailable when the server can't be reached */
    public function open(string $email, string $password): Mailbox;

    /**
     * Send as the mailbox owner. Returns the raw message as sent, for the Sent copy.
     *
     * @throws MailAuthFailed|MailUnavailable
     */
    public function send(string $email, string $password, Email $message): string;

    /**
     * Test connection (integration card): can we reach IMAP and SMTP with the settings in force, TLS included?
     * Logs in to nothing: no mailbox password is held at this level.
     *
     * @return array{ok: bool, message: string}
     */
    public function probe(): array;
}
