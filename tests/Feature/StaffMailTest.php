<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\StaffMail\MailAuthFailed;
use App\Services\StaffMail\Mailbox;
use App\Services\StaffMail\MailCredentials;
use App\Services\StaffMail\MailServer;
use App\Services\StaffMail\Mime;
use App\Support\Integrations;
use App\Support\NavMenu;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use Symfony\Component\Mime\Email;
use Tests\TestCase;

/** Staff mail (16 Nov 2026): who can reach it, the password vault, and the IMAP/SMTP layer (faked here). */
class StaffMailTest extends TestCase
{
    use RefreshDatabase;

    private const PASSWORD = 'mailbox-secret-123';

    private FakeMailServer $server;
    private User $staff;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);
        config(['staffmail.domain' => 'sharet.africa', 'staffmail.enabled' => true]);
        $this->server = new FakeMailServer('aimzone@sharet.africa', self::PASSWORD);
        $this->app->instance(MailServer::class, $this->server);

        $this->staff = User::factory()->create(['email' => 'aimzone@sharet.africa', 'name' => 'Aimzone Desk', 'email_verified_at' => now()]);
        $this->staff->assignRole('manager');
    }

    private function unlocked(?User $user = null): static
    {
        $res = $this->actingAs($user ?? $this->staff)->post(route('staff.mail.unlock.store'), ['password' => self::PASSWORD]);
        $res->assertRedirect();
        $cookie = $res->getCookie(MailCredentials::COOKIE);
        $this->assertNotNull($cookie, 'the key cookie is set');

        return $this->withCookie(MailCredentials::COOKIE, $cookie->getValue());
    }

    // ---- who can reach it -----------------------------------------------------------------------------------

    public function test_staff_off_the_mail_domain_get_a_404_and_no_menu_item(): void
    {
        $other = User::factory()->create(['email' => 'ops@gmail.com', 'email_verified_at' => now()]);
        $other->assignRole('super-admin');

        $this->actingAs($other)->get(route('staff.mail.index'))->assertNotFound();
        $this->actingAs($other)->get(route('staff.mail.unlock'))->assertNotFound();
        $this->actingAs($other)->post(route('staff.mail.send'), ['to' => 'x@y.com'])->assertNotFound();
        $this->assertNotContains('staff.mail.index', $this->menuRoutes($other));
        $this->assertSame([], $this->server->opened);
    }

    public function test_a_customer_with_a_domain_address_gets_a_404(): void
    {
        $borrower = User::factory()->create(['email' => 'someone@sharet.africa', 'email_verified_at' => now()]);
        $borrower->assignRole('borrower');

        $this->actingAs($borrower)->get(route('staff.mail.index'))->assertNotFound();
        $this->assertFalse($borrower->canUseStaffMail());
    }

    public function test_a_lookalike_domain_is_not_the_mail_domain(): void
    {
        $u = User::factory()->create(['email' => 'x@notsharet.africa', 'email_verified_at' => now()]);
        $u->assignRole('manager');
        $this->assertFalse($u->canUseStaffMail());
        $this->actingAs($u)->get(route('staff.mail.index'))->assertNotFound();
    }

    public function test_guests_are_sent_to_sign_in(): void
    {
        $this->get(route('staff.mail.index'))->assertRedirect(route('login'));
    }

    public function test_domain_staff_see_the_menu_item_and_the_header_link(): void
    {
        $this->assertContains('staff.mail.index', $this->menuRoutes($this->staff));
        $this->actingAs($this->staff)->get(route('dashboard'))->assertOk()->assertSee(route('staff.mail.index'), false);
    }

    // ---- the integration ------------------------------------------------------------------------------------

    public function test_while_the_integration_is_off_mail_does_not_exist_for_anyone(): void
    {
        config(['staffmail.enabled' => false]);
        $this->assertFalse($this->staff->canUseStaffMail());
        $this->actingAs($this->staff)->get(route('staff.mail.index'))->assertNotFound();
        $this->assertNotContains('staff.mail.index', $this->menuRoutes($this->staff));
    }

    public function test_it_is_set_up_and_switched_on_from_the_integrations_page(): void
    {
        config(['staffmail.enabled' => false, 'staffmail.domain' => 'example.org']);
        $admin = User::factory()->create(['email' => 'root@gmail.com', 'email_verified_at' => now()]);
        $admin->assignRole('super-admin');

        $this->actingAs($admin)->get(route('staff.integrations'))->assertOk()->assertSee('Staff mail')->assertSee('Allowed domain');
        // can't switch on before it is connected (domain saved)
        $this->post(route('staff.integrations.toggle', 'staff-mail'))->assertSessionHas('error');

        $this->post(route('staff.integrations.update', 'staff-mail'), ['fields' => ['STAFF_MAIL_DOMAIN' => 'not a domain']])->assertSessionHasErrors('fields.STAFF_MAIL_DOMAIN');
        $this->post(route('staff.integrations.update', 'staff-mail'), ['fields' => ['STAFF_MAIL_DOMAIN' => '@Sharet.Africa', 'STAFF_MAIL_IMAP_HOST' => '127.0.0.1',
            'STAFF_MAIL_IMAP_PORT' => '993', 'STAFF_MAIL_IMAP_ENCRYPTION' => 'ssl', 'STAFF_MAIL_IMAP_VALIDATE_CERT' => '0', 'STAFF_MAIL_SMTP_PORT' => '587']])
            ->assertSessionHasNoErrors();
        $this->post(route('staff.integrations.toggle', 'staff-mail'))->assertSessionHas('success', 'Staff mail switched on.');

        Integrations::apply(); // what boot does on the next request
        $this->assertTrue(config('staffmail.enabled'));
        $this->assertSame('sharet.africa', config('staffmail.domain'));
        $this->assertSame(993, (int) config('staffmail.imap.port'));
        $this->assertTrue($this->staff->fresh()->canUseStaffMail());

        // switched off again: gone
        $this->post(route('staff.integrations.toggle', 'staff-mail'));
        Integrations::apply();
        $this->assertFalse(config('staffmail.enabled'));
    }

    public function test_test_connection_keeps_its_result_on_the_card(): void
    {
        $admin = User::factory()->create(['email' => 'root@gmail.com', 'email_verified_at' => now()]);
        $admin->assignRole('super-admin');

        $this->actingAs($admin)->post(route('staff.integrations.update', 'staff-mail'), ['fields' => ['STAFF_MAIL_DOMAIN' => 'sharet.africa']]);
        $this->get(route('staff.integrations'))->assertSee(route('staff.integrations.staff-mail.test'), false)->assertSee('not tested yet');

        $this->from(route('staff.integrations'))->post(route('staff.integrations.staff-mail.test'))
            ->assertRedirect(route('staff.integrations'))->assertSessionHas('success');
        $this->assertSame(1, $this->server->probes);
        $this->assertTrue(Integrations::saved('staff-mail')[Integrations::TEST]['ok']);

        $this->server->probeOk = false;
        $this->post(route('staff.integrations.staff-mail.test'))->assertSessionHas('warning');
        $this->get(route('staff.integrations'))->assertSee('last test failed');
    }

    public function test_only_integration_managers_can_test_or_change_it(): void
    {
        $officer = User::factory()->create(['email' => 'lo@sharet.africa', 'email_verified_at' => now()]);
        $officer->assignRole('loan-officer');
        $this->actingAs($officer)->post(route('staff.integrations.staff-mail.test'))->assertForbidden();
        $this->actingAs($officer)->post(route('staff.integrations.toggle', 'staff-mail'))->assertForbidden();
        $this->assertSame(0, $this->server->probes);
    }

    // ---- unlocking ----------------------------------------------------------------------------------------

    public function test_the_mailbox_asks_for_its_password_first(): void
    {
        $this->actingAs($this->staff)->get(route('staff.mail.index'))->assertRedirect(route('staff.mail.unlock'));
        $this->actingAs($this->staff)->get(route('staff.mail.unlock'))->assertOk()->assertSee('Type your mailbox password')
            ->assertHeader('Cache-Control', 'max-age=0, no-store, private');
    }

    public function test_a_wrong_password_is_refused_and_nothing_is_kept(): void
    {
        $this->actingAs($this->staff)->post(route('staff.mail.unlock.store'), ['password' => 'nope'])
            ->assertRedirect(route('staff.mail.unlock'))->assertSessionHasErrors('password');
        $this->assertNull(session(MailCredentials::SESSION));
    }

    public function test_the_session_holds_only_ciphertext_and_the_key_lives_in_the_cookie(): void
    {
        $res = $this->actingAs($this->staff)->post(route('staff.mail.unlock.store'), ['password' => self::PASSWORD]);
        $res->assertRedirect(route('staff.mail.index'));

        $stored = session(MailCredentials::SESSION);
        $this->assertIsArray($stored);
        $this->assertStringNotContainsString(self::PASSWORD, json_encode($stored));
        $this->assertStringNotContainsString(base64_encode(self::PASSWORD), json_encode($stored));

        $cookie = $res->getCookie(MailCredentials::COOKIE);
        $this->assertTrue($cookie->isHttpOnly());
        $this->assertSame('strict', $cookie->getSameSite());
        $this->assertSame('/staff/mail', $cookie->getPath());
        $this->assertSame(0, $cookie->getExpiresTime(), 'gone when the browser closes');
        $this->assertStringNotContainsString(self::PASSWORD, (string) base64_decode($cookie->getValue()));

        // without the browser's key the session alone opens nothing
        $this->actingAs($this->staff)->withCookie(MailCredentials::COOKIE, base64_encode(random_bytes(32)))
            ->get(route('staff.mail.index'))->assertRedirect(route('staff.mail.unlock'));
    }

    public function test_another_user_cannot_use_the_stored_password(): void
    {
        $this->unlocked();
        $other = User::factory()->create(['email' => 'desk2@sharet.africa', 'email_verified_at' => now()]);
        $other->assignRole('manager');

        $this->actingAs($other)->get(route('staff.mail.index'))->assertRedirect(route('staff.mail.unlock'));
    }

    public function test_it_locks_after_the_idle_time(): void
    {
        $this->unlocked();
        $this->get(route('staff.mail.index'))->assertOk();
        $this->travel(config('staffmail.idle_minutes') + 1)->minutes();
        $this->get(route('staff.mail.index'))->assertRedirect(route('staff.mail.unlock'));
    }

    public function test_lock_forgets_the_password(): void
    {
        $this->unlocked()->post(route('staff.mail.lock'))->assertRedirect(route('dashboard'));
        $this->assertNull(session(MailCredentials::SESSION));
        $this->get(route('staff.mail.index'))->assertRedirect(route('staff.mail.unlock'));
    }

    public function test_a_changed_mailbox_password_locks_it_again(): void
    {
        $this->unlocked();
        $this->server->password = 'changed-on-the-server';
        $this->get(route('staff.mail.index'))->assertRedirect(route('staff.mail.unlock'));
        $this->assertNull(session(MailCredentials::SESSION));
    }

    // ---- reading ------------------------------------------------------------------------------------------

    public function test_the_inbox_lists_messages_newest_first_with_counts(): void
    {
        $this->server->seed('inbox', ['subject' => 'Older note', 'text' => 'first one', 'seen' => true]);
        $this->server->seed('inbox', ['subject' => 'Loan statement query', 'text' => 'Please resend the statement', 'from' => ['name' => 'Jane Borrower', 'email' => 'jane@example.com']]);
        $this->server->seed('spam', ['subject' => 'You won']);

        $res = $this->unlocked()->get(route('staff.mail.index'))->assertOk()
            ->assertSeeInOrder(['Loan statement query', 'Please resend the statement', 'Older note'])
            ->assertSee('Jane Borrower')
            ->assertSee('Showing')->assertSee('1&ndash;2', false);
        $this->assertSame(['opened' => 'aimzone@sharet.africa'], ['opened' => $this->server->opened[0]]);

        $this->get(route('staff.mail.index', ['folder' => 'spam']))->assertOk()->assertSee('You won');
        $this->get(route('staff.mail.index', ['q' => 'statement']))->assertOk()->assertSee('Loan statement query')->assertDontSee('Older note');
    }

    public function test_the_list_pages_through_the_folder(): void
    {
        config(['staffmail.per_page' => 2]);
        foreach (['m1', 'm2', 'm3'] as $s) {
            $this->server->seed('inbox', ['subject' => $s]);
        }
        $this->unlocked()->get(route('staff.mail.index'))->assertSee('m3')->assertSee('m2')->assertDontSee('>m1<', false);
        $this->get(route('staff.mail.index', ['page' => 2]))->assertSee('m1')->assertSee('3&ndash;3', false);
    }

    public function test_opening_a_message_shows_it_and_marks_it_read(): void
    {
        $uid = $this->server->seed('inbox', ['subject' => 'Contract', 'text' => 'Plain body here', 'html' => '<p>Hello <b>there</b></p><script>alert(1)</script>',
            'attachments' => [['name' => 'Guidelines.pdf', 'mime' => 'application/pdf', 'content' => '%PDF-1.4 test']]]);

        $res = $this->unlocked()->get(route('staff.mail.show', ['folder' => 'inbox', 'uid' => $uid]))->assertOk()
            ->assertSee('Contract')->assertSee('Guidelines.pdf')->assertSee('1 of 1')
            ->assertSee('sandbox="allow-popups allow-popups-to-escape-sandbox"', false)
            ->assertSee('Reply')->assertSee('Reply all')->assertSee('Forward');
        // the HTML only ever appears escaped inside srcdoc, never as live markup on our page
        $this->assertStringNotContainsString('<script>alert(1)</script>', $res->getContent());
        $this->assertTrue($this->server->folders['inbox'][$uid]['seen']);
    }

    public function test_an_attachment_downloads_and_never_renders_inline(): void
    {
        $uid = $this->server->seed('inbox', ['attachments' => [['name' => 'evil.html', 'mime' => 'text/html', 'content' => '<script>x</script>']]]);

        $res = $this->unlocked()->get(route('staff.mail.attachment', ['folder' => 'inbox', 'uid' => $uid, 'index' => 0]))->assertOk();
        $this->assertSame('application/octet-stream', $res->headers->get('Content-Type'));
        $this->assertStringStartsWith('attachment;', $res->headers->get('Content-Disposition'));
        $this->assertSame('nosniff', $res->headers->get('X-Content-Type-Options'));
        $this->get(route('staff.mail.attachment', ['folder' => 'inbox', 'uid' => $uid, 'index' => 5]))->assertNotFound();
    }

    public function test_a_missing_message_is_a_404(): void
    {
        $this->unlocked()->get(route('staff.mail.show', ['folder' => 'inbox', 'uid' => 999]))->assertNotFound();
    }

    // ---- actions ------------------------------------------------------------------------------------------

    public function test_bulk_actions_mark_move_and_delete(): void
    {
        $a = $this->server->seed('inbox', ['subject' => 'A']);
        $b = $this->server->seed('inbox', ['subject' => 'B']);
        $this->unlocked();

        $this->post(route('staff.mail.bulk', 'inbox'), ['action' => 'read', 'uids' => [$a, $b]])->assertRedirect(route('staff.mail.index', ['folder' => 'inbox']));
        $this->assertTrue($this->server->folders['inbox'][$a]['seen'] && $this->server->folders['inbox'][$b]['seen']);

        $this->post(route('staff.mail.bulk', 'inbox'), ['action' => 'archive', 'uids' => [$a]]);
        $this->assertCount(1, $this->server->folders['archive']);

        $this->post(route('staff.mail.bulk', 'inbox'), ['action' => 'delete', 'uids' => [$b]]);
        $this->assertSame([], $this->server->folders['inbox']);
        $trashUid = array_key_first($this->server->folders['trash']);

        $this->post(route('staff.mail.bulk', 'trash'), ['action' => 'delete', 'uids' => [$trashUid]]);
        $this->assertSame([], $this->server->folders['trash'], 'deleting from Trash deletes for good');
    }

    // ---- sending ------------------------------------------------------------------------------------------

    public function test_compose_and_send_goes_out_as_the_staff_member_and_lands_in_sent(): void
    {
        $this->unlocked()->get(route('staff.mail.compose'))->assertOk()->assertSee('New message');

        $this->post(route('staff.mail.send'), [
            'to' => 'Jane Borrower <jane@example.com>, ops@example.org',
            'cc' => 'boss@example.com',
            'subject' => 'Your statement',
            'body' => "Hi Jane,\nAttached.",
            'attachments' => [UploadedFile::fake()->createWithContent('statement.txt', 'balance 100')],
        ])->assertRedirect(route('staff.mail.index'))->assertSessionHas('success', 'Message sent.');

        $this->assertCount(1, $this->server->sent);
        [$as, $email] = $this->server->sent[0];
        $this->assertSame('aimzone@sharet.africa', $as);
        $this->assertSame('aimzone@sharet.africa', $email->getFrom()[0]->getAddress());
        $this->assertSame(['jane@example.com', 'ops@example.org'], array_map(fn ($a) => $a->getAddress(), $email->getTo()));
        $this->assertSame('boss@example.com', $email->getCc()[0]->getAddress());
        $this->assertSame('Your statement', $email->getSubject());
        $this->assertSame('statement.txt', $email->getAttachments()[0]->getFilename());
        $this->assertCount(1, $this->server->folders['sent'], 'a copy is kept in Sent');
    }

    public function test_reply_threads_and_marks_the_original_answered(): void
    {
        $uid = $this->server->seed('inbox', ['subject' => 'Question', 'text' => 'Can I pay early?', 'message_id' => '<q1@example.com>',
            'from' => ['name' => 'Jane', 'email' => 'jane@example.com'], 'cc' => [['name' => '', 'email' => 'aimzone@sharet.africa'], ['name' => '', 'email' => 'tom@example.com']]]);
        $this->unlocked();

        $this->get(route('staff.mail.compose', ['mode' => 'reply', 'folder' => 'inbox', 'uid' => $uid]))->assertOk()
            ->assertSee('Re: Question')->assertSee('jane@example.com')->assertSee('&gt; Can I pay early?', false);
        // reply all leaves the staff member themself out
        $this->get(route('staff.mail.compose', ['mode' => 'reply-all', 'folder' => 'inbox', 'uid' => $uid]))->assertOk()
            ->assertSee('tom@example.com')->assertDontSee('value="aimzone@sharet.africa"', false);

        $this->post(route('staff.mail.send'), ['to' => 'jane@example.com', 'subject' => 'Re: Question', 'body' => 'Yes.', 'mode' => 'reply', 'ref_folder' => 'inbox', 'ref_uid' => $uid])
            ->assertRedirect(route('staff.mail.show', ['folder' => 'inbox', 'uid' => $uid]));

        $email = $this->server->sent[0][1];
        $this->assertSame(['q1@example.com'], $email->getHeaders()->get('In-Reply-To')->getIds());
        $this->assertTrue($this->server->folders['inbox'][$uid]['answered']);
    }

    public function test_forward_carries_the_original_attachments(): void
    {
        $uid = $this->server->seed('inbox', ['subject' => 'Docs', 'attachments' => [['name' => 'id.pdf', 'mime' => 'application/pdf', 'content' => 'PDF']]]);
        $this->unlocked()->get(route('staff.mail.compose', ['mode' => 'forward', 'folder' => 'inbox', 'uid' => $uid]))->assertOk()
            ->assertSee('Fwd: Docs')->assertSee('id.pdf');

        $this->post(route('staff.mail.send'), ['to' => 'legal@example.com', 'subject' => 'Fwd: Docs', 'body' => 'FYI', 'mode' => 'forward', 'ref_folder' => 'inbox', 'ref_uid' => $uid, 'include_attachments' => '1']);
        $this->assertSame('id.pdf', $this->server->sent[0][1]->getAttachments()[0]->getFilename());
    }

    public function test_bad_addresses_come_back_filled_in_without_sending_or_flashing_the_draft(): void
    {
        $this->unlocked()->post(route('staff.mail.send'), ['to' => 'jane@example.com, not-an-address', 'subject' => 'Hello', 'body' => 'Draft text stays on the page'])
            ->assertStatus(422)->assertSee('Check these addresses')->assertSee('Draft text stays on the page');

        $this->assertSame([], $this->server->sent);
        $this->assertNull(session('_old_input'), 'the draft is never flashed into the session');
    }

    public function test_send_needs_an_unlocked_mailbox(): void
    {
        $this->actingAs($this->staff)->post(route('staff.mail.send'), ['to' => 'x@example.com', 'body' => 'hi'])
            ->assertRedirect(route('staff.mail.unlock'));
        $this->assertSame([], $this->server->sent);
    }

    // ---- MIME helpers ------------------------------------------------------------------------------------

    public function test_the_preview_reads_the_first_text_part_of_a_multipart_message(): void
    {
        $headers = Mime::headers("From: =?UTF-8?B?SmFuZSDDhGJlbA==?= <Jane@Example.com>\r\nSubject: Hi\r\nContent-Type: multipart/alternative;\r\n boundary=\"b1\"\r\n");
        $body = "--b1\r\nContent-Type: text/plain; charset=utf-8\r\nContent-Transfer-Encoding: quoted-printable\r\n\r\nHello =E2=80=94 statement=\r\n attached\r\n--b1\r\nContent-Type: text/html\r\n\r\n<p>x</p>\r\n--b1--";

        $this->assertSame('Hello — statement attached', Mime::snippet($headers, $body));
        $this->assertSame([['name' => 'Jane Äbel', 'email' => 'jane@example.com']], Mime::addresses($headers['from']));
        $this->assertSame([['name' => 'Doe, J', 'email' => 'j@x.com'], ['name' => '', 'email' => 'k@y.com']], Mime::addresses('"Doe, J" <j@x.com>, k@y.com'));
    }

    private function menuRoutes(User $u): array
    {
        return collect(NavMenu::groups($u, false))->flatten(1)->pluck(1)->all();
    }
}

