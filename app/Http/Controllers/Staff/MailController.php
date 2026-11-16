<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use App\Services\StaffMail\MailAuthFailed;
use App\Services\StaffMail\Mailbox;
use App\Services\StaffMail\MailCredentials;
use App\Services\StaffMail\MailServer;
use App\Services\StaffMail\MailUnavailable;
use App\Services\StaffMail\Mime;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Symfony\Component\HttpFoundation\HeaderUtils;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\Email;

/**
 * Staff mail (owner, 16 Nov 2026): the staff member's own mailbox on the VPS mail server, read over IMAP and sent
 * over SMTP as them. A thin front end: no message, header or draft is written to our database or disk. Errors on
 * the compose form re-render the page instead of flashing the draft into the session.
 */
class MailController extends Controller
{
    private const INTENDED = 'staff_mail_intended';

    public function __construct(private MailServer $server, private MailCredentials $creds)
    {
    }

    public function index(Request $request)
    {
        $folder = $this->folderKey($request->query('folder'));
        $q = mb_substr(trim((string) $request->query('q')), 0, 100);
        $per = (int) config('staffmail.per_page', 25);

        return $this->withMailbox($request, function (Mailbox $box) use ($folder, $q, $per, $request) {
            $folders = $box->folders();
            $uids = $box->uids($folder, $q);
            $total = count($uids);
            $pages = max(1, (int) ceil($total / $per));
            $page = min(max(1, (int) $request->query('page', 1)), $pages);
            $items = $box->summaries($folder, array_slice($uids, ($page - 1) * $per, $per));

            return view('staff.mail.index', [
                'folders' => $folders, 'folder' => $folder, 'items' => $items, 'q' => $q,
                'total' => $total, 'page' => $page, 'pages' => $pages,
                'from' => $total ? ($page - 1) * $per + 1 : 0, 'to' => min($total, $page * $per),
            ]);
        });
    }

    public function show(Request $request, string $folder, int $uid)
    {
        $folder = $this->folderKey($folder);
        $q = mb_substr(trim((string) $request->query('q')), 0, 100);

        return $this->withMailbox($request, function (Mailbox $box) use ($folder, $uid, $q) {
            $message = $box->message($folder, $uid);
            abort_if(! $message, 404);
            if (! $message['seen']) {
                $box->setSeen($folder, [$uid], true);
            }
            $uids = $box->uids($folder, $q);
            $pos = array_search($uid, $uids, true);

            return view('staff.mail.show', [
                'folders' => $box->folders(), 'folder' => $folder, 'm' => $message, 'q' => $q,
                'position' => $pos === false ? null : $pos + 1, 'total' => count($uids),
                'prev' => $pos !== false && $pos > 0 ? $uids[$pos - 1] : null,
                'next' => $pos !== false && isset($uids[$pos + 1]) ? $uids[$pos + 1] : null,
            ]);
        });
    }

    public function attachment(Request $request, string $folder, int $uid, int $index)
    {
        $folder = $this->folderKey($folder);

        return $this->withMailbox($request, function (Mailbox $box) use ($folder, $uid, $index) {
            $a = $box->attachment($folder, $uid, $index);
            abort_if(! $a, 404);
            $name = str_replace(["\r", "\n", '/', '\\'], '', $a['name']) ?: 'attachment';
            $fallback = preg_replace('/[^\x20-\x7e]|%/', '_', $name);

            // always a download, never shown inline: an attached .html must not run on our origin
            return response($a['content'], 200, [
                'Content-Type' => 'application/octet-stream',
                'Content-Disposition' => HeaderUtils::makeDisposition(HeaderUtils::DISPOSITION_ATTACHMENT, $name, $fallback),
                'X-Content-Type-Options' => 'nosniff',
                'Content-Security-Policy' => "default-src 'none'; sandbox",
            ]);
        });
    }

