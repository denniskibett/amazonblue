<?php

namespace App\Http\Controllers\Aml;

use App\Http\Controllers\Controller;
use App\Models\Business;
use App\Models\User;
use App\Services\Aml\SanctionsList;
use App\Services\Aml\Screening;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * AML screening (owner, 23 Oct 2026): the sanctions lists, and screening a business (with its directors) or a
 * partner from their review page. Routes: routes/features/aml.php.
 */
class AmlController extends Controller
{
    public function lists(Request $request, SanctionsList $lists): View
    {
        // the names themselves, searchable and paged (owner, 3 Nov 2026)
        $q = trim((string) $request->query('q', ''));
        $kind = in_array($request->query('kind'), ['individual', 'entity'], true) ? $request->query('kind') : null;
        $list = (string) $request->query('list', '');
        $names = \Illuminate\Support\Facades\DB::table('sanctions_entries')
            ->when($q !== '', fn ($w) => $w->where(fn ($x) => $x->where('name', 'like', "%{$q}%")->orWhere('reference', 'like', "%{$q}%")->orWhere('country', 'like', "%{$q}%")))
            ->when($kind, fn ($w) => $w->where('kind', $kind))
            ->when($list !== '', fn ($w) => $w->where('list_name', $list))
            ->orderBy('name')->paginate(50, ['id', 'list_name', 'kind', 'name', 'dob', 'country', 'reference'])->withQueryString();

        return view('aml.lists', ['lists' => $lists->lists(), 'enabled' => Screening::enabled(), 'names' => $names, 'q' => $q, 'kind' => $kind, 'list' => $list]);
    }

    public function upload(Request $request, SanctionsList $lists): RedirectResponse
    {
        $data = $request->validate([
            'list_name' => ['required', 'string', 'max:60'],
            'file' => ['required', 'file', 'max:51200', 'mimes:xml,csv,txt'],
        ], [], ['list_name' => 'list name']);
        try {
            $n = $lists->import($request->file('file')->getRealPath(), $data['list_name'], $request->user());
        } catch (\DomainException $e) {
            return back()->withInput()->withErrors(['file' => $e->getMessage()]);
        }

        return back()->with('success', number_format($n) . ' names stored for "' . $data['list_name'] . '".');
    }

    public function remove(Request $request, SanctionsList $lists): RedirectResponse
    {
        $name = (string) $request->validate(['list_name' => ['required', 'string', 'max:60']])['list_name'];
        $lists->remove($name);

        return back()->with('success', '"' . $name . '" removed.');
    }

    public function screen(Request $request, Screening $screening, string $type, int $id): RedirectResponse
    {
        $subject = $this->subject($type, $id);
        $aml = $screening->screen($subject, $request->user());
        $n = count($aml['hits']);

        return back()->with($n ? 'error' : 'success', $n
            ? $n . ' name(s) look like a listed name. Check each one below.'
            : 'No listed names match. Tick the checklist to finish.');
    }

    public function decide(Request $request, Screening $screening, string $type, int $id): RedirectResponse
    {
        $subject = $this->subject($type, $id);
        $data = $request->validate([
            'aml_checks' => ['array'], 'aml_checks.*' => ['string'],
            'aml_note' => ['nullable', 'string', 'max:1000'],
        ]);
        try {
            $aml = $screening->decide($subject, $data['aml_checks'] ?? [], $data['aml_note'] ?? null, $request->user());
        } catch (\DomainException $e) {
            return back()->withErrors(['aml' => $e->getMessage()]);
        }

        return back()->with($aml['clear'] ? 'success' : 'error', $aml['clear'] ? 'AML screening is clear.' : 'Saved. Some boxes are not ticked, so it is not clear yet.');
    }

    private function subject(string $type, int $id): Business|User
    {
        return match ($type) {
            'business' => Business::with('members')->findOrFail($id),
            'user' => User::findOrFail($id),
            default => abort(404),
        };
    }
}
