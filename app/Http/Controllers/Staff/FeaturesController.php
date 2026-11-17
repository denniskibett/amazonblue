<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

/**
 * Features and suggestions (owner, 17 Nov 2026), sysadmin only: the tracker of what the app does, what needs work and
 * what is missing (feature_items), and the suggestions staff, borrowers and partners send from every page.
 */
class FeaturesController extends Controller
{
    public const STATUS = ['working' => 'Working', 'improve' => 'Needs work', 'missing' => 'Missing'];
    public const PRIORITY = ['now' => 'Now', 'next' => 'Next', 'later' => 'Later'];
    public const REPLY = ['new' => 'New', 'planned' => 'Planned', 'done' => 'Done', 'declined' => 'Declined'];

    public function index(Request $request): View
    {
        $this->sysadmin($request);
        $features = DB::table('feature_items')->orderByRaw("FIELD(priority, 'now', 'next', 'later')")->orderBy('area')->orderBy('name')->get();
        $suggestions = DB::table('suggestions as s')->leftJoin('users as u', 'u.id', '=', 's.user_id')
            ->orderByRaw("FIELD(s.status, 'new', 'planned', 'done', 'declined')")->orderByDesc('s.id')
            ->get(['s.*', 'u.name as user_name', 'u.email as user_email']);
        $linked = DB::table('feature_items')->whereNotNull('suggestion_id')->pluck('suggestion_id')->all();

        return view('staff.features', compact('features', 'suggestions', 'linked'));
    }

    public function store(Request $request): RedirectResponse
    {
        $this->sysadmin($request);
        $data = $this->validFeature($request);
        DB::table('feature_items')->insert($data + ['suggestion_id' => $request->integer('suggestion_id') ?: null, 'updated_by' => $request->user()->id, 'created_at' => now(), 'updated_at' => now()]);
        if ($sid = $request->integer('suggestion_id')) {
            DB::table('suggestions')->where('id', $sid)->where('status', 'new')->update(['status' => 'planned', 'updated_at' => now()]);
        }

        return back()->with('success', 'Added ' . $data['name'] . ' to the tracker.');
    }

    public function update(Request $request, int $feature): RedirectResponse
    {
        $this->sysadmin($request);
        abort_unless(DB::table('feature_items')->where('id', $feature)->exists(), 404);
        DB::table('feature_items')->where('id', $feature)->update($this->validFeature($request) + ['updated_by' => $request->user()->id, 'updated_at' => now()]);

        return back()->with('success', 'Saved.');
    }

    public function reply(Request $request, int $suggestion): RedirectResponse
    {
        $this->sysadmin($request);
        $data = $request->validate(['status' => 'required|in:' . implode(',', array_keys(self::REPLY)), 'reply' => 'nullable|string|max:1000']);
        abort_unless(DB::table('suggestions')->where('id', $suggestion)->exists(), 404);
        DB::table('suggestions')->where('id', $suggestion)->update($data + ['updated_at' => now()]);

        return back()->with('success', 'Saved.');
    }

    private function validFeature(Request $request): array
    {
        return $request->validate([
            'area' => 'required|string|max:60', 'name' => 'required|string|max:160',
            'status' => 'required|in:' . implode(',', array_keys(self::STATUS)), 'priority' => 'required|in:' . implode(',', array_keys(self::PRIORITY)),
            'shortcoming' => 'nullable|string|max:2000',
        ]);
    }

    private function sysadmin(Request $request): void
    {
        abort_unless($request->user()?->hasRole('super-admin'), 403);
    }
}
