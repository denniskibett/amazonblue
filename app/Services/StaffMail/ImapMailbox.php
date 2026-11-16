<?php

namespace App\Services\StaffMail;

use Illuminate\Support\Carbon;
use Webklex\PHPIMAP\Client;
use Webklex\PHPIMAP\Connection\Protocols\ImapProtocol;
use Webklex\PHPIMAP\IMAP;
use Webklex\PHPIMAP\Message;

/**
 * A mailbox opened over IMAP for one request. Lists fetch only headers, flags and the first 2 KB of the body
 * (for the preview line), never whole messages; reading fetches one message into memory. Nothing is cached.
 */
class ImapMailbox implements Mailbox
{
    private ImapProtocol $conn;
    private ?array $paths = null;
    private string $delimiter = '.';
    private ?string $selected = null;

    private const SPECIAL_USE = ['sent' => 'sent', 'drafts' => 'drafts', 'spam' => 'junk', 'trash' => 'trash', 'archive' => 'archive'];
    private const NAMES = [
        'sent' => ['sent', 'sent items', 'sent messages', 'sent mail'],
        'drafts' => ['drafts', 'draft'],
        'spam' => ['junk', 'spam', 'junk e-mail', 'junk email', 'bulk mail'],
        'trash' => ['trash', 'deleted items', 'deleted messages', 'bin'],
        'archive' => ['archive', 'archives'],
    ];

    public function __construct(private Client $client)
    {
        $this->conn = $client->getConnection();
    }

    public function folders(): array
    {
        $out = [];
        foreach (self::FOLDERS as $key => $label) {
            $path = $this->paths()[$key] ?? null;
            $status = ['messages' => 0, 'unseen' => 0];
            if ($path !== null) {
                try {
                    $status = $this->conn->folderStatus($path, ['MESSAGES', 'UNSEEN'])->validatedData() + $status;
                } catch (\Throwable) {
                }
            }
            $out[$key] = ['label' => $label, 'unread' => (int) $status['unseen'], 'total' => (int) $status['messages'], 'exists' => $path !== null];
        }

        return $out;
    }

    public function uids(string $folder, ?string $search = null): array
    {
        if (! $this->select($folder)) {
            return [];
        }
        $search = trim((string) $search);
        $params = $search === '' ? ['ALL'] : ['CHARSET', 'UTF-8', 'TEXT', $this->quote($search)];
        $ids = $this->conn->search($params)->validatedData();
        $ids = array_map('intval', array_filter((array) $ids, fn ($v) => is_numeric($v)));
        rsort($ids, SORT_NUMERIC); // UIDs grow as mail arrives: biggest is newest

        return array_values($ids);
    }

    public function summaries(string $folder, array $uids): array
    {
        $uids = array_values(array_map('intval', $uids));
        if (! $uids || ! $this->select($folder)) {
            return [];
        }
        $rows = $this->conn->fetch(['UID', 'FLAGS', 'INTERNALDATE', 'BODY.PEEK[HEADER]'], $uids)->validatedData();
        $texts = $this->bodyStarts($uids);

        $out = [];
        foreach ($uids as $uid) {
            $r = $rows[$uid] ?? null;
            if (! is_array($r)) {
                continue;
            }
            $headers = Mime::headers((string) ($r['BODY[HEADER]'] ?? ''));
            $from = Mime::addresses($headers['from'] ?? '')[0] ?? ['name' => '', 'email' => ''];
            $flags = array_map(fn ($f) => strtolower(ltrim((string) $f, '\\')), (array) ($r['FLAGS'] ?? []));
            $out[] = [
                'uid' => $uid,
                'from_name' => $from['name'] ?: $from['email'],
                'from_email' => $from['email'],
                'to' => implode(', ', array_map(fn ($a) => $a['name'] ?: $a['email'], Mime::addresses($headers['to'] ?? ''))),
                'subject' => $headers['subject'] ?? '',
                'snippet' => Mime::snippet($headers, $texts[$uid] ?? ''),
                'date' => Mime::date($headers['date'] ?? null) ?? Mime::date($r['INTERNALDATE'] ?? null),
                'seen' => in_array('seen', $flags, true),
                'flagged' => in_array('flagged', $flags, true),
                'answered' => in_array('answered', $flags, true),
            ];
        }

        return $out;
    }

