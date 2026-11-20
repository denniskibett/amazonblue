<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use App\Models\Integration;
use App\Services\AuditTrail;
use App\Services\Claude\Claude;
use App\Services\Sms\SmsSender;
use App\Support\Integrations;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * "Test connection" on the sysadmin cards (owner, 20 Nov 2026), like Sharet Pay's and Staff mail's: each makes one real
 * call with the settings in force and keeps the result on the card.
 *   Claude: one small request to Anthropic with the saved key and model.
 *   Sharet Ent SMS: one SMS under the platform sender (SHARETENT) to the owner's phone, never to anyone else.
 */
class IntegrationTestController extends Controller
{
    /** Every test SMS goes to the owner's phone (owner, 20 Nov 2026). */
    public const TEST_PHONE = '0717492048';

    public function claude(Request $request): RedirectResponse
    {
        abort_unless($request->user()->hasRole('super-admin'), 403, 'Claude is set by the system administrator.');

        return $this->keep($request, 'claude', Claude::test(), 'Claude');
    }

    public function sms(Request $request, SmsSender $sms): RedirectResponse
    {
        abort_unless($request->user()->hasRole('super-admin'), 403, 'The platform SMS sender is set by the system administrator.');
        $from = SmsSender::sender('otp')['sender_id'];
        $driver = (string) config('lending.notifications.sms.driver', 'log');
        $ok = $sms->otp(self::TEST_PHONE, 'Aimzone test SMS: the platform sender ' . $from . ' works. Sent from Integrations at ' . now()->format('H:i') . '.');
        $result = match (true) {
            ! $ok => ['ok' => false, 'message' => 'The test SMS to ' . self::TEST_PHONE . ' was not accepted (driver ' . $driver . '). See the log.'],
            $driver === 'log' => ['ok' => false, 'message' => 'The SMS driver is "log", so nothing was sent. Choose kenyasms on the KenyaSMS card.'],
            default => ['ok' => true, 'message' => 'Test SMS sent to ' . self::TEST_PHONE . ' as ' . $from . ' (' . $driver . ').'],
        };

        return $this->keep($request, 'sms_platform', $result, 'Platform SMS');
    }

    /** @param  array{ok: bool, message: string}  $result */
    private function keep(Request $request, string $slug, array $result, string $name): RedirectResponse
    {
        $def = Integrations::CATALOGUE[$slug];
        $row = Integration::firstOrNew(['slug' => $slug]);
        $row->fill(['name' => $def['name'], 'category' => $def['category'], 'description' => $def['description'], 'logo' => $def['logo'],
            'settings' => array_merge($row->settings ?? [], [Integrations::TEST => ['at' => now()->toIso8601String(), 'ok' => $result['ok'], 'message' => $result['message']]]),
            // a first test must not switch a card on; a card without its own switch stays applied as before
            'enabled' => $row->exists ? $row->enabled : empty($def['switch_on']), 'updated_by' => $request->user()->id])->save();
        Integrations::forget();
        AuditTrail::record('integrations', $row->id, 'update', null, ['event' => 'integration_tested', 'integration' => $slug, 'ok' => $result['ok']]);

        return back()->with($result['ok'] ? 'success' : 'warning', $name . ': ' . $result['message']);
    }
}
