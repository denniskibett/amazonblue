<?php

namespace App\Http\Controllers\Concerns;

use App\Models\Business;
use App\Models\BusinessDocument;
use App\Models\BusinessMember;
use App\Models\User;
use App\Support\Banks;
use App\Support\CustomerNotifier;
use App\Support\PrivateFiles;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * Business KYC for borrowers (used by HomeController, routes account.business.*).
 *
 * Only a borrower whose individual KYC is approved can add a business. Steps, each saved as it goes:
 * start -> 1 business details -> 2 directors & owners -> 3 company documents -> 4 financials -> 5 review & submit.
 * The same pattern as individual KYC: a submitted business is locked while staff review it, an approved one
 * stays locked, a rejected one reopens until it is submitted again. Staff side: BusinessReviewController.
 */
trait BusinessOnboarding
{
    // ============ ENTRY ============

    public function businessIndex(Request $request): View|RedirectResponse
    {
        $businesses = $request->user()->businesses()->latest('id')->get();
        if ($businesses->isEmpty()) {
            return redirect()->route('account.business.start');
        }

        return view('home.business.index', [
            'businesses' => $businesses,
            'eligible' => $this->businessEligible($request->user()),
        ]);
    }

    public function businessStart(Request $request): View
    {
        $user = $request->user();

        return view('home.business.start', [
            'user' => $user,
            'eligible' => $this->businessEligible($user),
            'draft' => $user->businesses()->whereIn('status', [Business::STATUS_DRAFT, Business::STATUS_REJECTED])->latest('id')->first(),
        ]);
    }

    public function businessShow(Request $request, Business $business): View
    {
        $this->businessOwned($request, $business);
        $business->load(['members', 'documents']);

        return view('home.business.show', [
            'business' => $business,
            'sections' => $this->businessSections($business),
        ]);
    }

    // ============ 1. BUSINESS DETAILS ============

    public function businessCreate(Request $request): View|RedirectResponse
    {
        if ($blocked = $this->businessNotEligible($request->user())) {
            return $blocked;
        }

        return $this->businessDetailsView($request->user(), new Business(['country' => 'Kenya']));
    }

    public function businessStore(Request $request): RedirectResponse
    {
        $user = $request->user();
        if ($blocked = $this->businessNotEligible($user)) {
            return $blocked;
        }
        $data = $this->businessValidateDetails($request, true);

        $business = DB::transaction(function () use ($user, $data) {
            $business = Business::create(collect($data)->except('your_role')->all() + [
                'owner_user_id' => $user->id,
                'status' => Business::STATUS_DRAFT,
            ]);
            // the person applying is always on the business, already verified by their own KYC
            $business->members()->create([
                'user_id' => $user->id,
                'full_name' => $user->name,
                'role' => $data['your_role'],
                'id_number' => $user->id_number,
                'kra_pin' => $user->kra_pin,
                'phone' => $user->phone,
                'email' => $user->email,
                'ownership_pct' => $data['registration_type'] === 'sole_proprietorship' ? 100 : 0,
                'is_signatory' => true,
                'verified_at' => now(),
            ]);

            return $business;
        });

        return redirect()->route('account.business.members', $business)
            ->with('status', 'Business details saved.');
    }

    public function businessDetails(Request $request, Business $business): View
    {
        $this->businessOwned($request, $business);

        return $this->businessDetailsView($request->user(), $business);
    }

    public function businessUpdateDetails(Request $request, Business $business): RedirectResponse
    {
        $this->businessOwned($request, $business);
        if ($locked = $this->businessLocked($business)) {
            return $locked;
        }
        $data = $this->businessValidateDetails($request, false);
        $business->update($data);

        return redirect()->route('account.business.members', $business);
    }

    // ============ 2. DIRECTORS & OWNERS ============

    public function businessMembers(Request $request, Business $business): View
    {
        $this->businessOwned($request, $business);
        $business->load('members');
        $editing = $request->integer('edit') ? $business->members->firstWhere('id', $request->integer('edit')) : null;

        return view('home.business.members', [
            'business' => $business,
            'members' => $business->members,
            'editing' => $editing,
            'total' => $business->ownershipTotal(),
            'step' => $this->businessSections($business)['members'],
        ]);
    }

