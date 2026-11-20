<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use App\Models\Integration;
use App\Services\AuditTrail;
use App\Support\Integrations;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Route;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * Integrations (owner, 3 Nov 2026): each connected service, its settings (secrets masked, never sent back to the
 * browser) and an on/off switch. Saved values override .env (Support\Integrations::apply). Changes are audited
 * by key name, never by value. Sharet Pay's card (10 Nov 2026) also shows its connection state and last test, and
 * can be removed (its key forgotten).
 */
class IntegrationsController extends Controller
{
    public function index(): View
    {
        $rows = Integration::all()->keyBy('slug');
        $sysadmin = request()->user()?->hasRole('super-admin');
        $cards = collect(Integrations::CATALOGUE)->reject(fn ($def) => ! empty($def['sysadmin']) && ! $sysadmin)->map(function ($def, $slug) use ($rows) {
            $row = $rows->get($slug);
            $saved = $row?->settings ?? [];
            $fields = collect($def['fields'])->map(function ($f, $env) use ($slug, $saved, $def) {
                $value = ($saved[$env] ?? null) !== null && ($saved[$env] ?? '') !== '' ? (string) $saved[$env] : Integrations::current($slug, $env);
                $secret = $f[2] === 'secret';

                return ['env' => $env, 'label' => $f[0], 'type' => $f[2], 'options' => $f[3] ?? [], 'secret' => $secret,
                    'value' => $secret ? null : $value, 'shown' => $secret ? Integrations::mask($value) : ($value ?? 'Not set'),
                    'set' => $value !== null && $value !== '', 'source' => array_key_exists($env, $saved) && $saved[$env] !== '' ? 'saved' : '.env',
                    'help' => $def['help'][$env] ?? null, 'placeholder' => $def['placeholders'][$env] ?? null,
                    'connection' => in_array($env, $def['connection'] ?? [], true)];
            })->values();
            $test = $saved[Integrations::TEST] ?? null;

            return ['slug' => $slug, 'name' => $def['name'], 'category' => $def['category'], 'description' => $def['description'],
                'logo' => asset($def['logo']), 'managed' => ! empty($def['managed']) && Route::has($def['managed']) ? route($def['managed']) : null,
                'enabled' => $row ? Integrations::on($slug, $row->enabled, $saved) : empty($def['switch_on']), 'stored' => (bool) $row, 'fields' => $fields,
                'configured' => $fields->where('set', true)->count(), 'updated' => $row?->updated_at?->diffForHumans(),
                // Sharet Pay's card: connected = a key is saved; the last Test connection
                'switch_on' => ! empty($def['switch_on']), 'removable' => ! empty($def['removable']),
                'required' => array_key_first($def['fields']), 'intro' => $def['intro'] ?? null,
                'test_url' => ! empty($def['test']) && Route::has($def['test']) ? route($def['test']) : null,
                'connected' => filled($saved[array_key_first($def['fields'])] ?? null),
                // which kind of key is saved (its prefix only): live or test
                'key_mode' => $slug !== 'sharet' ? null : (str_starts_with((string) ($saved[array_key_first($def['fields'])] ?? ''), 'sk_live_') ? 'live key' : 'test key'),
                'test' => $test ? ['ok' => (bool) $test['ok'], 'message' => (string) $test['message'], 'when' => Carbon::parse($test['at'])->diffForHumans()] : null];
        })->values();

        return view('staff.desk.integrations', ['cards' => $cards, 'imported' => $rows->isNotEmpty()]);
    }

