<?php

namespace App\Http\Controllers;

use App\Services\Mpesa\PaymentRecorder;
use App\Support\StaffAlarm;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Safaricom Daraja callbacks (Stage 4 Phase 4): /mpesa/{secret}/c2b/validation, /c2b/confirmation, /stk.
 * No session or CSRF; a wrong secret is a 404, an IP outside MPESA_ALLOWED_IPS (when set) is a 403.
 *
 * A confirmed payment is always stored (nothing Safaricom reports is lost): when it can't be saved, the answer is
 * non-zero so Safaricom sends it again, and admins are alerted. With MPESA_ENABLED it is matched
 * and recorded straight away; with it off it waits in the staff payments queue.
 */
class MpesaCallbackController extends Controller
{
    public function validation(Request $request, string $secret): JsonResponse
    {
        $this->guard($request, $secret);

        // payments are never refused at the till; matching happens on confirmation
        return response()->json(['ResultCode' => 0, 'ResultDesc' => 'Accepted']);
    }

    public function confirmation(Request $request, string $secret, PaymentRecorder $recorder): JsonResponse
    {
        $this->guard($request, $secret);
        try {
            $p = $request->json()->all() ?: $request->all();
            if (config('lending.mpesa.enabled')) {
                $recorder->receiveC2b($p);
            } else {
                $this->storeOnly($p);
            }
        } catch (\Throwable $e) {
            $transId = strtoupper(trim((string) ($p['TransID'] ?? $request->input('TransID', ''))));
            $stored = $transId !== '' && rescue(fn () => \App\Models\MpesaTransaction::where('trans_id', $transId)->exists(), false, false);
            StaffAlarm::raise('mpesa-c2b', 'M-Pesa payment not ' . ($stored ? 'matched' : 'stored'),
                'An M-Pesa payment (' . ($transId ?: 'no receipt') . ') came in but ' . ($stored
                    ? 'could not be matched to a loan. It waits in the payments queue.'
                    : 'could not be saved. Safaricom was asked to send it again; check the M-Pesa statement.'),
                ['trans_id' => $transId, 'error' => $e->getMessage()]);
            if (! $stored) {
                // not saved: a non-zero answer asks Safaricom to send it again
                return response()->json(['ResultCode' => 1, 'ResultDesc' => 'Not stored, please retry']);
            }
        }

        return response()->json(['ResultCode' => 0, 'ResultDesc' => 'Accepted']);
    }

    public function stk(Request $request, string $secret, PaymentRecorder $recorder): JsonResponse
    {
        $this->guard($request, $secret);
        try {
            $recorder->receiveStk($request->json()->all() ?: $request->all());
        } catch (\Throwable $e) {
            StaffAlarm::raise('mpesa-stk', 'M-Pesa STK payment not recorded',
                'A borrower\'s STK payment result came in but could not be recorded. Check the M-Pesa statement and the payments queue.',
                ['error' => $e->getMessage()]);

            return response()->json(['ResultCode' => 1, 'ResultDesc' => 'Not stored, please retry']);
        }

        return response()->json(['ResultCode' => 0, 'ResultDesc' => 'Accepted']);
    }

    private function guard(Request $request, string $secret): void
    {
        $expected = (string) config('lending.mpesa.callback_secret');
        abort_unless($expected !== '' && hash_equals($expected, $secret), 404);
        $ips = config('lending.mpesa.allowed_ips', []);
        abort_if($ips && ! in_array($request->ip(), $ips, true), 403);
    }

    private function storeOnly(array $p): void
    {
        $id = strtoupper(trim((string) ($p['TransID'] ?? '')));
        if ($id !== '' && ! \App\Models\MpesaTransaction::where('trans_id', $id)->exists()) {
            \App\Models\MpesaTransaction::create([
                'type' => 'c2b', 'trans_id' => $id, 'msisdn' => (string) ($p['MSISDN'] ?? ''), 'amount' => (float) ($p['TransAmount'] ?? 0),
                'bill_ref' => trim((string) ($p['BillRefNumber'] ?? '')), 'shortcode' => (string) ($p['BusinessShortCode'] ?? ''),
                'status' => 'unmatched', 'note' => 'Automatic recording is off (MPESA_ENABLED): assign it in the payments queue',
            ]);
        }
    }
}