    public function compose(Request $request)
    {
        $mode = in_array($request->query('mode'), ['reply', 'reply-all', 'forward'], true) ? $request->query('mode') : null;
        if (! $mode) {
            return $this->withMailbox($request, fn (Mailbox $box) => $this->composeView($box, ['to' => (string) $request->query('to', '')]));
        }
        $folder = $this->folderKey($request->query('folder'));
        $uid = (int) $request->query('uid');

        return $this->withMailbox($request, function (Mailbox $box) use ($mode, $folder, $uid, $request) {
            $orig = $box->message($folder, $uid);
            abort_if(! $orig, 404);
            $me = strtolower($request->user()->email);
            $fmt = fn (array $list) => implode(', ', array_map(fn ($a) => $a['name'] ? '"' . str_replace('"', '', $a['name']) . '" <' . $a['email'] . '>' : $a['email'], $list));
            $replyTo = $orig['reply_to'] ?: [$orig['from']];
            $to = $cc = '';
            if ($mode === 'reply') {
                $to = $fmt($replyTo);
            } elseif ($mode === 'reply-all') {
                $seen = [$me => true];
                $pick = function (array $list) use (&$seen) {
                    return array_values(array_filter($list, function ($a) use (&$seen) {
                        if (isset($seen[$a['email']])) {
                            return false;
                        }

                        return $seen[$a['email']] = true;
                    }));
                };
                $to = $fmt($pick(array_merge($replyTo, $orig['to'])));
                $cc = $fmt($pick($orig['cc']));
            }
            $subject = trim((string) $orig['subject']);
            $prefix = $mode === 'forward' ? 'Fwd: ' : 'Re: ';
            if (! preg_match('/^' . ($mode === 'forward' ? 'fwd?' : 're') . ':/i', $subject)) {
                $subject = $prefix . $subject;
            }
            $who = $orig['from']['name'] ? $orig['from']['name'] . ' <' . $orig['from']['email'] . '>' : $orig['from']['email'];
            $text = $this->plainBody($orig);
            if ($mode === 'forward') {
                $body = "\n\n---------- Forwarded message ----------\nFrom: {$who}\nDate: " . ($orig['date']?->format('D, j M Y H:i') ?? '')
                    . "\nSubject: {$orig['subject']}\nTo: " . $fmt($orig['to']) . ($orig['cc'] ? "\nCc: " . $fmt($orig['cc']) : '') . "\n\n" . $text;
            } else {
                $body = "\n\nOn " . ($orig['date']?->format('D, j M Y \a\t H:i') ?? 'an earlier date') . ", {$who} wrote:\n"
                    . preg_replace('/^/m', '> ', rtrim($text));
            }

            return $this->composeView($box, [
                'to' => $to, 'cc' => $cc, 'subject' => $subject, 'body' => $body,
                'mode' => $mode, 'ref_folder' => $folder, 'ref_uid' => $uid,
                'forward_attachments' => $mode === 'forward' ? $orig['attachments'] : [],
            ]);
        });
    }

