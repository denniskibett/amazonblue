<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use App\Models\Integration;
use App\Services\AuditTrail;
use App\Support\Integrations;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * Integrations (owner, 3 Nov 2026): each connected service, its settings (secrets masked, never sent back to the
 * browser) and an on/off switch. Saved values override .env (Support\Integrations::apply). Changes are audited
 * by key name, never by value.
 */
class IntegrationsController extends Controller
{
    public function index(): View
    {
        $rows = Integration::all()->keyBy('slug');
        $cards = collect(Integrations::CATALOGUE)->map(function ($def, $slug) use ($rows) {
            $row = $rows->get($slug);
            $saved = $row?->settings ?? [];
            $fields = collect($def['fields'])->map(function ($f, $env) use ($slug, $saved) {
                $value = ($saved[$env] ?? null) !== null && ($saved[$env] ?? '') !== '' ? (string) $saved[$env] : Integrations::current($slug, $env);
                $secret = $f[2] === 'secret';

                return ['env' => $env, 'label' => $f[0], 'type' => $f[2], 'options' => $f[3] ?? [], 'secret' => $secret,
                    'value' => $secret ? null : $value, 'shown' => $secret ? Integrations::mask($value) : ($value ?? 'Not set'),
                    'set' => $value !== null && $value !== '', 'source' => array_key_exists($env, $saved) && $saved[$env] !== '' ? 'saved' : '.env'];
            })->values();

            return ['slug' => $slug, 'name' => $def['name'], 'category' => $def['category'], 'description' => $def['description'],
                'logo' => asset($def['logo']), 'managed' => ! empty($def['managed']) && \Illuminate\Support\Facades\Route::has($def['managed']) ? route($def['managed']) : null, 'enabled' => $row ? $row->enabled : true, 'stored' => (bool) $row, 'fields' => $fields,
                'configured' => $fields->where('set', true)->count(), 'updated' => $row?->updated_at?->diffForHumans()];
        })->values();

        return view('staff.desk.integrations', ['cards' => $cards, 'imported' => $rows->isNotEmpty()]);
    }

    public function update(Request $request, string $slug): RedirectResponse
    {
        $def = Integrations::CATALOGUE[$slug] ?? abort(404);
        abort_if(! empty($def['managed']), 403, $def['name'] . ' is changed on its own page.');
        $rules = [];
        foreach ($def['fields'] as $env => $f) {
            $rules["fields.$env"] = match ($f[2]) {
                'bool' => ['nullable', 'in:0,1'],
                'select' => ['nullable', Rule::in($f[3])],
                default => ['nullable', 'string', 'max:2000'],
            };
        }
        $data = $request->validate($rules)['fields'] ?? [];

        $row = Integration::firstOrNew(['slug' => $slug]);
        $settings = $row->settings ?? [];
        $changed = [];
        foreach ($def['fields'] as $env => $f) {
            $value = $data[$env] ?? null;
            // a blank secret keeps the saved one; "clear" is not offered for secrets on purpose
            if ($f[2] === 'secret' && ($value === null || $value === '')) {
                continue;
            }
            $value = $f[2] === 'bool' ? ((string) $value === '1' ? '1' : '0') : trim((string) $value);
            if (($settings[$env] ?? null) !== $value) {
                $settings[$env] = $value;
                $changed[] = $env;
            }
        }
        // a new Sharet key (e.g. test -> live) needs its own webhook: Test connection registers it again (9 Nov 2026)
        if ($slug === 'sharet' && in_array('SHARET_API_KEY', $changed, true) && ! in_array('SHARET_WEBHOOK_SECRET', $changed, true) && ! empty($settings['SHARET_WEBHOOK_SECRET'])) {
            unset($settings['SHARET_WEBHOOK_SECRET']);
            $changed[] = 'SHARET_WEBHOOK_SECRET';
        }
        $row->fill(['name' => $def['name'], 'category' => $def['category'], 'description' => $def['description'], 'logo' => $def['logo'],
            'settings' => $settings, 'enabled' => $row->exists ? $row->enabled : true, 'updated_by' => $request->user()->id])->save();
        Integrations::forget();
        if ($changed) {
            AuditTrail::record('integrations', $row->id, 'update', null, ['event' => 'settings_changed', 'integration' => $slug, 'keys' => $changed]);
        }

        return back()->with('success', $changed ? $def['name'] . ': saved ' . count($changed) . ' change(s).' : 'Nothing changed.');
    }

    public function toggle(Request $request, string $slug): RedirectResponse
    {
        $def = Integrations::CATALOGUE[$slug] ?? abort(404);
        $row = Integration::firstOrNew(['slug' => $slug]);
        $row->fill(['name' => $def['name'], 'category' => $def['category'], 'description' => $def['description'], 'logo' => $def['logo'],
            'settings' => $row->settings ?? [], 'enabled' => ! ($row->exists ? $row->enabled : true), 'updated_by' => $request->user()->id])->save();
        Integrations::forget();
        AuditTrail::record('integrations', $row->id, 'update', null, ['event' => $row->enabled ? 'integration_on' : 'integration_off', 'integration' => $slug]);

        return back()->with('success', $def['name'] . ($row->enabled ? ' switched on.' : ' switched off' . ($def['switch'] ? '.' : ': .env values are used.')));
    }
}