    public function businessStoreMember(Request $request, Business $business): RedirectResponse
    {
        $this->businessOwned($request, $business);
        if ($locked = $this->businessLocked($business)) {
            return $locked;
        }
        $member = $request->filled('member_id') ? $business->members()->whereKey($request->integer('member_id'))->firstOrFail() : null;
        $isApplicant = $member && $member->user_id === $business->owner_user_id;

        $data = $request->validate([
            'full_name' => [Rule::requiredIf(! $isApplicant), 'nullable', 'string', 'max:191'],
            'role' => ['required', Rule::in(array_keys(BusinessMember::ROLES))],
            'id_number' => [Rule::requiredIf(! $isApplicant), 'nullable', 'string', 'max:50', 'regex:/^[A-Za-z0-9]+$/'],
            'kra_pin' => ['nullable', 'string', 'regex:/^[AP]\d{9}[A-Z]$/i'],
            'phone' => ['nullable', 'regex:/^(?:\+?254|0)[17]\d{8}$/'],
            'email' => ['nullable', 'email', 'max:191'],
            'ownership_pct' => ['required', 'numeric', 'min:0', 'max:100'],
            'is_signatory' => ['nullable', 'boolean'],
        ], [
            'full_name.required' => 'Enter their full name.',
            'id_number.required' => 'Enter their ID or passport number.',
            'id_number.regex' => 'Use letters and numbers only for the ID or passport number.',
            'kra_pin.regex' => 'A KRA PIN looks like A123456789B.',
            'phone.regex' => 'Enter a Kenyan mobile number, e.g. 0712 345 678.',
            'ownership_pct.max' => 'Ownership can\'t be more than 100%.',
        ]);

        // nobody twice, and shares can't add up to more than the whole business
        $others = $business->members()->when($member, fn ($q) => $q->whereKeyNot($member->id))->get();
        if (! $isApplicant && $others->contains(fn ($m) => strcasecmp((string) $m->id_number, (string) $data['id_number']) === 0)) {
            return back()->withInput()->withErrors(['id_number' => 'This person is already on the business.']);
        }
        $total = round((float) $others->sum('ownership_pct') + (float) $data['ownership_pct'], 2);
        if ($total > 100) {
            return back()->withInput()->withErrors(['ownership_pct' => 'Ownership would add up to ' . rtrim(rtrim(number_format($total, 2), '0'), '.') . '%. The total can\'t be more than 100%.']);
        }

        $values = [
            'role' => $data['role'],
            'ownership_pct' => $data['ownership_pct'],
            'is_signatory' => $request->boolean('is_signatory'),
        ];
        if (! $isApplicant) {
            $linked = $this->businessLinkedUser($data['id_number']);
            $values += [
                'full_name' => trim($data['full_name']),
                'id_number' => strtoupper($data['id_number']),
                'kra_pin' => isset($data['kra_pin']) ? strtoupper($data['kra_pin']) : null,
                'phone' => $data['phone'] ?? null,
                'email' => $data['email'] ?? null,
                // someone who passed individual KYC with this ID number is linked (and can see the business)
                'user_id' => $linked?->id,
                'verified_at' => $linked ? ($member?->verified_at ?? now()) : null,
            ];
        }
        $member ? $member->update($values) : $business->members()->create($values);

        return redirect()->route('account.business.members', $business)
            ->with('status', $member ? 'Saved.' : 'Person added.');
    }

    public function businessRemoveMember(Request $request, Business $business, BusinessMember $member): RedirectResponse
    {
        $this->businessOwned($request, $business);
        abort_unless($member->business_id === $business->id, 404);
        if ($locked = $this->businessLocked($business)) {
            return $locked;
        }
        if ($member->user_id === $business->owner_user_id) {
            return back()->withErrors(['members' => 'You applied for the business, so you stay on it.']);
        }
        $member->delete();

        return redirect()->route('account.business.members', $business)->with('status', $member->full_name . ' removed.');
    }

    public function businessMembersNext(Request $request, Business $business): RedirectResponse
    {
        $this->businessOwned($request, $business);
        $step = $this->businessSections($business->load('members'))['members'];
        if (! $step['complete']) {
            return back()->withErrors(['members' => $step['problem']]);
        }

        return redirect()->route('account.business.documents', $business);
    }

    // ============ 3. COMPANY DOCUMENTS ============

