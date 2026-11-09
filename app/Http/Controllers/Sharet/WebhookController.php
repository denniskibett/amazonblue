<?php

namespace App\Http\Controllers\Sharet;

use App\Http\Controllers\Controller;
use App\Models\SharetPayout;
use App\Services\Sharet\SharetClient;
use App\Services\Sharet\SharetCollections;
use App\Services\Sharet\SharetPayouts;
use Illuminate\Database\QueryException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * POST /webhooks/sharet (9 Nov 2026): events from Sharet, signed "Sharet-Signature" with the secret saved by
 * Integrations › Sharet Pay › Test connection. Each event id is handled once; applying is idempotent anyway.
 */
class WebhookController extends Controller
{
    public function __invoke(Request $request, SharetCollections $collections, SharetPayouts $payouts): JsonResponse
    {
        $raw = $request->getContent();
        if (! config('sharet.enabled') || ! SharetClient::verifySignature($request->header('Sharet-Signature'), $raw,
            (string) config('sharet.webhook_secret'), (int) config('sharet.signature_tolerance', 300))) {
            Log::warning('Sharet webhook rejected: bad or missing signature', ['ip' => $request->ip()]);

            return response()->json(['error' => 'invalid_signature'], 400);
        }

        $event = json_decode($raw, true) ?: [];
        $id = (string) ($event['id'] ?? '');
        $type = (string) ($event['type'] ?? '');
        $data = (array) ($event['data'] ?? []);
        if ($id === '' || $type === '') {
            return response()->json(['error' => 'invalid_event'], 400);
        }

        try {
            DB::table('sharet_webhook_events')->insert(['event_id' => mb_substr($id, 0, 80), 'type' => mb_substr($type, 0, 60),
                'payload' => json_encode($event), 'created_at' => now(), 'updated_at' => now()]);
        } catch (QueryException) {
            $done = DB::table('sharet_webhook_events')->where('event_id', $id)->value('processed_at');
            if ($done) {
                return response()->json(['received' => true, 'duplicate' => true]);
            }
            // stored before but not finished (an error last time): handle it again; applying is idempotent
        }

        try {
            match (true) {
                str_starts_with($type, 'payment_request.') => $collections->apply($data),
                str_starts_with($type, 'payment.') => ($row = SharetPayout::where('sharet_payment_id', (string) ($data['id'] ?? ''))->first())
                    ? $payouts->record($row, $data) : null,
                default => null,
            };
        } catch (\Throwable $e) {
            \App\Support\StaffAlarm::raise('sharet-webhook', 'Sharet event not recorded',
                'Sharet reported a payment that could not be recorded. Sharet will send it again; sharet:sync also checks.', ['event' => $id, 'error' => $e->getMessage()]);

            return response()->json(['error' => 'not_recorded'], 500);
        }

        DB::table('sharet_webhook_events')->where('event_id', $id)->update(['processed_at' => now(), 'updated_at' => now()]);

        return response()->json(['received' => true]);
    }
}
