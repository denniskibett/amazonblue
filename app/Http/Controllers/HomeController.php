<?php

namespace App\Http\Controllers;

use App\Models\Address;
use App\Models\Borrower;
use App\Models\Employment;
use App\Models\Enquiry;
use App\Models\PaymentMethod;
use App\Models\User;
use App\Services\SignatureService;
use App\Support\PrivateFiles;
use App\Services\PartnerPortfolio;
use App\Services\LoanProgress;
use App\Services\LoanAgreementService;
use App\Services\BorrowingEligibility;
use App\Models\Partner;
use App\Models\Loan;
use Illuminate\Http\RedirectResponse;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * Public side of the app: the Aimzone website (welcome + pages in resources/views/home),
 * the contact page's enquiry form, and self-service KYC for individual borrowers (personal details, identity documents,
 * income & payout, then review and submit). Each KYC step saves straight to the borrower's records.
 */
class HomeController extends Controller
{
    use Concerns\BusinessOnboarding; // business KYC: /account/business/*

    use Concerns\BorrowerRepayment;
    use Concerns\BorrowerJourneys;
    use Concerns\PartnerSelfService;

    // Website pages served from resources/views/home
    public const PAGES = [
        'services', 'emergency-solutions', 'crb-solutions', 'asset-finance', 'commodities',
        'company', 'careers', 'contact', 'privacy', 'terms', 'disclaimer',
    ];

    public const COUNTIES = ['Nairobi', 'Mombasa', 'Kisumu', 'Nakuru', 'Kiambu', 'Machakos', 'Uasin Gishu', 'Other'];

    public const INCOME_TYPES = [
        'employed' => 'Employed',
        'self_employed' => 'Self-employed',
        'business' => 'Business owner',
        'other' => 'Other',
    ];

    // employment_types lookup slugs for each income type
    private const EMPLOYMENT_TYPE_SLUGS = [
        'employed' => 'full_time',
        'self_employed' => 'self_employed',
        'business' => 'business_owner',
        'other' => 'other',
    ];

    // ============ WEBSITE ============

    public function index(): View
    {
        return view('welcome');
    }

    public function page(string $page): View
    {
        return view('home.' . $page);
    }

    // ============ SEARCH ENGINES ============

    public function robots(): Response
    {
        $lines = [
            'User-agent: *',
            'Disallow: /dashboard',
            'Disallow: /account',
            'Disallow: /kyc',
            'Disallow: /storage',
            'Disallow: /files',
            'Disallow: /system',
            'Disallow: /verify-email',
            'Disallow: /login',
            'Disallow: /register',
            'Disallow: /forgot-password',
            'Disallow: /reset-password',
            '',
            'Sitemap: ' . route('sitemap'),
        ];

        return response(implode("\n", $lines) . "\n", 200, ['Content-Type' => 'text/plain']);
    }

    public function sitemap(): Response
    {
        $urls = array_merge([route('home')], array_map(fn ($page) => route('site.page', $page), self::PAGES));

        return response()->view('home.sitemap', ['urls' => $urls], 200, ['Content-Type' => 'application/xml']);
    }

    // ============ CUSTOMER ACCOUNT (borrowers & partners) ============
    // Customers use these pages (website design, mobile first); the dashboard is staff-only.

    public function account(Request $request, BorrowingEligibility $eligibility, LoanProgress $progress, PartnerPortfolio $portfolio): View|RedirectResponse
    {
        $user = $request->user();
        if (! $user->isCustomer()) {
            return redirect()->route('dashboard');
        }

        if ($user->hasRole('borrower')) {
            $openLoan = $eligibility->openLoan($user);

            return view('home.account.borrower', $this->borrowContext($user, $eligibility) + [
                'progress' => $openLoan ? $progress->for($openLoan) : null,
                'kycSteps' => $this->sections($user),
                'activity' => $this->recentActivity($user),
                // how each loan ended (on time, late, rolled over...), from the 4Cs scoring
                'journey' => app(\App\Services\CreditAssessment::class)->current($user),
            ]);
        }

        $partner = Partner::where('user_id', $user->id)->first();

        return view('home.account.partner', [
            'user' => $user,
            'portfolio' => $partner ? $portfolio->summary($partner) : null,
        ]);
    }

    public function accountBorrow(Request $request, BorrowingEligibility $eligibility): View|RedirectResponse
    {
        $user = $request->user();
        if ($eligibility->openLoan($user)) {
            return redirect()->route('account.home')->with('alert', ['type' => 'info', 'title' => 'You already have a loan', 'message' => 'You can borrow again once your current loan is repaid.']);
        }

        return view('home.account.borrow', $this->borrowContext($user, $eligibility));
    }

    public function accountLoans(Request $request): View
    {
        $loans = Loan::with(['loanType', 'cycles'])->where('user_id', $request->user()->id)
            ->latest('borrow_date')->latest('id')->paginate(10);

        return view('home.account.loans', ['user' => $request->user(), 'loans' => $loans]);
    }

    public function accountLoan(Request $request, Loan $loan, LoanProgress $progress): View
    {
        abort_unless($loan->user_id === $request->user()->id, 404);

        return view('home.account.loan', ['user' => $request->user(), 'progress' => $progress->for($loan)]);
    }

