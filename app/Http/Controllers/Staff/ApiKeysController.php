<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use App\Http\Controllers\MobileAppController;
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
        return view('staff.desk.api-keys', ['keys' => ApiKey::orderByDesc('id')->get(), 'base' => url('/api/v1'), 'apk' => MobileAppController::current()]);
    }

    /** Puts up a new test APK for /app (owner, 14 Nov 2026). The old file is replaced only once the new one is in place. */
    public function uploadApk(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'apk' => 'required|file|max:204800',
            'version' => ['required', 'string', 'max:20', 'regex:/^[0-9][0-9A-Za-z.+-]*$/'],
            'notes' => 'nullable|string|max:300',
        ]);
        $file = $request->file('apk');
        $head = (string) file_get_contents($file->getRealPath(), false, null, 0, 2);
        if (strtolower($file->getClientOriginalExtension()) !== 'apk' || $head !== 'PK') {
            return back()->withErrors(['apk' => 'That is not an APK file.'])->withInput();
        }

        $path = MobileAppController::path();
        @mkdir(dirname($path), 0775, true);
        $tmp = $path . '.uploading';
        $file->move(dirname($tmp), basename($tmp));
        rename($tmp, $path);
        $meta = ['version' => $data['version'], 'notes' => $data['notes'] ?: null, 'sha256' => hash_file('sha256', $path), 'size' => filesize($path),
            'uploaded_by' => $request->user()->id, 'uploaded_at' => now()->toIso8601String()];
        file_put_contents(MobileAppController::metaPath(), json_encode($meta, JSON_PRETTY_PRINT));
        AuditTrail::record('mobile_apk', 0, 'update', null, ['event' => 'uploaded', 'version' => $meta['version'], 'sha256' => $meta['sha256'], 'size' => $meta['size']]);

        return back()->with('success', 'Version ' . $meta['version'] . ' is up on the download page.');
    }

    public function removeApk(): RedirectResponse
    {
        $apk = MobileAppController::current();
        @unlink(MobileAppController::path());
        @unlink(MobileAppController::metaPath());
        AuditTrail::record('mobile_apk', 0, 'delete', $apk ? ['version' => $apk['version'], 'sha256' => $apk['sha256']] : null, null);

        return back()->with('success', 'The APK is down. The download page now says it is not up yet.');
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate(['name' => 'required|string|max:80', 'client' => 'nullable|in:' . implode(',', array_keys(ApiKey::CLIENTS))]);
        [$key, $plain] = ApiKey::issue($data['name'], $request->user()->id, $data['client'] ?? 'system');
        AuditTrail::record('api_keys', $key->id, 'create', null, ['name' => $key->name, 'client' => $key->client, 'last_four' => $key->last_four]);

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