    public function businessDocuments(Request $request, Business $business): View
    {
        $this->businessOwned($request, $business);
        $business->load('documents');

        return view('home.business.documents', [
            'business' => $business,
            'documents' => $business->documents->keyBy('type'),
            'required' => $business->requiredDocumentTypes(),
        ]);
    }

    public function businessStoreDocuments(Request $request, Business $business): RedirectResponse
    {
        $this->businessOwned($request, $business);
        if ($locked = $this->businessLocked($business)) {
            return $locked;
        }
        $types = array_keys(BusinessDocument::TYPES);
        $request->validate(
            collect($types)->mapWithKeys(fn ($t) => ["docs.$t" => ['nullable', 'file', 'mimes:jpg,jpeg,png,webp,heic,pdf', 'max:10240']])->all(),
            ['docs.*.mimes' => 'Use a JPG, PNG or PDF file.', 'docs.*.max' => 'Each file can be up to 10 MB.', 'docs.*.uploaded' => 'That file did not upload. Try again (up to 10 MB).']
        );

        $user = $request->user();
        foreach ($types as $type) {
            $file = $request->file("docs.$type");
            if (! $file) {
                continue;
            }
            $path = PrivateFiles::storeUpload($file, 'business_documents', $user);
            // one file per type: a new upload replaces the old one
            foreach ($business->documents()->where('type', $type)->get() as $old) {
                PrivateFiles::delete($old->path);
                $old->delete();
            }
            $business->documents()->create([
                'type' => $type,
                'path' => $path,
                'original_name' => mb_substr($file->getClientOriginalName(), 0, 255),
                'mime' => $file->getMimeType(),
                'size' => $file->getSize(),
                'uploaded_by' => $user->id,
            ]);
        }

        $missing = $business->load('documents')->missingDocumentTypes();
        if ($missing) {
            return redirect()->route('account.business.documents', $business)->withErrors(
                collect($missing)->mapWithKeys(fn ($t) => ["docs.$t" => 'Upload the ' . preg_replace_callback('/^[A-Z](?![A-Z0-9])/', fn ($m) => strtolower($m[0]), BusinessDocument::TYPES[$t][0]) . '.'])->all()
                + ['documents' => count($missing) === 1 ? 'One document is still missing.' : count($missing) . ' documents are still missing.']
            );
        }

        return redirect()->route('account.business.financials', $business);
    }

    // ============ 4. FINANCIALS ============

    public function businessFinancials(Request $request, Business $business): View
    {
        $this->businessOwned($request, $business);

        return view('home.business.financials', ['business' => $business, 'banks' => Banks::names()]);
    }

    public function businessStoreFinancials(Request $request, Business $business): RedirectResponse
    {
        $this->businessOwned($request, $business);
        if ($locked = $this->businessLocked($business)) {
            return $locked;
        }
        $data = $request->validate([
            'monthly_revenue' => ['required', 'numeric', 'min:0', 'max:9999999999999'],
            'employees' => ['required', 'integer', 'min:0', 'max:1000000'],
            'bank_name' => ['required', 'string', 'max:100'],
            'bank_other' => [Rule::requiredIf($request->input('bank_name') === Banks::OTHER), 'nullable', 'string', 'max:100'],
            'bank_account' => ['required', 'string', 'max:50', 'regex:/^[0-9A-Za-z\- ]+$/'],
        ], [
            'monthly_revenue.required' => 'Enter the average monthly revenue (sales).',
            'employees.required' => 'Enter the number of employees (0 if it is just you).',
            'bank_name.required' => 'Choose the business\'s bank.',
            'bank_other.required' => 'Type the name of the bank.',
            'bank_account.required' => 'Enter the business bank account number.',
            'bank_account.regex' => 'Use numbers (and letters if your bank uses them) for the account number.',
        ]);

        $business->update([
            'monthly_revenue' => $data['monthly_revenue'],
            'employees' => $data['employees'],
            'bank_name' => $data['bank_name'] === Banks::OTHER ? trim($data['bank_other']) : $data['bank_name'],
            'bank_account' => preg_replace('/\s+/', '', $data['bank_account']),
        ]);

        return redirect()->route('account.business.review', $business);
    }

    // ============ 5. REVIEW & SUBMIT ============

    public function businessReview(Request $request, Business $business): View
    {
        $this->businessOwned($request, $business);

        return view('home.business.review', ['business' => $business, 'sections' => $this->businessSections($business->load(['members', 'documents']))]);
    }

