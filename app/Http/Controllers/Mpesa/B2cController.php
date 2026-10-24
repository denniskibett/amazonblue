<?php

namespace App\Http\Controllers\Mpesa;

use App\Http\Controllers\Controller;
use App\Models\Loan;
use App\Services\Mpesa\B2cPayouts;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

/** M-Pesa B2C payouts (owner, 23 Oct 2026): staff ask, a second person approves, Safaricom calls back. */
class B2cController extends Controller
{
    public function __construct(private B2cPayouts $payouts)
    {
    }

    public function index(): View
    {
        return view('mpesa.payouts', [
            'rows' => DB::table('mpesa_transactions as m')->leftJoin('loans as l', 'l.id', '=', 'm.loan_id')->leftJoin('users as u', 'u.id', '=', 'l.user_id')
                ->where('m.type', 'b2c')->latest('m.id')->limit(300)->get(['m.*', 'u.name as borrower']),
            'enabled' => B2cPayouts::enabled(),
        ]);
    }

    public function store(Request $request, Loan $loan): RedirectResponse
    {
        $data = $request->validate(['phone' => ['required', 'string', 'max:20'], 'amount' => ['required', 'numeric', 'min:10']]);
        try {
            $this->payouts->request($loan, (float) $data['amount'], $data['phone'], $request->user());
        } catch (\DomainException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', 'Payout asked for. A second person approves it on Second sign-offs; then it is sent.');
    }

    public function result(Request $request, string $secret): JsonResponse
    {
        $this->guard($request, $secret);
        $this->payouts->result($request->json()->all());

        return response()->json(['ResultCode' => 0, 'ResultDesc' => 'Accepted']);
    }

    public function timeout(Request $request, string $secret): JsonResponse
    {
        $this->guard($request, $secret);
        $this->payouts->timeout($request->json()->all());

        return response()->json(['ResultCode' => 0, 'ResultDesc' => 'Accepted']);
    }

    /** Same checks as the C2B callbacks: the secret path segment and, when set, Safaricom's IPs. */
    private function guard(Request $request, string $secret): void
    {
        $expected = (string) config('lending.mpesa.callback_secret');
        abort_unless($expected !== '' && hash_equals($expected, $secret), 404);
        $ips = config('lending.mpesa.allowed_ips', []);
        abort_if($ips && ! in_array($request->ip(), $ips, true), 403);
    }
}