    public function update(Request $request, string $slug): RedirectResponse
    {
        $def = Integrations::CATALOGUE[$slug] ?? abort(404);
        abort_if(! empty($def['sysadmin']) && ! $request->user()->hasRole('super-admin'), 403, $def['name'] . ' is set by the system administrator.');
        abort_if(! empty($def['managed']), 403, $def['name'] . ' is changed on its own page.');
        $rules = [];
        foreach ($def['fields'] as $env => $f) {
            $rules["fields.$env"] = match ($f[2]) {
                'bool' => ['nullable', 'in:0,1'],
                'select' => ['nullable', Rule::in($f[3])],
                default => ['nullable', 'string', 'max:2000'],
            };
        }
        if ($slug === 'sharet') {
            $rules['fields.SHARET_API_KEY'][] = 'regex:/^sk_(test|live)_[A-Za-z0-9_]+$/';
            $rules['fields.SHARET_BASE_URL'] = ['nullable', 'url:https', 'max:255'];
            $rules['fields.SHARET_PAY_LINK_MINUTES'] = ['nullable', 'integer', 'min:15', 'max:43200'];
        }
        if ($slug === 'staff-mail') {
            $host = ['nullable', 'string', 'max:253', 'regex:/^[A-Za-z0-9.-]+$|^\[?[0-9a-fA-F:]+\]?$/'];
            $rules['fields.STAFF_MAIL_DOMAIN'] = ['nullable', 'string', 'max:253', 'regex:/^(?=.{3,253}$)([a-z0-9](?:[a-z0-9-]{0,61}[a-z0-9])?\.)+[a-z]{2,}$/i'];
            $rules['fields.STAFF_MAIL_IMAP_HOST'] = $host;
            $rules['fields.STAFF_MAIL_SMTP_HOST'] = $host;
            $rules['fields.STAFF_MAIL_IMAP_PORT'] = ['nullable', 'integer', 'min:1', 'max:65535'];
            $rules['fields.STAFF_MAIL_SMTP_PORT'] = ['nullable', 'integer', 'min:1', 'max:65535'];
            $rules['fields.STAFF_MAIL_IDLE_MINUTES'] = ['nullable', 'integer', 'min:5', 'max:1440'];
            $request->merge(['fields' => array_merge((array) $request->input('fields'), ['STAFF_MAIL_DOMAIN' => strtolower(ltrim(trim((string) $request->input('fields.STAFF_MAIL_DOMAIN')), '@'))])]);
        }
        $data = $request->validate($rules, ['fields.SHARET_API_KEY.regex' => 'A Sharet API key starts with sk_test_ or sk_live_.'])['fields'] ?? [];

        $row = Integration::firstOrNew(['slug' => $slug]);
        $settings = $row->settings ?? [];
        $enabled = $row->exists ? Integrations::on($slug, $row->enabled, $settings) : empty($def['switch_on']);
        $changed = [];
        foreach ($def['fields'] as $env => $f) {
            $value = $data[$env] ?? null;
            // a blank secret keeps the saved one; Remove (Sharet) forgets it
            if ($f[2] === 'secret' && ($value === null || $value === '')) {
                continue;
            }
            $value = $f[2] === 'bool' ? ((string) $value === '1' ? '1' : '0') : trim((string) $value);
            if (($settings[$env] ?? null) !== $value) {
                $settings[$env] = $value;
                $changed[] = $env;
            }
        }
        $message = $changed ? $def['name'] . ': saved ' . count($changed) . ' change(s).' : 'Nothing changed.';
        // a new Sharet key (e.g. test -> live) needs its own webhook and its own test, so Sharet Pay goes off until
        // Test connection has registered the webhook and someone switches it back on (9-10 Nov 2026)
        if ($slug === 'sharet' && in_array('SHARET_API_KEY', $changed, true)) {
            if (! in_array('SHARET_WEBHOOK_SECRET', $changed, true) && ! empty($settings['SHARET_WEBHOOK_SECRET'])) {
                unset($settings['SHARET_WEBHOOK_SECRET']);
                $changed[] = 'SHARET_WEBHOOK_SECRET';
            }
            unset($settings[Integrations::TEST]);
            $message = 'Sharet Pay: key saved' . ($enabled ? ' and Sharet Pay switched off' : '') . '. Press Test connection, then switch it on.';
            $enabled = false;
        }
        unset($settings[Integrations::SHARET_LEGACY_SWITCH]);
        $this->save($row, $def, $request, $settings, $enabled);
        if ($changed) {
            AuditTrail::record('integrations', $row->id, 'update', null, ['event' => 'settings_changed', 'integration' => $slug, 'keys' => $changed]);
        }

        return back()->with('success', $message);
    }

    public function toggle(Request $request, string $slug): RedirectResponse
    {
        $def = Integrations::CATALOGUE[$slug] ?? abort(404);
        abort_if(! empty($def['sysadmin']) && ! $request->user()->hasRole('super-admin'), 403, $def['name'] . ' is set by the system administrator.');
        abort_if(! empty($def['managed']), 403, $def['name'] . ' is changed on its own page.');
        $row = Integration::firstOrNew(['slug' => $slug]);
        $settings = $row->settings ?? [];
        $on = ! ($row->exists ? Integrations::on($slug, $row->enabled, $settings) : empty($def['switch_on']));
        if ($on && ! empty($def['switch_on']) && blank($settings[array_key_first($def['fields'])] ?? null)) {
            return back()->with('error', 'Connect ' . $def['name'] . ' first: save its ' . ($slug === 'sharet' ? 'API key.' : strtolower($def['fields'][array_key_first($def['fields'])][0]) . '.'));
        }
        unset($settings[Integrations::SHARET_LEGACY_SWITCH]);
        $this->save($row, $def, $request, $settings, $on);
        AuditTrail::record('integrations', $row->id, 'update', null, ['event' => $on ? 'integration_on' : 'integration_off', 'integration' => $slug]);

        return back()->with('success', $def['name'] . ($on ? ' switched on.' : ' switched off' . ($def['switch'] ? '.' : ': .env values are used.')));
    }

    /** Remove (Sharet Pay): forgets the key, the webhook secret, the options and the last test, and switches it off. */
    public function destroy(Request $request, string $slug): RedirectResponse
    {
        $def = Integrations::CATALOGUE[$slug] ?? abort(404);
        abort_if(! empty($def['sysadmin']) && ! $request->user()->hasRole('super-admin'), 403, $def['name'] . ' is set by the system administrator.');
        abort_unless(! empty($def['removable']), 404);
        $row = Integration::where('slug', $slug)->first();
        if (! $row) {
            return back()->with('success', $def['name'] . ' was not connected.');
        }
        $keys = array_values(array_intersect(array_keys($row->settings ?? []), array_keys($def['fields'])));
        $this->save($row, $def, $request, [], false);
        AuditTrail::record('integrations', $row->id, 'update', null, ['event' => 'integration_removed', 'integration' => $slug, 'keys' => $keys]);

        return back()->with('success', $def['name'] . ' removed: its key and settings were deleted.');
    }

    private function save(Integration $row, array $def, Request $request, array $settings, bool $enabled): void
    {
        $row->fill(['name' => $def['name'], 'category' => $def['category'], 'description' => $def['description'], 'logo' => $def['logo'],
            'settings' => $settings, 'enabled' => $enabled, 'updated_by' => $request->user()->id])->save();
        Integrations::forget();
    }
}
