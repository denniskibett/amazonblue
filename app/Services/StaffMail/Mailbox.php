<?php

namespace App\Services\StaffMail;

/**
 * One staff member's mailbox on the mail server, opened for one request. Folder arguments are our keys
 * (inbox, sent, drafts, spam, trash, archive), never server paths. Nothing here writes to our DB or disk.
 */
interface Mailbox
{
    public const FOLDERS = ['inbox' => 'Inbox', 'sent' => 'Sent', 'drafts' => 'Drafts', 'spam' => 'Spam', 'trash' => 'Trash', 'archive' => 'Archive'];

    /** @return array<string, array{label: string, unread: int, total: int, exists: bool}> keyed like FOLDERS */
    public function folders(): array;

    /** UIDs in the folder, newest first; $search matches sender, recipients, subject and body. */
    public function uids(string $folder, ?string $search = null): array;

    /**
     * List rows for these UIDs, in the order given.
     *
     * @return list<array{uid: int, from_name: string, from_email: string, to: string, subject: string, snippet: string,
     *                    date: ?\Carbon\CarbonInterface, seen: bool, flagged: bool, answered: bool}>
     */
    public function summaries(string $folder, array $uids): array;

    /**
     * One whole message, or null when it is not there. Does not change its flags.
     *
     * @return array{uid: int, subject: string, from: array, to: list<array>, cc: list<array>, reply_to: list<array>,
     *               date: ?\Carbon\CarbonInterface, message_id: ?string, references: list<string>, seen: bool,
     *               html: ?string, text: ?string, attachments: list<array{index: int, name: string, mime: string, size: int, inline_id: ?string}>}|null
     */
    public function message(string $folder, int $uid): ?array;

    /** @return array{name: string, mime: string, content: string}|null */
    public function attachment(string $folder, int $uid, int $index): ?array;

    public function setSeen(string $folder, array $uids, bool $seen): void;

    public function setAnswered(string $folder, int $uid): void;

    /** Move to another of our folders (created on the server when it is missing, e.g. Archive). */
    public function move(string $folder, array $uids, string $target): void;

    /** Delete for good (only used from Trash). */
    public function destroy(string $folder, array $uids): void;

    /** Keep a copy of a sent message (raw RFC 822) in Sent. */
    public function appendSent(string $raw): void;

    public function close(): void;
}