    public function businessSubmit(Request $request, Business $business): RedirectResponse
    {
        $this->businessOwned($request, $business);
        if ($locked = $this->businessLocked($business)) {
            return $locked;
        }
        $request->validate([
            'confirm_true' => ['accepted'],
            'confirm_authorised' => ['accepted'],
            'confirm_checks' => ['accepted'],
        ], ['*.accepted' => 'Tick all three declarations to submit.']);

        $incomplete = collect($this->businessSections($business->load(['members', 'documents'])))->firstWhere('complete', false);
        if ($incomplete) {
            return redirect($incomplete['href'])->withErrors(['business' => $incomplete['title'] . ' is not finished yet.']);
        }

        // a resubmission starts a fresh review: the previous decision stays in the audit log
        $business->update([
            'status' => Business::STATUS_SUBMITTED,
            'submitted_at' => now(),
            'reviewed_by' => null,
            'reviewed_at' => null,
            'checks' => null,
        ]);
        CustomerNotifier::businessSubmitted($business);

        return redirect()->route('account.business.submitted', $business);
    }

    public function businessSubmitted(Request $request, Business $business): View|RedirectResponse
    {
        $this->businessOwned($request, $business);
        if (! $business->submitted_at) {
            return redirect()->route('account.business.review', $business);
        }

        return view('home.business.submitted', ['business' => $business, 'firstName' => strtok($request->user()->name, ' ')]);
    }

    // ============ HELPERS ============

    /**
     * Each step: done or not, a one-line summary for the review page, and what is wrong when not done.
     */
    private function businessSections(Business $business): array
    {
        $members = $business->members;
        $total = $business->ownershipTotal();
        $membersProblem = match (true) {
            $members->isEmpty() => 'Add the people on the business.',
            $total <= 0 => 'Enter how much of the business each person owns.',
            $total > 100 => 'Ownership adds up to more than 100%.',
            ! $members->contains('is_signatory', true) => 'Mark at least one person who can sign for the business.',
            $business->isLimitedCompany() && ! $members->contains('role', 'director') && ! $members->contains('role', 'owner') => 'A limited company needs at least one director.',
            default => null,
        };
        $missing = $business->exists ? $business->missingDocumentTypes() : $business->requiredDocumentTypes();
        $fmt = fn ($n) => rtrim(rtrim(number_format((float) $n, 2), '0'), '.');

        $detailsDone = $business->exists && $business->legal_name && $business->registration_number && $business->registration_type
            && $business->kra_pin && $business->date_registered && $business->address_line_1 && $business->phone;
        $financialsDone = $business->monthly_revenue !== null && $business->employees !== null && $business->bank_name && $business->bank_account;

        return [
            'details' => [
                'title' => 'Business details',
                'complete' => (bool) $detailsDone,
                'summary' => $business->exists ? trim(($business->legal_name ?? '') . ' · ' . ($business->registration_number ?? '—') . ' · ' . (Business::REGISTRATION_TYPES[$business->registration_type] ?? '—'), ' ·') : 'Not started',
                'href' => $business->exists ? route('account.business.details', $business) : route('account.business.create'),
            ],
            'members' => [
                'title' => 'Directors & owners',
                'complete' => $business->exists && $membersProblem === null,
                'problem' => $membersProblem,
                'summary' => $members->isEmpty() ? 'No one added yet' : $members->count() . ' ' . str($members->count() === 1 ? 'person' : 'people') . ' · ' . $fmt($total) . '% ownership listed',
                'href' => $business->exists ? route('account.business.members', $business) : route('account.business.create'),
            ],
            'documents' => [
                'title' => 'Company documents',
                'complete' => $business->exists && ! $missing,
                'summary' => $missing ? count($missing) . ' required ' . (count($missing) === 1 ? 'document' : 'documents') . ' missing' : $business->documents->count() . ' uploaded',
                'href' => $business->exists ? route('account.business.documents', $business) : route('account.business.create'),
            ],
            'financials' => [
                'title' => 'Financials',
                'complete' => $business->exists && $financialsDone,
                'summary' => $financialsDone ? 'KES ' . number_format((float) $business->monthly_revenue) . ' a month · ' . $business->employees . ' ' . ($business->employees === 1 ? 'employee' : 'employees') . ' · ' . $business->bank_name : 'Revenue, employees and bank',
                'href' => $business->exists ? route('account.business.financials', $business) : route('account.business.create'),
            ],
        ];
    }

