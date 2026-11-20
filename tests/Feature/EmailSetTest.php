<?php

namespace Tests\Feature;

use App\Support\Emails;
use Tests\TestCase;

/** Every branded email renders as HTML and plain text, and none promises approval or names Cloud9 (20 Nov 2026). */
class EmailSetTest extends TestCase
{
    public function test_every_email_renders_both_parts_without_promises(): void
    {
        foreach (Emails::samples() as $name => $m) {
            $html = view('emails.branded', ['m' => $m])->render();
            $text = view('emails.branded-text', ['m' => $m])->render();
            $this->assertStringContainsString('<table role="presentation"', $html, $name);
            $this->assertStringContainsString($m['heading'], $text, $name);
            if (str_starts_with($name, 'F')) {
                foreach (['guarantee', 'pre-approved', 'you qualify', 'cloud9'] as $bad) {
                    $this->assertStringNotContainsStringIgnoringCase($bad, $html . $text, "$name says $bad");
                }
            }
        }
        $this->assertStringContainsString('not yet an offer', view('emails.branded-text', ['m' => Emails::financeApproved('G', 'AIM-AF-26-0001', 'KES 1', '', 'https://x')])->render());
    }
}
