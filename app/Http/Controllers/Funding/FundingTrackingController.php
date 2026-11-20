<?php

namespace App\Http\Controllers\Funding;

use App\Http\Controllers\Controller;
use App\Models\Funding\FundingEmail;
use App\Models\Funding\Suppression;
use App\Services\Funding\EmailEngagement;
use Illuminate\Http\Request;

/** Public endpoints behind a funding cold email: the tracking image, the tracked link, and the opt-out. */
class FundingTrackingController extends Controller
{
    private const GIF = 'R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7';

    public function open(Request $request, string $track)
    {
        EmailEngagement::record(FundingEmail::byTrack($track), 'open', $request);

        return response(base64_decode(self::GIF), 200, ['Content-Type' => 'image/gif', 'Cache-Control' => 'no-store, no-cache, must-revalidate', 'Pragma' => 'no-cache']);
    }

    public function click(Request $request, string $track)
    {
        $email = FundingEmail::byTrack($track);
        EmailEngagement::record($email, 'click', $request);
        if ($email && $email->prospect && $email->prospect->stage === 'contacted') {
            $email->prospect->setStage('interested', null, 'Opened the finance link in ' . $email->reference);
        }

        return redirect()->route('finance.choose', $email ? ['p' => $track] : []);
    }

    public function optOutForm(string $track)
    {
        $email = FundingEmail::byTrack($track);

        return response()->view('funding.optout', ['email' => $email, 'track' => $track, 'done' => $email && Suppression::has($email->to)])
            ->header('X-Robots-Tag', 'noindex');
    }

    /** One click: mail apps POST here (RFC 8058); the page's button too. */
    public function optOut(Request $request, string $track)
    {
        $email = FundingEmail::byTrack($track);
        if ($email) {
            Suppression::add($email->to, 'opt_out', $email->reference);
            $email->prospect?->activities()->create(['type' => 'note', 'title' => 'Opted out of email (' . $email->reference . ')', 'done_at' => now()]);
        }
        if ($request->has('List-Unsubscribe')) {
            return response('', 204);
        }

        return response()->view('funding.optout', ['email' => $email, 'track' => $track, 'done' => (bool) $email])->header('X-Robots-Tag', 'noindex');
    }
}
