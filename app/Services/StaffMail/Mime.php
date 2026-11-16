<?php

namespace App\Services\StaffMail;

use Illuminate\Support\Carbon;

/** Small, forgiving MIME helpers for list rows: headers, addresses, dates and the one-line preview. */
class Mime
{
    /** Raw header block → lower-case name => decoded UTF-8 value (first occurrence wins). */
    public static function headers(string $raw): array
    {
        $raw = preg_replace("/\r?\n[ \t]+/", ' ', str_replace("\r\n", "\n", $raw)); // unfold
        $out = [];
        foreach (explode("\n", (string) $raw) as $line) {
            if (! str_contains($line, ':')) {
                continue;
            }
            [$name, $value] = explode(':', $line, 2);
            $name = strtolower(trim($name));
            if ($name === '' || isset($out[$name])) {
                continue;
            }
            $out[$name] = self::decode(trim($value));
        }

        return $out;
    }

    public static function decode(string $value): string
    {
        if (str_contains($value, '=?')) {
            $d = @iconv_mime_decode($value, ICONV_MIME_DECODE_CONTINUE_ON_ERROR, 'UTF-8');
            $value = $d !== false ? $d : mb_decode_mimeheader($value);
        }

        return mb_scrub($value, 'UTF-8');
    }

    /** "A <a@x>, \"B, Jr\" <b@y>, c@z" → [['name' => 'A', 'email' => 'a@x'], ...] */
    public static function addresses(string $value): array
    {
        $parts = [];
        $buf = '';
        $quoted = false;
        $angle = 0;
        foreach (preg_split('//u', $value, -1, PREG_SPLIT_NO_EMPTY) as $ch) {
            if ($ch === '"') {
                $quoted = ! $quoted;
            } elseif (! $quoted && $ch === '<') {
                $angle++;
            } elseif (! $quoted && $ch === '>') {
                $angle = max(0, $angle - 1);
            } elseif (! $quoted && ! $angle && ($ch === ',' || $ch === ';')) {
                $parts[] = $buf;
                $buf = '';
                continue;
            }
            $buf .= $ch;
        }
        $parts[] = $buf;

        $out = [];
        foreach ($parts as $p) {
            $p = trim($p);
            if ($p === '') {
                continue;
            }
            if (preg_match('/^(.*)<([^<>]+)>\s*$/s', $p, $m)) {
                $name = trim(trim($m[1]), '"\' ');
                $email = trim($m[2]);
            } else {
                $name = '';
                $email = trim($p, '<> ');
            }
            if (str_contains($email, '@')) {
                $out[] = ['name' => str_replace('\\"', '"', $name), 'email' => strtolower($email)];
            }
        }

        return $out;
    }

    public static function date(?string $value): ?Carbon
    {
        $value = trim(preg_replace('/\s*\([^)]*\)\s*$/', '', (string) $value));
        if ($value === '') {
            return null;
        }
        try {
            return Carbon::parse($value)->setTimezone(config('app.timezone'));
        } catch (\Throwable) {
            return null;
        }
    }

    /** "<a@b> <c@d>" → ['<a@b>', '<c@d>'] */
    public static function ids(string $value): array
    {
        preg_match_all('/<[^<>\s]+>/', $value, $m);

        return array_values(array_unique($m[0]));
    }

    /** A one-line preview from the message's headers and the first bytes of its body. */
    public static function snippet(array $headers, string $body, int $length = 160): string
    {
        $text = self::firstText($headers['content-type'] ?? 'text/plain', $headers['content-transfer-encoding'] ?? '', $body, 0);
        $text = html_entity_decode($text, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $text = trim(preg_replace('/\s+/u', ' ', mb_scrub($text, 'UTF-8')));

        return mb_strlen($text) > $length ? rtrim(mb_substr($text, 0, $length)) . '…' : $text;
    }

    private static function firstText(string $type, string $encoding, string $body, int $depth): string
    {
        $body = str_replace("\r\n", "\n", $body);
        if ($depth < 3 && stripos($type, 'multipart/') === 0 && preg_match('/boundary\s*=\s*"?([^";]+)"?/i', $type, $b)) {
            $chunks = explode('--' . trim($b[1]), $body);
            array_shift($chunks); // preamble
            foreach ($chunks as $chunk) {
                if (str_starts_with($chunk, '--')) {
                    break; // closing boundary
                }
                $split = preg_split("/\n\n/", ltrim($chunk, "\n"), 2);
                $h = self::headers($split[0] ?? '');
                $ptype = $h['content-type'] ?? 'text/plain';
                if (stripos($ptype, 'multipart/') === 0 || stripos($ptype, 'text/') === 0) {
                    if (stripos($h['content-disposition'] ?? '', 'attachment') === 0) {
                        continue;
                    }
                    $t = self::firstText($ptype, $h['content-transfer-encoding'] ?? '', $split[1] ?? '', $depth + 1);
                    if (trim($t) !== '') {
                        return $t;
                    }
                }
            }

            return '';
        }
        if (stripos($type, 'text/') !== 0) {
            return '';
        }
        $enc = strtolower(trim($encoding));
        if ($enc === 'quoted-printable') {
            $body = quoted_printable_decode(preg_replace('/=[0-9A-F]?$/i', '', $body));
        } elseif ($enc === 'base64') {
            $clean = preg_replace('/[^A-Za-z0-9+\/=]/', '', $body);
            $body = (string) base64_decode(substr($clean, 0, intdiv(strlen($clean), 4) * 4));
        }
        if (preg_match('/charset\s*=\s*"?([\w.:-]+)"?/i', $type, $c) && strtolower($c[1]) !== 'utf-8') {
            try {
                $body = mb_convert_encoding($body, 'UTF-8', $c[1]);
            } catch (\Throwable) {
            }
        }
        if (stripos($type, 'text/html') === 0) {
            $body = preg_replace('#<(style|script|head|title)\b[^>]*>.*?(</\1>|$)#is', ' ', $body);
            $body = strip_tags(preg_replace('/<br\s*\/?>|<\/p>|<\/div>/i', ' ', $body));
        }

        return $body;
    }
}