    private function businessDetailsView(User $user, Business $business): View
    {
        return view('home.business.details', [
            'user' => $user,
            'business' => $business,
            'counties' => self::COUNTIES,
            'yourRole' => $business->exists ? $business->members()->where('user_id', $user->id)->value('role') : null,
        ]);
    }

    private function businessValidateDetails(Request $request, bool $creating): array
    {
        $request->merge([
            'kra_pin' => strtoupper(trim((string) $request->input('kra_pin'))),
            'registration_number' => strtoupper(trim((string) $request->input('registration_number'))),
            'website' => $request->filled('website') && ! preg_match('#^https?://#i', $request->input('website')) ? 'https://' . trim($request->input('website')) : $request->input('website'),
        ]);

        $data = $request->validate([
            'registration_type' => ['required', Rule::in(array_keys(Business::REGISTRATION_TYPES))],
            'legal_name' => ['required', 'string', 'max:191'],
            'trading_name' => ['nullable', 'string', 'max:191'],
            'registration_number' => ['required', 'string', 'max:50', 'regex:/^[A-Z0-9][A-Z0-9\-\/ ]*$/'],
            'kra_pin' => ['required', 'regex:/^[AP]\d{9}[A-Z]$/'],
            'industry' => ['required', Rule::in(Business::INDUSTRIES)],
            'date_registered' => ['required', 'date', 'before_or_equal:today', 'after:1900-01-01'],
            'address_line_1' => ['required', 'string', 'max:191'],
            'address_line_2' => ['nullable', 'string', 'max:191'],
            'city' => ['required', 'string', 'max:100'],
            'county' => ['required', Rule::in(self::COUNTIES)],
            'postal_code' => ['nullable', 'string', 'max:20'],
            'phone' => ['required', 'regex:/^(?:\+?254|0)[17]\d{8}$/'],
            'email' => ['nullable', 'email', 'max:191'],
            'website' => ['nullable', 'url', 'max:191'],
            'your_role' => [Rule::requiredIf($creating), 'nullable', Rule::in(array_keys(BusinessMember::ROLES))],
        ], [
            'registration_type.required' => 'Choose how the business is registered.',
            'legal_name.required' => 'Enter the registered name.',
            'registration_number.required' => 'Enter the registration number.',
            'registration_number.regex' => 'Use letters, numbers, dashes or slashes, as on the certificate.',
            'kra_pin.required' => 'Enter the business KRA PIN.',
            'kra_pin.regex' => 'A KRA PIN looks like P051234567X.',
            'industry.required' => 'Choose the industry.',
            'date_registered.required' => 'Enter the date it was registered.',
            'date_registered.before_or_equal' => 'The registration date can\'t be in the future.',
            'address_line_1.required' => 'Enter the business address.',
            'city.required' => 'Enter the town or city.',
            'phone.required' => 'Enter the business phone number.',
            'phone.regex' => 'Enter a Kenyan mobile number, e.g. 0712 345 678.',
            'website.url' => 'Enter a web address like www.example.co.ke.',
            'your_role.required' => 'Choose your role in the business.',
        ]);

        return $creating ? $data : collect($data)->except('your_role')->all();
    }

    /** Only the borrower who added the business works on it here; anyone else gets a 404. */
    private function businessOwned(Request $request, Business $business): void
    {
        abort_unless($business->owner_user_id === $request->user()->id, 404);
    }

    private function businessEligible(User $user): bool
    {
        return $user->kyc_status === 'approved';
    }

    private function businessNotEligible(User $user): ?RedirectResponse
    {
        return $this->businessEligible($user) ? null : redirect()->route('account.business.start');
    }

    /** Staff check exactly what was submitted, so a business under review or approved can't change. */
    private function businessLocked(Business $business): ?RedirectResponse
    {
        return match ($business->status) {
            Business::STATUS_SUBMITTED => back()->withErrors(['business' => 'This business is with our team for review and can\'t be changed right now.']),
            Business::STATUS_APPROVED => back()->withErrors(['business' => 'This business is verified. To change its details, message us on WhatsApp.']),
            default => null,
        };
    }

    /** A borrower who passed individual KYC with this ID number. */
    private function businessLinkedUser(string $idNumber): ?User
    {
        return User::where('id_number', $idNumber)->where('kyc_status', 'approved')->first();
    }
}
