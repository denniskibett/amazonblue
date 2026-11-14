<?php

namespace App\Http\Controllers\Api\Mobile;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * The phone the app runs on, and what the borrower agreed to share (design: KYC 3). Model, OS and app version, plus an
 * approximate area (town or ward, never coordinates) at sign-in, application and payment. Staff only; never shown
 * to collection agencies. Never contacts, photos, call logs, SMS or precise location.
 */
class DeviceController extends Controller
{
    public const ITEMS = ['device' => 'Phone model and app version', 'area' => 'Approximate area at application and payment', 'push' => 'Reminders on this phone'];

    public function register(Request $request): JsonResponse
    {
        $data = $request->validate([
            'install_id' => ['required', 'string', 'max:64'],
            'platform' => ['required', 'in:android,ios'],
            'model' => ['nullable', 'string', 'max:80'],
            'os_version' => ['nullable', 'string', 'max:40'],
            'app_version' => ['nullable', 'string', 'max:20'],
            'push_token' => ['nullable', 'string', 'max:255'],
            'consents' => ['nullable', 'array'],
            'consents.*' => ['boolean'],
        ]);
        $user = $request->user();
        foreach (array_intersect_key($data['consents'] ?? [], self::ITEMS) as $item => $granted) {
            DB::table('data_consents')->updateOrInsert(['user_id' => $user->id, 'item' => $item],
                ['granted' => (bool) $granted, 'version' => 1, 'updated_at' => now(), 'created_at' => now()]);
        }
        $deviceOk = $this->granted($user->id, 'device');
        $now = now();
        $row = ['platform' => $data['platform'], 'last_seen_at' => $now, 'updated_at' => $now,
            'model' => $deviceOk ? ($data['model'] ?? null) : null, 'os_version' => $deviceOk ? ($data['os_version'] ?? null) : null,
            'app_version' => $data['app_version'] ?? null,
            'push_token' => $this->granted($user->id, 'push') ? ($data['push_token'] ?? null) : null];
        $exists = DB::table('mobile_devices')->where('user_id', $user->id)->where('install_id', $data['install_id'])->exists();
        DB::table('mobile_devices')->updateOrInsert(['user_id' => $user->id, 'install_id' => $data['install_id']],
            $exists ? $row : $row + ['first_seen_at' => $now, 'created_at' => $now]);

        return response()->json(['ok' => true, 'consents' => $this->consents($user->id)]);
    }

    public function consents(int $userId): array
    {
        $saved = DB::table('data_consents')->where('user_id', $userId)->pluck('granted', 'item');

        return collect(self::ITEMS)->map(fn ($label, $item) => ['item' => $item, 'label' => $label, 'granted' => (bool) ($saved[$item] ?? false)])->values()->all();
    }

    public function sightingEndpoint(Request $request): JsonResponse
    {
        $request->validate(['event' => ['required', 'in:signed_in,applied,paid'], 'area' => ['nullable', 'string', 'max:80']]);
        self::sighting($request, $request->input('event'));

        return response()->json(['ok' => true]);
    }

    /** Notes the phone at an event; the area only when the borrower agreed to share it. Silent when no phone is registered. */
    public static function sighting(Request $request, string $event): void
    {
        $install = $request->header('X-Install-Id');
        $user = $request->user();
        if (! $install || ! $user) {
            return;
        }
        $device = DB::table('mobile_devices')->where('user_id', $user->id)->where('install_id', $install)->first(['id']);
        if (! $device) {
            return;
        }
        $areaOk = (bool) DB::table('data_consents')->where('user_id', $user->id)->where('item', 'area')->value('granted');
        $area = $areaOk ? mb_substr(trim((string) $request->input('area', $request->header('X-Area', ''))), 0, 80) : '';
        DB::table('device_sightings')->insert(['mobile_device_id' => $device->id, 'event' => $event, 'area' => $area !== '' ? $area : null,
            'seen_at' => now()]);
        DB::table('mobile_devices')->where('id', $device->id)->update(['last_seen_at' => now()]);
    }

    private function granted(int $userId, string $item): bool
    {
        return (bool) DB::table('data_consents')->where('user_id', $userId)->where('item', $item)->value('granted');
    }
}