/** In-memory mail server standing in for Dovecot/Postfix. */
class FakeMailServer implements MailServer
{
    public array $folders = ['inbox' => [], 'sent' => [], 'drafts' => [], 'spam' => [], 'trash' => [], 'archive' => []];
    public array $opened = [];
    public array $sent = [];
    public int $next = 1;
    public int $probes = 0;
    public bool $probeOk = true;

    public function probe(): array
    {
        $this->probes++;

        return ['ok' => $this->probeOk, 'message' => $this->probeOk ? 'IMAP and SMTP answered.' : 'IMAP did not answer.'];
    }

    public function __construct(public string $email, public string $password)
    {
    }

    public function seed(string $folder, array $m): int
    {
        $uid = $this->next++;
        $this->folders[$folder][$uid] = $m + [
            'uid' => $uid, 'subject' => 'Subject ' . $uid, 'from' => ['name' => 'Sender', 'email' => 'sender@example.com'], 'to' => [['name' => '', 'email' => $this->email]],
            'cc' => [], 'reply_to' => [], 'date' => now()->subMinutes(100 - $uid), 'message_id' => '<m' . $uid . '@example.com>', 'references' => [],
            'seen' => false, 'flagged' => false, 'answered' => false, 'html' => null, 'text' => 'Body ' . $uid, 'attachments' => [],
        ];

        return $uid;
    }

