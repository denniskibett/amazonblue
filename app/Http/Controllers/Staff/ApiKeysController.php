<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use App\Models\ApiKey;
use App\Services\AuditTrail;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/** API keys for /api/v1 (owner, 3 Nov 2026). A new or regenerated key is shown once; only its hash is kept. */
class ApiKeysController extends Controller
{
    public function index(): View
    {
        return view('staff.desk.api-keys', ['keys' => ApiKey::orderByDesc('id')->get(), 'base' => url('/api/v1')]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate(['name' => 'required|string|max:80']);
        [$key, $plain] = ApiKey::issue($data['name'], $request->user()->id);
        AuditTrail::record('api_keys', $key->id, 'create', null, ['name' => $key->name, 'last_four' => $key->last_four]);

        return back()->with('new_key', ['name' => $key->name, 'value' => $plain]);
    }

    public function regenerate(Request $request, ApiKey $apiKey): RedirectResponse
    {
        $plain = $apiKey->regenerate();
        AuditTrail::record('api_keys', $apiKey->id, 'update', null, ['event' => 'regenerated', 'last_four' => $apiKey->last_four]);

        return back()->with('new_key', ['name' => $apiKey->name, 'value' => $plain]);
    }

    public function toggle(ApiKey $apiKey): RedirectResponse
    {
        $apiKey->update(['status' => $apiKey->status === 'active' ? 'disabled' : 'active']);
        AuditTrail::record('api_keys', $apiKey->id, 'update', null, ['status' => $apiKey->status]);

        return back()->with('success', $apiKey->name . ' is now ' . $apiKey->status . '.');
    }

    public function destroy(ApiKey $apiKey): RedirectResponse
    {
        AuditTrail::record('api_keys', $apiKey->id, 'delete', ['name' => $apiKey->name, 'last_four' => $apiKey->last_four], null);
        $apiKey->delete();

        return back()->with('success', 'Key deleted. Anything still using it now gets 401.');
    }
}