    public function send(Request $request)
    {
        $kb = (int) config('staffmail.max_attachment_kb', 10240);
        $v = Validator::make($request->all(), [
            'to' => ['required', 'string', 'max:4000'],
            'cc' => ['nullable', 'string', 'max:4000'],
            'bcc' => ['nullable', 'string', 'max:4000'],
            'subject' => ['nullable', 'string', 'max:500'],
            'body' => ['nullable', 'string', 'max:300000'],
            'attachments' => ['nullable', 'array', 'max:10'],
            'attachments.*' => ['file', 'max:' . $kb],
            'mode' => ['nullable', 'in:reply,reply-all,forward'],
            'ref_folder' => ['nullable', 'in:' . implode(',', array_keys(Mailbox::FOLDERS))],
            'ref_uid' => ['nullable', 'integer', 'min:1'],
            'include_attachments' => ['nullable', 'boolean'],
        ], [], ['to' => 'To']);

        $lists = [];
        foreach (['to', 'cc', 'bcc'] as $f) {
            [$lists[$f], $bad] = $this->parseRecipients((string) $request->input($f, ''));
            if ($bad) {
                $v->after(fn ($v) => $v->errors()->add($f, 'Check these addresses: ' . implode(', ', array_slice($bad, 0, 3))));
            }
        }
        $v->after(function ($v) use ($lists, $request, $kb) {
            if (! $lists['to'] && ! $v->errors()->has('to')) {
                $v->errors()->add('to', 'Add at least one address.');
            }
            if (count($lists['to']) + count($lists['cc']) + count($lists['bcc']) > 50) {
                $v->errors()->add('to', 'At most 50 recipients.');
            }
            $size = array_sum(array_map(fn ($f) => $f?->getSize() ?? 0, (array) $request->file('attachments', [])));
            if ($size > config('staffmail.max_total_attachment_kb', 20480) * 1024) {
                $v->errors()->add('attachments', 'Attachments add up to more than ' . round(config('staffmail.max_total_attachment_kb', 20480) / 1024) . ' MB.');
            }
        });

        $user = $request->user();
        $password = $this->creds->password($request);
        if ($password === null) {
            return $this->toUnlock($request, 'Your mailbox locked before this was sent. Unlock it and send again (your text was not kept).');
        }

        try {
            $box = $this->server->open($user->email, $password);
        } catch (MailAuthFailed) {
            $this->creds->forget($request);

            return $this->toUnlock($request, 'Your mailbox password has changed. Type it again, then send again (your text was not kept).');
        } catch (MailUnavailable $e) {
            report($e);

            return $this->unavailable();
        }

        try {
            $refFolder = $request->input('ref_folder');
            $refUid = (int) $request->input('ref_uid');
            $mode = $request->input('mode');
            $orig = ($mode && $refFolder && $refUid) ? $box->message($refFolder, $refUid) : null;

            if ($v->fails()) {
                return $this->composeView($box, $request->only(['to', 'cc', 'bcc', 'subject', 'body', 'mode', 'ref_folder', 'ref_uid'])
                    + ['forward_attachments' => $mode === 'forward' ? ($orig['attachments'] ?? []) : []], $v->errors());
            }

            $email = (new Email())
                ->from(new Address($user->email, (string) $user->name))
                ->to(...$lists['to'])
                ->subject((string) $request->input('subject', ''))
                ->text(str_replace("\r\n", "\n", (string) $request->input('body', '')));
            if ($lists['cc']) {
                $email->cc(...$lists['cc']);
            }
            if ($lists['bcc']) {
                $email->bcc(...$lists['bcc']);
            }
            if ($orig && in_array($mode, ['reply', 'reply-all'], true) && $orig['message_id']) {
                $email->getHeaders()->addIdHeader('In-Reply-To', trim($orig['message_id'], '<>'));
                $refs = array_map(fn ($id) => trim($id, '<>'), array_values(array_unique([...$orig['references'], $orig['message_id']])));
                $email->getHeaders()->addIdHeader('References', array_slice($refs, -20));
            }
            if ($orig && $mode === 'forward' && $request->boolean('include_attachments', true)) {
                foreach ($orig['attachments'] as $a) {
                    if ($file = $box->attachment($refFolder, $refUid, $a['index'])) {
                        $email->attach($file['content'], $file['name'], $file['mime']);
                    }
                }
            }
            foreach ((array) $request->file('attachments', []) as $file) {
                if ($file && $file->isValid()) {
                    $email->attach($file->get(), $file->getClientOriginalName(), $file->getMimeType() ?: 'application/octet-stream');
                }
            }

            try {
                $raw = $this->server->send($user->email, $password, $email);
            } catch (MailAuthFailed) {
                $this->creds->forget($request);

                return $this->toUnlock($request, 'Your mailbox password has changed. Type it again, then send again (your text was not kept).');
            } catch (MailUnavailable $e) {
                report($e);

                return $this->composeView($box, $request->only(['to', 'cc', 'bcc', 'subject', 'body', 'mode', 'ref_folder', 'ref_uid'])
                    + ['forward_attachments' => $mode === 'forward' ? ($orig['attachments'] ?? []) : []],
                    new \Illuminate\Support\MessageBag(['send' => 'The mail server did not take the message. Nothing was sent; try again in a minute.']));
            }

            $note = 'Message sent.';
            try {
                $box->appendSent($raw);
            } catch (\Throwable $e) {
                report(new MailUnavailable('Sent, but the copy for Sent could not be saved (' . class_basename($e) . ').'));
                $note = 'Message sent. The copy for your Sent folder could not be saved.';
            }
            if ($orig && in_array($mode, ['reply', 'reply-all'], true)) {
                try { $box->setAnswered($refFolder, $refUid); } catch (\Throwable) {}
            }
        } finally {
            $box->close();
        }

        return $orig && $refFolder
            ? redirect()->route('staff.mail.show', ['folder' => $refFolder, 'uid' => $refUid])->with('success', $note)
            : redirect()->route('staff.mail.index')->with('success', $note);
    }