    public function open(string $email, string $password): Mailbox
    {
        if ($email !== $this->email || $password !== $this->password) {
            throw new MailAuthFailed('refused');
        }
        $this->opened[] = $email;

        return new FakeMailbox($this);
    }

    public function send(string $email, string $password, Email $message): string
    {
        if ($password !== $this->password) {
            throw new MailAuthFailed('refused');
        }
        $this->sent[] = [$email, $message];

        return $message->toString();
    }
}

class FakeMailbox implements Mailbox
{
    public function __construct(private FakeMailServer $s)
    {
    }

    public function folders(): array
    {
        return collect(self::FOLDERS)->map(fn ($label, $k) => ['label' => $label, 'exists' => true, 'total' => count($this->s->folders[$k]),
            'unread' => collect($this->s->folders[$k])->where('seen', false)->count()])->all();
    }

    public function uids(string $folder, ?string $search = null): array
    {
        $ids = array_keys(array_filter($this->s->folders[$folder], fn ($m) => ! $search
            || str_contains(mb_strtolower($m['subject'] . ' ' . $m['text'] . ' ' . $m['from']['email']), mb_strtolower($search))));
        rsort($ids);

        return $ids;
    }

    public function summaries(string $folder, array $uids): array
    {
        return array_map(fn ($uid) => ($m = $this->s->folders[$folder][$uid]) ? [
            'uid' => $uid, 'from_name' => $m['from']['name'], 'from_email' => $m['from']['email'], 'to' => $m['to'][0]['email'] ?? '',
            'subject' => $m['subject'], 'snippet' => (string) $m['text'], 'date' => $m['date'], 'seen' => $m['seen'], 'flagged' => $m['flagged'], 'answered' => $m['answered'],
        ] : null, $uids);
    }

