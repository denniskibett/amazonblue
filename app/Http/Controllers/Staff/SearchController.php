<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use App\Models\Loan;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Header search (owner, 3 Nov 2026): loans by number (547, LN-00547) and people by name, phone, email or ID
 * number, each only with the permission its page needs. Pages are matched in the browser from Support\NavMenu.
 * Brokers search pages only (their book is on Your loans).
 */
class SearchController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        $user = $request->user();
        $q = trim((string) $request->input('q', ''));
        if (mb_strlen($q) < 2 || ! $user->isStaff()) {
            return response()->json(['results' => []]);
        }
        $results = [];

        if ($user->can('view loans') && preg_match('/^(?:LN-?)?0*(\d{1,9})$/i', $q, $m)) {
            foreach (Loan::with('user:id,name')->whereKey((int) $m[1])->get() as $loan) {
                $results[] = ['group' => 'Loans', 'label' => 'Loan LN-' . str_pad((string) $loan->id, 5, '0', STR_PAD_LEFT),
                    'sub' => ($loan->user->name ?? '—') . ' · KES ' . number_format((float) $loan->amount) . ' · ' . $loan->status,
                    'url' => route('loans.show', $loan)];
            }
        }

        if ($user->can('view users')) {
            $like = '%' . addcslashes($q, '%_\\') . '%';
            $digits = preg_replace('/\D/', '', $q);
            $people = User::query()
                ->where(function ($w) use ($like, $digits) {
                    $w->where('name', 'like', $like)->orWhere('email', 'like', $like)->orWhere('id_number', 'like', $like);
                    if (strlen($digits) >= 4) {
                        $w->orWhere('phone', 'like', '%' . substr($digits, -9) . '%');
                    }
                })
                ->orderBy('name')->limit(6)->get(['id', 'name', 'email', 'phone']);
            foreach ($people as $p) {
                $results[] = ['group' => 'People', 'label' => $p->name, 'sub' => $p->phone ?: $p->email, 'url' => route('users.show', $p)];
            }
        }

        return response()->json(['results' => $results]);
    }
}