    public function bulk(Request $request, string $folder)
    {
        $folder = $this->folderKey($folder);
        $data = $request->validate([
            'action' => ['required', 'in:read,unread,delete,archive,spam,inbox'],
            'uids' => ['required', 'array', 'min:1', 'max:200'],
            'uids.*' => ['integer', 'min:1'],
            'page' => ['nullable', 'integer', 'min:1'],
            'q' => ['nullable', 'string', 'max:100'],
        ]);
        $uids = array_values(array_unique(array_map('intval', $data['uids'])));
        $n = count($uids);
        $msgs = $n === 1 ? '1 message' : "{$n} messages";

        return $this->withMailbox($request, function (Mailbox $box) use ($folder, $uids, $data, $msgs) {
            $note = match ($data['action']) {
                'read' => [$box->setSeen($folder, $uids, true), "Marked {$msgs} as read."][1],
                'unread' => [$box->setSeen($folder, $uids, false), "Marked {$msgs} as unread."][1],
                'delete' => $folder === 'trash'
                    ? [$box->destroy($folder, $uids), "Deleted {$msgs} for good."][1]
                    : [$box->move($folder, $uids, 'trash'), "Moved {$msgs} to Trash."][1],
                'archive' => [$box->move($folder, $uids, 'archive'), "Archived {$msgs}."][1],
                'spam' => [$box->move($folder, $uids, 'spam'), "Moved {$msgs} to Spam."][1],
                'inbox' => [$box->move($folder, $uids, 'inbox'), "Moved {$msgs} to Inbox."][1],
            };

            return redirect()->route('staff.mail.index', array_filter(['folder' => $folder, 'page' => $data['page'] ?? null, 'q' => $data['q'] ?? null]))
                ->with('success', $note);
        });
    }

    public function unlockForm(Request $request)
    {
        if ($this->creds->password($request) !== null) {
            return redirect()->route('staff.mail.index');
        }

        return view('staff.mail.unlock', ['email' => $request->user()->email]);
    }

    public function unlock(Request $request)
    {
        $request->validate(['password' => ['required', 'string', 'max:512']]);
        $email = $request->user()->email;

        try {
            $this->server->open($email, (string) $request->input('password'))->close();
        } catch (MailAuthFailed) {
            return redirect()->route('staff.mail.unlock')->withErrors(['password' => 'The mail server did not accept that password for ' . $email . '.']);
        } catch (MailUnavailable $e) {
            report($e);

            return redirect()->route('staff.mail.unlock')->withErrors(['password' => 'The mail server could not be reached. Try again in a minute.']);
        }
        $this->creds->store($request, $email, (string) $request->input('password'));
        $to = (string) $request->session()->pull(self::INTENDED, '');

        return redirect(str_starts_with($to, '/staff/mail') && ! str_starts_with($to, '//') ? $to : route('staff.mail.index'));
    }