    /**
     * The signed agreement shown inside the account (PDF embedded, with a download button).
     */
    public function accountAgreement(Request $request, Loan $loan, LoanAgreementService $agreements): View
    {
        $this->agreementPath($request, $loan, $agreements);

        return view('home.account.agreement', ['user' => $request->user(), 'loan' => $loan->refresh()]);
    }

    public function accountAgreementFile(Request $request, Loan $loan, LoanAgreementService $agreements)
    {
        $path = $this->agreementPath($request, $loan, $agreements);
        $name = 'Aimzone loan agreement #' . str_pad($loan->id, 5, '0', STR_PAD_LEFT) . '.pdf';

        return $request->boolean('download')
            ? response()->download($path, $name, ['Content-Type' => 'application/pdf'])
            : response()->file($path, ['Content-Type' => 'application/pdf', 'Content-Disposition' => 'inline; filename="' . $name . '"']);
    }

    /**
     * The signed PDF for this borrower's loan (private disk). Frozen when they consented; generated
     * once on first view only for older loans signed before agreements were frozen.
     */
    private function agreementPath(Request $request, Loan $loan, LoanAgreementService $agreements): string
    {
        abort_unless($loan->user_id === $request->user()->id, 404);

        $path = $agreements->storedPath($loan);
        if (! $path) {
            abort_unless($loan->consent, 404);
            $path = $agreements->generateLoanAgreement($loan);
        }
        abort_unless(is_file($path), 404);

        return $path;
    }

    public function accountStatement(Request $request, PartnerPortfolio $portfolio): View
    {
        $partner = Partner::where('user_id', $request->user()->id)->firstOrFail();
        $wallet = $request->query('wallet');

        return view('home.account.statement', [
            'user' => $request->user(),
            'partner' => $partner,
            'wallet' => array_key_exists((string) $wallet, PartnerPortfolio::WALLETS) ? $wallet : null,
            'entries' => $portfolio->statement($partner, $wallet),
        ]);
    }

    public function accountProfile(Request $request, BorrowingEligibility $eligibility): View
    {
        $user = $request->user();
        $isBorrower = $user->hasRole('borrower');

        return view('home.account.profile', [
            'user' => $user,
            'isBorrower' => $isBorrower,
            'isVerified' => $isBorrower && $eligibility->isVerified($user),
            'kycSteps' => $isBorrower ? $this->sections($user) : [],
            'address' => $isBorrower ? $this->currentAddress($user) : null,
            'mpesa' => $isBorrower ? $this->paymentMethod($user, 'mobile_money') : null,
            'guarantor' => $isBorrower ? $user->contacts()->where('relationship_specific', self::GUARANTOR)->latest('id')->first() : null,
            'partner' => $user->hasRole('partner') ? Partner::where('user_id', $user->id)->first() : null,
        ]);
    }

    /**
     * Everything the Borrow screens need: limit, fixed product and what (if anything) blocks applying.
     */
    private function borrowContext(User $user, BorrowingEligibility $eligibility): array
    {
        $product = $eligibility->loanType();

        return [
            'user' => $user,
            'limit' => $eligibility->limit($user),
            'blockers' => $eligibility->blockers($user),
            'isVerified' => $eligibility->isVerified($user),
            'isExistingClient' => $eligibility->isExistingClient($user),
            'product' => $product ? [
                'name' => $product->name,
                'rate' => (float) $product->interest_rate,
                'penalty_rate' => (float) $product->penalty_rate,
                'period' => $product->period . ' ' . $product->unit,
                'due_date' => $eligibility->quote(1)['due_date'],
                'min' => (int) config('lending.min_amount'),
                'good_standing_limit' => (int) config('lending.good_standing_limit'),
            ] : null,
        ];
    }

    /**
     * Latest money movements across the borrower's loans: money sent and payments made.
     */
    private function recentActivity(User $user): \Illuminate\Support\Collection
    {
        $loanIds = Loan::where('user_id', $user->id)->pluck('id');

        $sent = DB::table('disbursements')->whereIn('loan_id', $loanIds)->whereNull('deleted_at')
            ->get(['loan_id', 'amount', 'net_amount', 'disburse_date as date', 'transaction'])
            ->map(fn ($d) => ['kind' => 'sent', 'label' => 'Sent to your M-Pesa', 'amount' => (float) ($d->net_amount ?: $d->amount), 'date' => $d->date, 'loan_id' => $d->loan_id, 'ref' => $d->transaction]);
        $paid = DB::table('repayments')->whereIn('loan_id', $loanIds)->whereNull('deleted_at')
            ->get(['loan_id', 'amount', 'repayment_date as date', 'transaction'])
            ->map(fn ($r) => ['kind' => 'paid', 'label' => 'Payment received', 'amount' => (float) $r->amount, 'date' => $r->date, 'loan_id' => $r->loan_id, 'ref' => $r->transaction]);

        // real cash only, like the wallet: rollovers, waivers and write-offs aren't money moving
        return $sent->concat($paid)
            ->filter(fn ($item) => \App\Services\CustomerWallets::kind($item['ref']) === 'cash')
            ->sortByDesc('date')->take(5)->values();
    }

    // ============ CONTACT: CONFIDENTIAL ENQUIRY ============

