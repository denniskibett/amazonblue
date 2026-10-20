<?php

namespace Tests\Feature;

use App\Models\User;
use App\Notifications\KycReviewed;
use App\Support\Emails;
use Database\Seeders\RoleSeeder;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * The branded emails (resources/design/emails → emails.branded): every template renders as HTML and text,
 * customer text is escaped, Laravel's account emails use the design, and the preview is super-admin only.
 */
class BrandedEmailsTest extends TestCase
{
    use RefreshDatabase;

    public function test_every_template_renders_as_html_and_text(): void
    {
        $samples = Emails::samples();
        $this->assertCount(24, $samples);

        foreach ($samples as $name => $m) {
            $html = (string) Emails::mail($m)->render();
            $this->assertStringContainsString('aimzone <span style="font-weight:300">ventures</span>', $html, $name);
            $this->assertStringContainsString(e($m['heading']), $html, $name);
            foreach ($m['buttons'] ?? [] as [$text, $url]) {
                $this->assertStringContainsString(e($url), $html, $name);
            }

            $text = view('emails.branded-text', ['m' => $m])->render();
            $this->assertStringContainsString($m['heading'], $text, $name);
            $this->assertStringNotContainsString('<', $text, $name);
        }
    }

    public function test_customer_text_is_escaped(): void
    {
        $html = (string) Emails::mail(Emails::kycActionNeeded('Mary', '<script>alert(1)</script>'))->render();

        $this->assertStringNotContainsString('<script>alert(1)</script>', $html);
        $this->assertStringContainsString('&lt;script&gt;', $html);
    }

    public function test_notifications_and_account_emails_use_the_design(): void
    {
        $user = User::factory()->create(['name' => 'Mary Wanjiru']);

        $approved = (string) (new KycReviewed(true))->toMail($user)->render();
        $this->assertStringContainsString('You&#039;re verified, Mary', $approved);
        $this->assertStringContainsString(route('account.borrow'), $approved);

        $verify = (new VerifyEmail)->toMail($user);
        $this->assertSame('Confirm your email for AIMZONE Ventures', $verify->subject);
        $this->assertStringContainsString('Confirm my email', (string) $verify->render());

        $reset = (new ResetPassword('tok123'))->toMail($user);
        $this->assertSame('Reset your AIMZONE password', $reset->subject);
        $this->assertStringContainsString('reset-password/tok123', (string) $reset->render());
    }

    public function test_the_preview_page_is_for_the_super_admin(): void
    {
        $this->seed(RoleSeeder::class);
        $super = User::factory()->create();
        $super->assignRole('super-admin');
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $this->actingAs($super)->get('/system/emails')->assertOk()->assertSee('Email templates (24)');
        $this->actingAs($super)->get('/system/emails/0')->assertOk()->assertSee('Welcome, Mary');
        $this->actingAs($admin)->get('/system/emails')->assertForbidden();
    }
}