    public function lock(Request $request)
    {
        $this->creds->forget($request);

        return redirect()->route('dashboard')->with('success', 'Mailbox locked. You will need your mailbox password to open it again.');
    }

    // ---------------------------------------------------------------------------------------------------------

    private function withMailbox(Request $request, callable $fn)
    {
        $password = $this->creds->password($request);
        if ($password === null) {
            return $this->toUnlock($request);
        }
        try {
            $box = $this->server->open($request->user()->email, $password);
        } catch (MailAuthFailed) {
            $this->creds->forget($request);

            return $this->toUnlock($request, 'Your mailbox password has changed. Type the new one.');
        } catch (MailUnavailable $e) {
            report($e);

            return $this->unavailable();
        }
        try {
            return $fn($box);
        } catch (MailUnavailable $e) {
            report($e);

            return $this->unavailable();
        } catch (\Webklex\PHPIMAP\Exceptions\ResponseException|\Webklex\PHPIMAP\Exceptions\RuntimeException $e) {
            report(new MailUnavailable('The mail server returned an error (' . class_basename($e) . ').'));

            return $this->unavailable();
        } finally {
            $box->close();
        }
    }

    private function toUnlock(Request $request, ?string $message = null)
    {
        if ($request->isMethod('GET')) {
            $request->session()->put(self::INTENDED, '/' . ltrim($request->path(), '/')); // the path only: never a search term
        }
        $r = redirect()->route('staff.mail.unlock');

        return $message ? $r->with('warning', $message) : $r;
    }

    private function unavailable()
    {
        return response()->view('staff.mail.unavailable', [], 503);
    }

    private function composeView(Mailbox $box, array $values, $errors = null)
    {
        $view = view('staff.mail.compose', [
            'folders' => $box->folders(), 'folder' => $values['ref_folder'] ?? 'inbox', 'v' => $values + ['to' => '', 'cc' => '', 'bcc' => '', 'subject' => '', 'body' => '', 'mode' => null, 'ref_folder' => null, 'ref_uid' => null, 'forward_attachments' => []],
        ]);
        if ($errors) {
            $view->withErrors($errors);

            return response($view, 422);
        }

        return $view;
    }

    /** @return array{0: list<Address>, 1: list<string>} the good addresses and the bits that are not addresses */
    private function parseRecipients(string $value): array
    {
        $good = $bad = [];
        foreach (preg_split('/[,;\n]+(?=(?:[^"]*"[^"]*")*[^"]*$)/', $value) as $part) {
            $part = trim($part);
            if ($part === '') {
                continue;
            }
            $a = Mime::addresses($part)[0] ?? null;
            if ($a && filter_var($a['email'], FILTER_VALIDATE_EMAIL)) {
                $good[strtolower($a['email'])] = new Address($a['email'], $a['name']);
            } else {
                $bad[] = mb_substr($part, 0, 60);
            }
        }

        return [array_values($good), $bad];
    }

    private function plainBody(array $m): string
    {
        if (trim((string) $m['text']) !== '') {
            return str_replace("\r\n", "\n", (string) $m['text']);
        }
        $html = preg_replace('#<(style|script|head)\b[^>]*>.*?</\1>#is', '', (string) $m['html']);
        $html = preg_replace('#<br\s*/?>|</p>|</div>|</tr>|</h\d>#i', "\n", $html);

        return trim(html_entity_decode(strip_tags($html), ENT_QUOTES | ENT_HTML5, 'UTF-8'));
    }

    private function folderKey(?string $key): string
    {
        return array_key_exists((string) $key, Mailbox::FOLDERS) ? (string) $key : 'inbox';
    }
}