    /**
     * Save an enquiry from the contact page. The same person (matched by email or phone)
     * can enquire about every topic, but only once per topic; a repeat is not saved and
     * the visitor is shown their existing reference instead.
     */
    public function storeEnquiry(Request $request): RedirectResponse
    {
        $request->merge([
            'email' => strtolower(trim((string) $request->input('email'))),
            'phone' => Enquiry::normalisePhone($request->input('phone')),
        ]);

        $data = $request->validate([
            'topic' => ['required', Rule::in(array_keys(Enquiry::TOPICS))],
            'name' => ['required', 'string', 'max:255'],
            'organisation' => ['nullable', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255'],
            'phone' => ['required', 'regex:/^\d{9,15}$/'],
            'investor_type' => ['nullable', 'string', 'max:100'],
            'allocation' => ['nullable', 'string', 'max:100'],
            'message' => ['required', 'string', 'max:5000'],
            'consent' => ['accepted'],
        ], [
            'phone.regex' => 'Enter a phone number we can call, e.g. +254 712 345 678.',
            'consent.accepted' => 'Please confirm so we can contact you.',
        ]);

        $back = redirect()->to(route('site.page', 'contact') . '#message');

        $existing = $this->findEnquiry($data['topic'], $data['email'], $data['phone']);
        if ($existing) {
            return $back->with('enquiry', ['duplicate' => true] + $this->enquiryFlash($existing));
        }

        $isInvestment = $data['topic'] === 'investment';
        try {
            $enquiry = Enquiry::create([
                'topic' => $data['topic'],
                'name' => $data['name'],
                'organisation' => $data['organisation'] ?? null,
                'email' => $data['email'],
                'phone' => $data['phone'],
                'investor_type' => $isInvestment ? ($data['investor_type'] ?? null) : null,
                'allocation' => $isInvestment ? ($data['allocation'] ?? null) : null,
                'message' => $data['message'],
                'route_to' => Enquiry::TOPICS[$data['topic']][1], // decided here, not by the browser
                'consented_at' => now(),
                'ip_address' => $request->ip(),
                'user_agent' => substr((string) $request->userAgent(), 0, 255),
            ]);
        } catch (UniqueConstraintViolationException) {
            // a second submit landed at the same moment; the database kept only the first
            $existing = $this->findEnquiry($data['topic'], $data['email'], $data['phone']);

            return $back->with('enquiry', ['duplicate' => true] + $this->enquiryFlash($existing));
        }

        return $back->with('enquiry', ['duplicate' => false] + $this->enquiryFlash($enquiry));
    }

    private function findEnquiry(string $topic, string $email, string $phone): ?Enquiry
    {
        return Enquiry::where('topic', $topic)
            ->where(fn ($q) => $q->where('email', $email)->orWhere('phone', $phone))
            ->oldest('id')
            ->first();
    }

    private function enquiryFlash(Enquiry $enquiry): array
    {
        return [
            'reference' => $enquiry->reference,
            'topic' => $enquiry->topic_label,
            'name' => strtok($enquiry->name, ' '),
            'date' => $enquiry->created_at->format('j M Y'),
            'desk' => $enquiry->route_to,
        ];
    }

    // ============ KYC: PERSONAL ============

    public function kycPersonal(Request $request): View
    {
        $user = $request->user();
        [$firstName, $lastName] = $this->splitName($user->name);

        return view('home.kyc.personal', [
            'user' => $user,
            'locked' => $this->lockedFields($user, $this->personalSaved($user)),
            'firstName' => $firstName,
            'lastName' => $lastName,
            'address' => $this->currentAddress($user),
            'counties' => self::COUNTIES,
        ]);
    }

    public function storeKycPersonal(Request $request): RedirectResponse
    {
        $user = $request->user();
        if ($locked = $this->lockedResponse($user)) {
            return $locked;
        }

        $this->keepSaved($request, $user, $this->personalSaved($user));
        $request->merge(['kra_pin' => strtoupper(trim((string) $request->input('kra_pin')))]);
        $data = $request->validate([
            'first_name' => ['required', 'string', 'max:100'],
            'last_name' => ['required', 'string', 'max:100'],
            'id_number' => ['required', 'string', 'max:30', Rule::unique('users', 'id_number')->ignore($user->id)],
            'dob' => ['required', 'date', 'before_or_equal:' . now()->subYears(18)->toDateString()],
            'kra_pin' => ['required', 'regex:/^[AP]\d{9}[A-Z]$/'],
            'county' => ['required', Rule::in(self::COUNTIES)],
            'address' => ['required', 'string', 'max:255'],
        ], [
            'id_number.unique' => 'This ID or passport number is already registered. Contact us if this is your number.',
            'dob.before_or_equal' => 'You must be at least 18 years old.',
            'kra_pin.regex' => 'Enter a valid KRA PIN, e.g. A012345678X.',
        ]);

        // while a loan is running, the identity on its signed agreement can't change
        if ($user->isProfileLocked()) {
            [$first, $last] = $this->splitName($user->name);
            $changed = array_keys(array_filter([
                'first_name' => $first !== '' && strcasecmp($first, $data['first_name']) !== 0,
                'last_name' => $last !== '' && strcasecmp($last, $data['last_name']) !== 0,
                'id_number' => filled($user->id_number) && $user->id_number !== $data['id_number'],
                'dob' => $user->dob && $user->dob->toDateString() !== $data['dob'],
            ]));
            if ($changed) {
                return back()->withInput()->withErrors(array_fill_keys($changed,
                    'This is on your current loan agreement, so it can\'t change until the loan is repaid. Contact us if it is wrong.'));
            }
        }

        DB::transaction(function () use ($user, $data) {
            $user->update([
                'name' => trim($data['first_name'] . ' ' . $data['last_name']),
                'id_number' => $data['id_number'],
                'dob' => $data['dob'],
                'kra_pin' => $data['kra_pin'],
                'nationality' => $user->nationality ?: 'Kenyan',
            ]);

            $address = $this->currentAddress($user) ?? new Address([
                'user_id' => $user->id,
                'address_type_id' => $this->lookupId('address_types', 'current'),
                'is_primary' => true,
                'country' => 'Kenya',
            ]);
            $address->fill([
                'address_line_1' => $data['address'],
                'state' => $data['county'],
                'city' => $data['county'] === 'Other' ? null : $data['county'],
            ])->save();
        });

        return redirect()->route('kyc.income');
    }

    // ============ KYC: DOCUMENTS ============

    public function kycDocuments(Request $request): View
    {
        $user = $request->user();

        return view('home.kyc.documents', ['user' => $user, 'lockSaved' => $this->locksSaved($user)]);
    }

    public function storeKycDocuments(Request $request, SignatureService $signatures): RedirectResponse
    {
        $user = $request->user();
        if ($locked = $this->lockedResponse($user)) {
            return $locked;
        }

        if ($this->locksSaved($user)) {
            if ($user->id_type) {
                $request->merge(['id_type' => $user->id_type]);
            }
            foreach (['id_front' => $user->id_front_path, 'passport_bio' => $user->id_front_path, 'id_back' => $user->id_back_path, 'photo' => $user->avatar, 'signature_file' => $user->signature]
                + array_map(fn ($column) => $user->{$column}, self::SELFIES) as $field => $onFile) {
                if ($onFile) {
                    $request->files->remove($field);
                }
            }
            if ($user->signature) {
                $request->request->remove('signature_data');
            }
        }

        $passport = $request->input('id_type') === 'passport';
        // a file is only required when nothing is on record yet (or the document type changed)
        $frontOnFile = $user->id_front_path && $user->id_type === $request->input('id_type');
        $doc = ['file', 'mimes:jpg,jpeg,png,webp,heic,pdf', 'max:10240'];

        $request->validate([
            'id_type' => ['required', Rule::in(['national_id', 'passport'])],
            'id_front' => [Rule::requiredIf(!$passport && !$frontOnFile), 'nullable', ...$doc],
            'id_back' => [Rule::requiredIf(!$passport && !$user->id_back_path), 'nullable', ...$doc],
            'passport_bio' => [Rule::requiredIf($passport && !$frontOnFile), 'nullable', ...$doc],
            'photo' => [Rule::requiredIf(!$user->avatar), 'nullable', 'image', 'max:10240'],
            'signature_file' => ['nullable', 'image', 'max:10240'],
            'signature_data' => ['nullable', 'string', 'starts_with:data:image/png;base64,'],
            'selfie_center' => [Rule::requiredIf(!$user->selfie_center_path), 'nullable', 'image', 'max:10240'],
            'selfie_left' => [Rule::requiredIf(!$user->selfie_left_path), 'nullable', 'image', 'max:10240'],
            'selfie_right' => [Rule::requiredIf(!$user->selfie_right_path), 'nullable', 'image', 'max:10240'],
        ], [
            'id_front.required' => 'Upload the front of your national ID.',
            'id_back.required' => 'Upload the back of your national ID.',
            'passport_bio.required' => 'Upload your passport bio page.',
            'photo.required' => 'Upload a passport-size photo.',
            'selfie_center.required' => 'Take a selfie looking straight at the camera.',
            'selfie_left.required' => 'Take a selfie with your head turned to your left.',
            'selfie_right.required' => 'Take a selfie with your head turned to your right.',
        ]);

        if (!$user->signature && !$request->hasFile('signature_file') && !$request->filled('signature_data')) {
            return back()->withErrors(['signature' => 'Sign in the box, or upload a photo of your signature.']);
        }

        $updates = ['id_type' => $request->input('id_type')];
        $front = $passport ? 'passport_bio' : 'id_front';
        if ($request->hasFile($front)) {
            $updates['id_front_path'] = $this->replaceFile($user->id_front_path, PrivateFiles::storeUpload($request->file($front), 'id_cards', $user));
        }
        if (!$passport && $request->hasFile('id_back')) {
            $updates['id_back_path'] = $this->replaceFile($user->id_back_path, PrivateFiles::storeUpload($request->file('id_back'), 'id_cards', $user));
        }
        if ($request->hasFile('photo')) {
            $updates['avatar'] = $this->replaceFile($user->avatar, PrivateFiles::storeUpload($request->file('photo'), 'avatars', $user));
        }
        foreach (self::SELFIES as $field => $column) {
            if ($request->hasFile($field)) {
                $updates[$column] = $this->replaceFile($user->{$column}, PrivateFiles::storeUpload($request->file($field), 'selfies', $user));
            }
        }
        $user->update($updates);

        if ($request->hasFile('signature_file')) {
            $user->update(['signature' => $this->replaceFile($user->signature, PrivateFiles::storeUpload($request->file('signature_file'), 'signatures', $user))]);
        } elseif ($request->filled('signature_data')) {
            $result = $signatures->saveSignature($request->input('signature_data'), $user);
            if (!$result['success']) {
                return back()->withErrors(['signature' => 'We could not save your signature. Please try again.']);
            }
        }

        return redirect()->route('kyc.review');
    }

    // ============ KYC: INCOME & PAYOUT ============

    public function kycIncome(Request $request): View
    {
        $user = $request->user();
        $saved = $this->incomeSaved($user);

        return view('home.kyc.income', [
            'user' => $user,
            'saved' => $saved,
            'locked' => $this->lockedFields($user, $saved),
            'incomeTypes' => self::INCOME_TYPES,
            'banks' => \App\Support\Banks::names(),
        ]);
    }

    /**
     * Fields each employment status asks for (required ones first). The form shows only the active set.
     */
    public const INCOME_FIELDS = [
        'employed' => ['required' => ['employer_name', 'job_title', 'net_salary', 'years'], 'optional' => ['employer_phone']],
        'self_employed' => ['required' => ['job_title', 'workplace', 'net_salary', 'years'], 'optional' => ['employer_name']],
        'business' => ['required' => ['employer_name', 'business_type', 'workplace', 'net_salary', 'years'], 'optional' => ['registration_no']],
        'other' => ['required' => ['job_title', 'net_salary'], 'optional' => ['employer_name']],
    ];

    public function storeKycIncome(Request $request): RedirectResponse
    {
        $user = $request->user();
        if ($locked = $this->lockedResponse($user)) {
            return $locked;
        }

        $this->keepSaved($request, $user, $this->incomeSaved($user));
        $request->merge([
            'mpesa_number' => preg_replace('/[\s-]/', '', (string) $request->input('mpesa_number')),
            'employer_phone' => preg_replace('/[\s-]/', '', (string) $request->input('employer_phone')) ?: null,
            'net_salary' => preg_replace('/[^\d.]/', '', (string) $request->input('net_salary')),
        ]);
        $type = (string) $request->input('income_type');
        $fields = self::INCOME_FIELDS[$type] ?? ['required' => [], 'optional' => []];
        $need = fn ($field) => in_array($field, $fields['required'], true) ? 'required' : 'nullable';

        $data = $request->validate([
            'income_type' => ['required', Rule::in(array_keys(self::INCOME_TYPES))],
            'employer_name' => [$need('employer_name'), 'string', 'max:255'],
            'job_title' => [$need('job_title'), 'string', 'max:255'],
            'workplace' => [$need('workplace'), 'string', 'max:255'],
            'business_type' => [$need('business_type'), 'string', 'max:255'],
            'registration_no' => ['nullable', 'string', 'max:50'],
            'employer_phone' => ['nullable', 'regex:/^(?:\+?254|0)\d{9}$/'],
            'net_salary' => ['required', 'numeric', 'min:0', 'max:999999999'],
            'years' => [$need('years'), 'nullable', 'integer', 'min:0', 'max:60'],
            'source_of_funds' => ['required', Rule::in(['Salary', 'Business income', 'Rental income', 'Other'])],
            'mpesa_number' => ['required', 'regex:/^(?:\+?254|0)[17]\d{8}$/'],
            'bank_name' => ['nullable', 'required_with:bank_account', Rule::in(array_merge(\App\Support\Banks::names(), [\App\Support\Banks::OTHER]))],
            'bank_other' => ['nullable', 'required_if:bank_name,' . \App\Support\Banks::OTHER, 'string', 'max:255'],
            'bank_account' => ['nullable', 'required_with:bank_name', 'string', 'max:50'],
        ], [
            'mpesa_number.regex' => 'Enter a Safaricom number like 0712 345 678.',
            'employer_phone.regex' => 'Enter a Kenyan phone number like 0712 345 678.',
            'employer_name.required' => $type === 'business' ? 'Enter your business name.' : 'Enter your employer\'s name.',
            'job_title.required' => match ($type) { 'self_employed' => 'Tell us what you do.', 'other' => 'Tell us your main source of income.', default => 'Enter your job title.' },
            'workplace.required' => $type === 'business' ? 'Where is the business?' : 'Where do you work from?',
            'business_type.required' => 'What does the business do?',
            'bank_other.required_if' => 'Type the name of your bank.',
        ]);
        $bankName = ($data['bank_name'] ?? null) === \App\Support\Banks::OTHER ? ($data['bank_other'] ?? null) : ($data['bank_name'] ?? null);

        DB::transaction(function () use ($user, $data, $type, $bankName) {
            $employer = $data['employer_name'] ?? null;
            $jobTitle = $type === 'business' ? 'Owner' : ($data['job_title'] ?? null);

            $borrower = $user->borrower ?? $this->makeBorrower($user);
            $borrower->update([
                'income_type' => $type,
                'employer_name' => $employer ?: ($type === 'self_employed' ? 'Self-employed' : null),
                'workplace' => $data['workplace'] ?? $employer,
                'job_title' => $jobTitle,
                'department' => $data['business_type'] ?? null,
                'net_salary' => $data['net_salary'],
            ]);

            $employment = $user->currentEmployment ?? new Employment(['user_id' => $user->id, 'is_current' => true]);
            $employment->fill([
                'employer_name' => $employer,
                'employer_address' => $data['workplace'] ?? null,
                'employer_phone' => $data['employer_phone'] ?? null,
                'job_title' => $jobTitle,
                'department' => $data['business_type'] ?? null,
                'employment_type_id' => $this->lookupId('employment_types', self::EMPLOYMENT_TYPE_SLUGS[$type]),
                'start_date' => isset($data['years']) ? now()->subYears((int) $data['years'])->startOfMonth()->toDateString() : null,
                'notes' => implode("\n", array_filter([
                    'Source of funds: ' . $data['source_of_funds'],
                    ! empty($data['registration_no']) ? 'Registration: ' . $data['registration_no'] : null,
                ])),
            ])->save();

            $mpesa = $this->paymentMethod($user, 'mobile_money') ?? new PaymentMethod([
                'user_id' => $user->id,
                'method_type_id' => $this->lookupId('payment_method_types', 'mobile_money'),
                'status' => 'pending_verification',
            ]);
            $mpesa->fill([
                'account_name' => $user->name,
                'mobile_network' => 'M-Pesa',
                'mobile_number' => $data['mpesa_number'],
                'is_primary' => true,
            ])->save();

            $bank = $this->paymentMethod($user, 'bank_account');
            if (!empty($data['bank_account'])) {
                ($bank ?? new PaymentMethod([
                    'user_id' => $user->id,
                    'method_type_id' => $this->lookupId('payment_method_types', 'bank_account'),
                    'status' => 'pending_verification',
                ]))->fill([
                    'account_name' => $user->name,
                    'bank_name' => $bankName,
                    'account_number' => $data['bank_account'],
                    'is_primary' => false,
                ])->save();
            } elseif ($bank && !$bank->is_verified) {
                $bank->delete();
            }
        });

        return redirect()->route('kyc.contacts');
    }

    // ============ KYC: NEXT OF KIN & GUARANTOR ============

    // relationship options (contact_types slugs) for the next of kin and guarantor
    private const RELATIONSHIPS = [
        'spouse' => 'Spouse', 'parent' => 'Parent', 'sibling' => 'Sibling', 'child' => 'Child',
        'relative' => 'Relative', 'friend' => 'Friend', 'colleague' => 'Colleague', 'employer' => 'Employer',
        'business_partner' => 'Business Partner', 'neighbor' => 'Neighbor', 'other' => 'Other',
    ];

    // guarantors are kept in contacts, marked by this relationship_specific value
    public const GUARANTOR = 'Guarantor';

    // KYC selfies: form field => users column (face straight, turned left, turned right)
    public const SELFIES = ['selfie_center' => 'selfie_center_path', 'selfie_left' => 'selfie_left_path', 'selfie_right' => 'selfie_right_path'];

    public function kycContacts(Request $request): View
    {
        $user = $request->user();
        $guarantor = $user->contacts()->where('relationship_specific', self::GUARANTOR)->latest('id')->first();

        return view('home.kyc.contacts', [
            'user' => $user,
            'guarantor' => $guarantor,
            'guarantorType' => $guarantor ? DB::table('contact_types')->where('id', $guarantor->contact_type_id)->value('slug') : null,
            'relationships' => self::RELATIONSHIPS,
            'locked' => $this->lockedFields($user, $this->contactsSaved($user)),
        ]);
    }

    public function storeKycContacts(Request $request): RedirectResponse
    {
        $user = $request->user();
        if ($locked = $this->lockedResponse($user)) {
            return $locked;
        }

        $this->keepSaved($request, $user, $this->contactsSaved($user));
        $normalise = fn ($phone) => preg_replace('/[\s-]/', '', (string) $phone);
        $request->merge([
            'kin_phone' => $normalise($request->input('kin_phone')),
            'guarantor_phone' => $normalise($request->input('guarantor_phone')),
        ]);
        $kenyan = 'regex:/^(?:\+?254|0)[17]\d{8}$/';
        $data = $request->validate([
            'kin_name' => ['required', 'string', 'max:255'],
            'kin_relation' => ['required', Rule::in(array_values(self::RELATIONSHIPS))],
            'kin_phone' => ['required', $kenyan, Rule::notIn([$normalise($user->phone)])],
            'guarantor_name' => ['required', 'string', 'max:255'],
            'guarantor_type' => ['required', Rule::in(array_keys(self::RELATIONSHIPS))],
            'guarantor_phone' => ['required', $kenyan, 'different:kin_phone', Rule::notIn([$normalise($user->phone)])],
        ], [
            'kin_phone.regex' => 'Enter a Kenyan mobile number like 0712 345 678.',
            'guarantor_phone.regex' => 'Enter a Kenyan mobile number like 0712 345 678.',
            'kin_phone.not_in' => 'Use your next of kin\'s number, not your own.',
            'guarantor_phone.not_in' => 'Use your guarantor\'s number, not your own.',
            'guarantor_phone.different' => 'Your guarantor must be a different person from your next of kin.',
        ]);

        DB::transaction(function () use ($user, $data) {
            $user->update([
                'kin_name' => $data['kin_name'],
                'kin_relation' => $data['kin_relation'],
                'kin_phone' => $data['kin_phone'],
            ]);

            $guarantor = $user->contacts()->where('relationship_specific', self::GUARANTOR)->latest('id')->first()
                ?? $user->contacts()->make(['relationship_specific' => self::GUARANTOR, 'priority' => 1]);
            $guarantor->fill([
                'contact_type_id' => $this->lookupId('contact_types', $data['guarantor_type']),
                'name' => $data['guarantor_name'],
                'phone' => $data['guarantor_phone'],
                'created_by' => $guarantor->exists ? $guarantor->created_by : $user->id,
                'updated_by' => $user->id,
            ])->save();
        });

        return redirect()->route('kyc.documents');
    }

    // ============ KYC: REVIEW & SUBMIT ============

    public function kycReview(Request $request): View
    {
        $user = $request->user();

        return view('home.kyc.review', ['sections' => $this->sections($user), 'verified' => $this->locksSaved($user)]);
    }

    public function submitKyc(Request $request): RedirectResponse
    {
        $user = $request->user();

        // already verified: the missing details they added are saved, no declarations or new review needed
        if ($this->locksSaved($user)) {
            $incomplete = collect($this->sections($user))->firstWhere('complete', false);

            return $incomplete
                ? redirect($incomplete['href'])->withErrors(['kyc' => $incomplete['title'] . ' still has details missing.'])
                : redirect()->route('account.profile')->with('alert', ['type' => 'success', 'title' => 'Details saved', 'message' => 'Thanks, your profile is complete.']);
        }

        $request->validate([
            'confirm_true' => ['accepted'],
            'confirm_not_pep' => ['accepted'],
            'confirm_crb' => ['accepted'],
        ], [
            '*.accepted' => 'Tick all three declarations to submit.',
        ]);

        $incomplete = collect($this->sections($user))->firstWhere('complete', false);
        if ($incomplete) {
            return redirect($incomplete['href'])->withErrors(['kyc' => $incomplete['title'] . ' is not finished yet.']);
        }

        // waits for staff, who approve it after calling the next of kin and guarantor
        $user->update(['kyc_submitted_at' => now(), 'kyc_status' => 'submitted']);
        \App\Support\CustomerNotifier::kycSubmitted($user);

        return redirect()->route('kyc.submitted');
    }

    public function kycSubmitted(Request $request): View|RedirectResponse
    {
        $user = $request->user();
        if (!$user->kyc_submitted_at) {
            return redirect()->route('kyc.review');
        }

        return view('home.kyc.submitted', [
            'user' => $user,
            'firstName' => $this->splitName($user->name)[0],
            'reference' => 'KYC-' . str_pad((string) $user->id, 6, '0', STR_PAD_LEFT),
            'verified' => app(\App\Services\BorrowingEligibility::class)->isVerified($user),
        ]);
    }

    // ============ HELPERS ============

    /**
     * Review summaries, and whether each step has everything the loan team needs.
     */
    private function sections(User $user): array
    {
        $address = $this->currentAddress($user);
        $borrower = $user->borrower;
        $mpesa = $this->paymentMethod($user, 'mobile_money');
        $passport = $user->id_type === 'passport';
        $mask = fn ($v, $keep) => strlen($v) > $keep ? str_repeat('•', strlen($v) - $keep) . substr($v, -$keep) : $v;

        $personalDone = $user->id_number && $user->dob && $user->kra_pin && $address;
        $selfiesDone = collect(self::SELFIES)->every(fn ($column) => filled($user->{$column}));
        $docsDone = $user->id_front_path && ($passport || $user->id_back_path) && $user->avatar && $user->signature && $selfiesDone;
        $income = $this->incomeSaved($user);
        $incomeDone = $income['income_type'] && $mpesa && $income['net_salary'] !== null
            && collect(self::INCOME_FIELDS[$income['income_type']]['required'])->every(fn ($f) => filled($income[$f]));
        $guarantor = $user->contacts()->where('relationship_specific', self::GUARANTOR)->latest('id')->first();
        $contactsDone = $user->kin_name && $user->kin_phone && $guarantor;

        return [
            [
                'title' => 'Personal details',
                'href' => route('kyc.personal'),
                'complete' => (bool) $personalDone,
                'summary' => $personalDone
                    ? implode(' · ', array_filter([$user->name, 'ID ' . $mask($user->id_number, 4), 'KRA PIN ' . $mask($user->kra_pin, 3), $address->state]))
                    : 'Your name, ID, KRA PIN and address',
            ],
            [
                'title' => 'Income & payout',
                'href' => route('kyc.income'),
                'complete' => (bool) $incomeDone,
                'summary' => $incomeDone
                    ? implode(' · ', [$borrower->formatted_income_type, 'KES ' . number_format((float) $borrower->net_salary, 2) . ' a month', 'M-Pesa ' . $mask($mpesa->mobile_number, 3)])
                    : 'Employment, income and M-Pesa number',
            ],
            [
                'title' => 'Next of kin & guarantor',
                'href' => route('kyc.contacts'),
                'complete' => (bool) $contactsDone,
                'summary' => $contactsDone
                    ? implode(' · ', [$user->kin_name . ' (' . $user->kin_relation . ')', 'Guarantor ' . $guarantor->name])
                    : 'Who we call to confirm your details',
            ],
            [
                'title' => 'Identity documents',
                'href' => route('kyc.documents'),
                'complete' => (bool) $docsDone,
                'summary' => implode(' · ', [
                    $user->id_front_path ? ($passport ? 'Passport bio page' : 'National ID front & back') : 'ID missing',
                    $user->avatar ? 'photo' : 'photo missing',
                    $selfiesDone ? '3 selfies' : 'selfies missing',
                    $user->signature ? 'signature' : 'signature missing',
                ]),
            ],
        ];
    }

    /**
     * Details can't change while a loan is in the pipeline (same rule as the profile page).
     */
    /**
     * Once approved, what is on file stays as it is (shown as "Saved"); the borrower only adds what's missing.
     * Before submission everything is editable, and a rejection reopens everything for correction.
     */
    private function locksSaved(User $user): bool
    {
        return $user->kyc_status === 'approved';
    }

    /**
     * Saved fields (those with a value) that are locked for this borrower.
     */
    private function lockedFields(User $user, array $saved): array
    {
        return $this->locksSaved($user) ? array_keys(array_filter($saved, fn ($v) => filled($v))) : [];
    }

    /**
     * Put the value on file back for every locked field, so nothing saved can change (even if the form is tampered with).
     */
    private function keepSaved(Request $request, User $user, array $saved): void
    {
        $locked = array_intersect_key($saved, array_flip($this->lockedFields($user, $saved)));
        if ($locked) {
            $request->merge($locked);
        }
    }

    private function personalSaved(User $user): array
    {
        [$first, $last] = $this->splitName($user->name);
        $address = $this->currentAddress($user);

        return [
            'first_name' => $first, 'last_name' => $last, 'id_number' => $user->id_number,
            'dob' => $user->dob?->toDateString(), 'kra_pin' => $user->kra_pin,
            'county' => $address?->state, 'address' => $address?->address_line_1,
        ];
    }

    private function contactsSaved(User $user): array
    {
        $guarantor = $user->contacts()->where('relationship_specific', self::GUARANTOR)->latest('id')->first();

        return [
            'kin_name' => $user->kin_name, 'kin_relation' => $user->kin_relation, 'kin_phone' => $user->kin_phone,
            'guarantor_name' => $guarantor?->name,
            'guarantor_type' => $guarantor ? DB::table('contact_types')->where('id', $guarantor->contact_type_id)->value('slug') : null,
            'guarantor_phone' => $guarantor?->phone,
        ];
    }

    private function incomeSaved(User $user): array
    {
        $borrower = $user->borrower;
        $employment = $user->currentEmployment;
        $notes = collect(preg_split('/\R/', (string) $employment?->notes));
        $mpesa = $this->paymentMethod($user, 'mobile_money');
        $bank = $this->paymentMethod($user, 'bank_account');

        return [
            'income_type' => $borrower?->income_type && array_key_exists($borrower->income_type, self::INCOME_TYPES) ? $borrower->income_type : null,
            'employer_name' => $borrower?->employer_name,
            'job_title' => $borrower?->job_title,
            'workplace' => $borrower?->workplace,
            'business_type' => $employment?->department,
            'registration_no' => ($line = $notes->first(fn ($l) => str_starts_with($l, 'Registration: '))) ? substr($line, 14) : null,
            'employer_phone' => $employment?->employer_phone,
            'net_salary' => $borrower?->net_salary !== null ? (string) (int) $borrower->net_salary : null,
            'years' => $employment?->start_date ? (string) (int) $employment->start_date->diffInYears(now()) : null,
            'source_of_funds' => $this->sourceOfFunds($employment),
            'mpesa_number' => $mpesa?->mobile_number,
            'bank_name' => $bank?->bank_name,
            'bank_account' => $bank?->account_number,
        ];
    }

    private function lockedResponse(User $user): ?RedirectResponse
    {
        // staff verify exactly what was submitted, so details stay fixed under review and once approved
        if ($user->kyc_status === 'submitted') {
            return back()->withErrors(['kyc' => 'Your details are with our team for verification and can\'t be changed right now.']);
        }
        // existing clients complete KYC once even while a loan is running; identity details
        // on that loan's agreement are protected separately in storeKycPersonal()
        return null;
    }

    private function makeBorrower(User $user): Borrower
    {
        return Borrower::create(['user_id' => $user->id, 'client_type' => 'individual', 'status' => 0]);
    }

    private function currentAddress(User $user): ?Address
    {
        return $user->addresses()
            ->where('address_type_id', $this->lookupId('address_types', 'current'))
            ->orderByDesc('is_primary')
            ->latest('id')
            ->first();
    }

    private function paymentMethod(User $user, string $typeSlug): ?PaymentMethod
    {
        return $user->paymentMethods()
            ->where('method_type_id', $this->lookupId('payment_method_types', $typeSlug))
            ->latest('id')
            ->first();
    }

    private function sourceOfFunds(?Employment $employment): ?string
    {
        $first = strtok((string) $employment?->notes, "\n");

        return $first !== false && str_starts_with($first, 'Source of funds: ') ? trim(substr($first, strlen('Source of funds: '))) : null;
    }

    private function lookupId(string $table, string $slug): ?int
    {
        static $cache = [];

        return $cache["$table.$slug"] ??= DB::table($table)->where('slug', $slug)->value('id');
    }

    // KYC documents are private (storage/app/private), named {user id}_{random}, so nobody else's upload can replace them
    private function replaceFile(?string $old, string $new): string
    {
        if ($old && $old !== $new) {
            PrivateFiles::delete($old);
        }

        return $new;
    }

    private function splitName(?string $name): array
    {
        $parts = preg_split('/\s+/', trim((string) $name), 2);

        return [$parts[0] ?? '', $parts[1] ?? ''];
    }
}
