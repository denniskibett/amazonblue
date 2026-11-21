<?php

namespace App\Http\Controllers\Api\Mobile;

use App\Http\Controllers\Controller;
use App\Models\Partner;
use App\Services\PartnerPortfolio;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/** Partners in the app (owner, 21 Nov 2026): their own portfolio, read only. Money in and out stays on the website. */
class PartnerController extends Controller
{
    public function show(Request $request, PartnerPortfolio $portfolio): JsonResponse
    {
        $partner = Partner::where('user_id', $request->user()->id)->first();
        if (! $partner) {
            return response()->json(['linked' => false, 'message' => 'Your partner account is being set up. Our team will be in touch.']);
        }
        $s = $portfolio->summary($partner);

        return response()->json([
            'linked' => true,
            'name' => $partner->name,
            'available' => round((float) $s['available'], 2),
            'lent_out' => round((float) $s['lent_out'], 2),
            'earned' => round((float) $s['earned'], 2),
            'earned_this_month' => round((float) $s['earned_this_month'], 2),
            'committed' => round((float) $s['committed'], 2),
            'returned' => round((float) $s['returned'], 2),
            'wallets' => collect($s['wallets'])->map(fn ($w, $slug) => ['slug' => $slug, 'label' => $w['label'], 'balance' => round((float) $w['balance'], 2)])->values(),
            'recent' => $s['recent']->map(fn ($e) => [
                'date' => optional($e->transaction_date)->toDateString(),
                'type' => PartnerPortfolio::TYPES[$e->type] ?? $e->type,
                'amount' => round((float) $e->amount, 2),
            ])->values(),
            'website' => route('account.partner.home'),
        ]);
    }
}