    public function message(string $folder, int $uid): ?array
    {
        $m = $this->load($folder, $uid);
        if (! $m) {
            return null;
        }
        [$msg, $flags] = $m;
        $attachments = [];
        $inline = [];
        foreach ($msg->getAttachments()->values() as $i => $a) {
            $cid = trim((string) ($a->id ?? ''), '<> ');
            $item = ['index' => $i, 'name' => (string) ($a->name ?: 'attachment-' . ($i + 1)), 'mime' => $this->mimeOf($a),
                'size' => strlen((string) $a->content), 'inline_id' => $cid ?: null];
            $attachments[] = $item;
            if ($cid && str_starts_with($item['mime'], 'image/') && $item['size'] <= 2_000_000) {
                $inline[$cid] = 'data:' . $item['mime'] . ';base64,' . base64_encode((string) $a->content);
            }
        }
        $html = $msg->hasHTMLBody() ? $msg->getHTMLBody() : null;
        if ($html !== null && $inline) {
            $html = preg_replace_callback('/cid:([^"\'\s)>]+)/i', fn ($x) => $inline[trim(rawurldecode($x[1]), '<>')] ?? $x[0], $html);
        }
        $raw = Mime::headers($this->rawHeaderBlock($msg));

        return [
            'uid' => $uid,
            'subject' => $raw['subject'] ?? '',
            'from' => Mime::addresses($raw['from'] ?? '')[0] ?? ['name' => '', 'email' => ''],
            'to' => Mime::addresses($raw['to'] ?? ''),
            'cc' => Mime::addresses($raw['cc'] ?? ''),
            'reply_to' => Mime::addresses($raw['reply-to'] ?? ''),
            'date' => Mime::date($raw['date'] ?? null),
            'message_id' => Mime::ids($raw['message-id'] ?? '')[0] ?? null,
            'references' => Mime::ids(($raw['references'] ?? '') . ' ' . ($raw['in-reply-to'] ?? '')),
            'seen' => in_array('seen', $flags, true),
            'html' => $html,
            'text' => $msg->hasTextBody() ? $msg->getTextBody() : null,
            'attachments' => array_values(array_filter($attachments, fn ($a) => ! ($a['inline_id'] && $html && isset($inline[$a['inline_id']]) && str_contains($html, $inline[$a['inline_id']])))),
        ];
    }

    public function attachment(string $folder, int $uid, int $index): ?array
    {
        $m = $this->load($folder, $uid);
        $a = $m ? ($m[0]->getAttachments()->values()[$index] ?? null) : null;

        return $a ? ['name' => (string) ($a->name ?: 'attachment-' . ($index + 1)), 'mime' => $this->mimeOf($a), 'content' => (string) $a->content] : null;
    }

    public function setSeen(string $folder, array $uids, bool $seen): void
    {
        $this->store($folder, $uids, '\Seen', $seen ? '+' : '-');
    }

    public function setAnswered(string $folder, int $uid): void
    {
        $this->store($folder, [$uid], '\Answered', '+');
    }

    public function move(string $folder, array $uids, string $target): void
    {
        $uids = array_map('intval', $uids);
        if (! $uids || $folder === $target || ! $this->select($folder)) {
            return;
        }
        $to = $this->ensure($target);
        $this->conn->moveManyMessages($uids, $to)->validate();
    }

    public function destroy(string $folder, array $uids): void
    {
        $uids = array_map('intval', $uids);
        if (! $uids || ! $this->select($folder)) {
            return;
        }
        foreach ($uids as $uid) {
            $this->conn->store(['\Deleted'], $uid, $uid, '+', true, IMAP::ST_UID)->validate();
        }
        $this->conn->expunge();
    }

    public function appendSent(string $raw): void
    {
        $this->conn->appendMessage($this->ensure('sent'), str_replace(["\r\n", "\n"], ["\n", "\r\n"], $raw), ['\Seen'])->validate();
    }

    public function close(): void
    {
        try {
            $this->client->disconnect();
        } catch (\Throwable) {
        }
    }

    public function __destruct()
    {
        $this->close();
    }

    // ---------------------------------------------------------------------------------------------------------

    /** Our folder keys → server paths, by special-use flag first (RFC 6154), then by common names. */
    private function paths(): array
    {
        if ($this->paths !== null) {
            return $this->paths;
        }
        $list = (array) $this->conn->folders('', '*')->validatedData();
        $map = ['inbox' => 'INBOX'];
        $flagsOf = fn ($info) => array_map(fn ($f) => strtolower(ltrim((string) $f, '\\')), (array) ($info['flags'] ?? []));
        foreach ($list as $path => $info) {
            $this->delimiter = (string) ($info['delimiter'] ?? $this->delimiter) ?: '.';
            if (in_array('noselect', $flagsOf($info), true)) {
                continue;
            }
            foreach (self::SPECIAL_USE as $key => $flag) {
                if (! isset($map[$key]) && in_array($flag, $flagsOf($info), true)) {
                    $map[$key] = (string) $path;
                }
            }
        }
        foreach (self::NAMES as $key => $names) {
            if (isset($map[$key])) {
                continue;
            }
            foreach ($list as $path => $info) {
                $leaf = strtolower(trim((string) last(explode((string) ($info['delimiter'] ?: '.'), (string) $path))));
                if (in_array($leaf, $names, true) && ! in_array('noselect', $flagsOf($info), true)) {
                    $map[$key] = (string) $path;
                    break;
                }
            }
        }

        return $this->paths = $map;
    }