    public function message(string $folder, int $uid): ?array
    {
        $m = $this->s->folders[$folder][$uid] ?? null;
        if (! $m) {
            return null;
        }
        $m['attachments'] = array_map(fn ($a, $i) => ['index' => $i, 'name' => $a['name'], 'mime' => $a['mime'], 'size' => strlen($a['content']), 'inline_id' => null], $m['attachments'], array_keys($m['attachments']));

        return $m;
    }

    public function attachment(string $folder, int $uid, int $index): ?array
    {
        return $this->s->folders[$folder][$uid]['attachments'][$index] ?? null;
    }

    public function setSeen(string $folder, array $uids, bool $seen): void
    {
        foreach ($uids as $u) {
            $this->s->folders[$folder][$u]['seen'] = $seen;
        }
    }

    public function setAnswered(string $folder, int $uid): void
    {
        $this->s->folders[$folder][$uid]['answered'] = true;
    }

    public function move(string $folder, array $uids, string $target): void
    {
        foreach ($uids as $u) {
            if (isset($this->s->folders[$folder][$u])) {
                $new = $this->s->next++;
                $this->s->folders[$target][$new] = ['uid' => $new] + $this->s->folders[$folder][$u];
                unset($this->s->folders[$folder][$u]);
            }
        }
    }

    public function destroy(string $folder, array $uids): void
    {
        foreach ($uids as $u) {
            unset($this->s->folders[$folder][$u]);
        }
    }

    public function appendSent(string $raw): void
    {
        $this->s->seed('sent', ['subject' => 'sent copy', 'seen' => true, 'text' => substr($raw, 0, 50)]);
    }

    public function close(): void
    {
    }
}
