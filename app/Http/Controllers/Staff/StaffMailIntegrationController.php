<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use App\Models\Integration;
use App\Services\AuditTrail;
use App\Services\StaffMail\MailServer;
use App\Support\Integrations;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * "Test connection" on the Staff mail integration card (owner, 16 Nov 2026): reaches the IMAP and SMTP servers with
 * the settings in force (TLS included) and keeps the result on the card, like Sharet Pay's test. Logs in to nothing.
 */
class StaffMailIntegrationController extends Controller
{
    public function test(Request $request, MailServer $server): RedirectResponse
    {
        $result = $server->probe();

        $def = Integrations::CATALOGUE['staff-mail'];
        $row = Integration::firstOrNew(['slug' => 'staff-mail']);
        $row->fill(['name' => $def['name'], 'category' => $def['category'], 'description' => $def['description'], 'logo' => $def['logo'],
            'settings' => array_merge($row->settings ?? [], [Integrations::TEST => ['at' => now()->toIso8601String(), 'ok' => $result['ok'], 'message' => $result['message']]]),
            'enabled' => $row->exists && $row->enabled, 'updated_by' => $request->user()->id])->save();
        Integrations::forget();
        AuditTrail::record('integrations', $row->id, 'update', null, ['event' => 'integration_tested', 'integration' => 'staff-mail', 'ok' => $result['ok']]);

        return back()->with($result['ok'] ? 'success' : 'warning', 'Staff mail: ' . $result['message']);
    }
}