    /** The server path for a key, creating the folder (e.g. Archive) when the server has none. */
    private function ensure(string $key): string
    {
        if ($path = $this->paths()[$key] ?? null) {
            return $path;
        }
        $prefix = '';
        foreach (array_keys((array) $this->conn->folders('', '*')->validatedData()) as $p) {
            if (str_starts_with(strtoupper((string) $p), 'INBOX' . $this->delimiter)) {
                $prefix = 'INBOX' . $this->delimiter; // Dovecot set up with an INBOX. namespace
                break;
            }
        }
        $path = $prefix . (self::FOLDERS[$key] ?? ucfirst($key));
        $this->conn->createFolder($path);
        try { $this->conn->subscribeFolder($path); } catch (\Throwable) {}
        $this->paths[$key] = $path;

        return $path;
    }

    private function select(string $folder): bool
    {
        $path = $this->paths()[$folder] ?? null;
        if ($path === null) {
            return false;
        }
        if ($this->selected !== $path) {
            $this->conn->selectFolder($path)->validate();
            $this->selected = $path;
        }

        return true;
    }

    /** @return array{0: Message, 1: list<string>}|null */
    private function load(string $folder, int $uid): ?array
    {
        if (! $this->select($folder)) {
            return null;
        }
        $rows = $this->conn->fetch(['UID', 'FLAGS', 'BODY.PEEK[]'], [$uid])->validatedData();
        $r = $rows[$uid] ?? null;
        if (! is_array($r) || ! isset($r['BODY[]'])) {
            return null;
        }
        $msg = Message::fromString((string) $r['BODY[]'], $this->client->getConfig());
        $flags = array_map(fn ($f) => strtolower(ltrim((string) $f, '\\')), (array) ($r['FLAGS'] ?? []));

        return [$msg, $flags];
    }

    /** The type the sender declared (sniffing the bytes guesses wrong on small or odd files). */
    private function mimeOf($a): string
    {
        $t = strtolower(trim(explode(';', (string) ($a->content_type ?? ''))[0]));

        return preg_match('#^[a-z0-9.+-]+/[a-z0-9.+-]+$#', $t) ? $t : ((string) $a->getMimeType() ?: 'application/octet-stream');
    }

    private function rawHeaderBlock(Message $msg): string
    {
        return (string) ($msg->getHeader()?->raw ?? '');
    }

    private function store(string $folder, array $uids, string $flag, string $mode): void
    {
        $uids = array_map('intval', $uids);
        if (! $uids || ! $this->select($folder)) {
            return;
        }
        foreach ($uids as $uid) {
            $this->conn->store([$flag], $uid, $uid, $mode, true, IMAP::ST_UID)->validate();
        }
    }

    /**
     * The first 2 KB of each message body, for the preview line. Read by hand: webklex's response parser loses its
     * place on partial fetches (BODY[TEXT]<0> {n}), so this sends the command and reads the literals itself.
     *
     * @return array<int, string> uid => bytes
     */
    private function bodyStarts(array $uids): array
    {
        $out = [];
        try {
            $response = $this->conn->sendRequest('UID FETCH', [implode(',', $uids), '(UID BODY.PEEK[TEXT]<0.2048>)'], $tag);
            while (true) {
                $line = $this->conn->nextLine($response);
                if (str_starts_with($line, $tag . ' ')) {
                    break;
                }
                if (! preg_match('/^\* \d+ FETCH \((.*)\{(\d+)\}\r?\n$/s', $line, $m)) {
                    continue;
                }
                $want = (int) $m[2];
                $data = '';
                while (strlen($data) < $want) {
                    $data .= $this->conn->nextLine($response);
                }
                $rest = substr($data, $want);
                $data = substr($data, 0, $want);
                if (! preg_match('/\)\s*$/', $rest)) {
                    $rest .= $this->conn->nextLine($response); // the closing ")" (and maybe UID) on its own line
                }
                if (preg_match('/\bUID (\d+)/', $m[1] . ' ' . $rest, $u)) {
                    $out[(int) $u[1]] = $data;
                }
            }
        } catch (\Throwable) {
            // previews are a nicety: a list without them still works
        }

        return $out;
    }

    private function quote(string $s): string
    {
        return '"' . str_replace(['\\', '"', "\r", "\n"], ['\\\\', '\\"', ' ', ' '], $s) . '"';
    }
}
