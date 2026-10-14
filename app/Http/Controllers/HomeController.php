<?php

namespace App\Http\Controllers;

use App\Models\Address;
use App\Models\Borrower;
use App\Models\Employment;
use App\Models\PaymentMethod;
use App\Models\User;
use App\Services\SignatureService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * Public side of the app: the Aimzone website (welcome + pages in resources/views/home)
 * and self-service KYC for individual borrowers (personal details, identity documents,
 * income & payout, then review and submit). Each KYC step saves straight to the borrower's records.
 */
class HomeController extends Controller
{
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

    // ============ KYC: PERSONAL ============

    public function kycPersonal(Request $request): View
    {
        $user = $request->user();
        [$firstName, $lastName] = $this->splitName($user->name);

        return view('home.kyc.personal', [
            'user' => $user,
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

        return redirect()->route('kyc.documents');
    }

    // ============ KYC: DOCUMENTS ============

    public function kycDocuments(Request $request): View
    {
        return view('home.kyc.documents', ['user' => $request->user()]);
    }

    public function storeKycDocuments(Request $request, SignatureService $signatures): RedirectResponse
    {
        $user = $request->user();
        if ($locked = $this->lockedResponse($user)) {
            return $locked;
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
        ], [
            'id_front.required' => 'Upload the front of your national ID.',
            'id_back.required' => 'Upload the back of your national ID.',
            'passport_bio.required' => 'Upload your passport bio page.',
            'photo.required' => 'Upload a passport-size photo.',
        ]);

        if (!$user->signature && !$request->hasFile('signature_file') && !$request->filled('signature_data')) {
            return back()->withErrors(['signature' => 'Sign in the box, or upload a photo of your signature.']);
        }

        $updates = ['id_type' => $request->input('id_type')];
        $front = $passport ? 'passport_bio' : 'id_front';
        if ($request->hasFile($front)) {
            $updates['id_front_path'] = $this->replaceFile($user->id_front_path, $request->file($front)->store('id_cards', 'public'));
        }
        if (!$passport && $request->hasFile('id_back')) {
            $updates['id_back_path'] = $this->replaceFile($user->id_back_path, $request->file('id_back')->store('id_cards', 'public'));
        }
        if ($request->hasFile('photo')) {
            $updates['avatar'] = $this->replaceFile($user->avatar, $request->file('photo')->store('avatars', 'public'));
        }
        $user->update($updates);

        if ($request->hasFile('signature_file')) {
            $user->update(['signature' => $this->replaceFile($user->signature, $request->file('signature_file')->store('signatures', 'public'))]);
        } elseif ($request->filled('signature_data')) {
            $result = $signatures->saveSignature($request->input('signature_data'), $user);
            if (!$result['success']) {
                return back()->withErrors(['signature' => 'We could not save your signature. Please try again.']);
            }
        }

        return redirect()->route('kyc.income');
    }

    // ============ KYC: INCOME & PAYOUT ============

    public function kycIncome(Request $request): View
    {
        $user = $request->user();
        $employment = $user->currentEmployment;

        return view('home.kyc.income', [
            'user' => $user,
            'borrower' => $user->borrower,
            'incomeTypes' => self::INCOME_TYPES,
            'years' => $employment?->start_date ? (int) $employment->start_date->diffInYears(now()) : null,
            'sourceOfFunds' => $this->sourceOfFunds($employment),
            'mpesa' => $this->paymentMethod($user, 'mobile_money'),
            'bank' => $this->paymentMethod($user, 'bank_account'),
        ]);
    }

    public function storeKycIncome(Request $request): RedirectResponse
    {
        $user = $request->user();
        if ($locked = $this->lockedResponse($user)) {
            return $locked;
        }

        $request->merge(['mpesa_number' => preg_replace('/[\s-]/', '', (string) $request->input('mpesa_number'))]);
        $data = $request->validate([
            'income_type' => ['required', Rule::in(array_keys(self::INCOME_TYPES))],
            'employer_name' => ['required', 'string', 'max:255'],
            'job_title' => ['required', 'string', 'max:255'],
            'net_salary' => ['required', 'numeric', 'min:0', 'max:999999999'],
            'years' => ['required', 'integer', 'min:0', 'max:60'],
            'source_of_funds' => ['required', Rule::in(['Salary', 'Business income', 'Rental income', 'Other'])],
            'mpesa_number' => ['required', 'regex:/^(?:\+?254|0)[17]\d{8}$/'],
            'bank_name' => ['nullable', 'required_with:bank_account', 'string', 'max:255'],
            'bank_account' => ['nullable', 'required_with:bank_name', 'string', 'max:50'],
        ], [
            'mpesa_number.regex' => 'Enter a Safaricom number like 0712 345 678.',
        ]);

        DB::transaction(function () use ($user, $data) {
            $borrower = $user->borrower ?? $this->makeBorrower($user);
            $borrower->update([
                'income_type' => $data['income_type'],
                'employer_name' => $data['employer_name'],
                'workplace' => $data['employer_name'],
                'job_title' => $data['job_title'],
                'net_salary' => $data['net_salary'],
            ]);

            $employment = $user->currentEmployment ?? new Employment(['user_id' => $user->id, 'is_current' => true]);
            $employment->fill([
                'employer_name' => $data['employer_name'],
                'job_title' => $data['job_title'],
                'employment_type_id' => $this->lookupId('employment_types', self::EMPLOYMENT_TYPE_SLUGS[$data['income_type']]),
                'start_date' => now()->subYears((int) $data['years'])->startOfMonth()->toDateString(),
                'notes' => 'Source of funds: ' . $data['source_of_funds'],
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
                    'bank_name' => $data['bank_name'],
                    'account_number' => $data['bank_account'],
                    'is_primary' => false,
                ])->save();
            } elseif ($bank && !$bank->is_verified) {
                $bank->delete();
            }
        });

        return redirect()->route('kyc.review');
    }

    // ============ KYC: REVIEW & SUBMIT ============

    public function kycReview(Request $request): View
    {
        return view('home.kyc.review', ['sections' => $this->sections($request->user())]);
    }

    public function submitKyc(Request $request): RedirectResponse
    {
        $user = $request->user();
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

        $user->update(['kyc_submitted_at' => now()]);

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
            'verified' => (int) $user->borrower?->status === 1,
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
        $docsDone = $user->id_front_path && ($passport || $user->id_back_path) && $user->avatar && $user->signature;
        $incomeDone = $borrower?->income_type && $borrower->employer_name && $borrower->net_salary !== null && $mpesa;

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
                'title' => 'Identity documents',
                'href' => route('kyc.documents'),
                'complete' => (bool) $docsDone,
                'summary' => implode(' · ', [
                    $user->id_front_path ? ($passport ? 'Passport bio page' : 'National ID front & back') : 'ID missing',
                    $user->avatar ? 'photo' : 'photo missing',
                    $user->signature ? 'signature' : 'signature missing',
                ]),
            ],
            [
                'title' => 'Income & payout',
                'href' => route('kyc.income'),
                'complete' => (bool) $incomeDone,
                'summary' => $incomeDone
                    ? implode(' · ', [$borrower->formatted_income_type, 'KES ' . number_format($borrower->net_salary) . ' a month', 'M-Pesa ' . $mask($mpesa->mobile_number, 3)])
                    : 'Employment, income and M-Pesa number',
            ],
        ];
    }

    /**
     * Details can't change while a loan is in the pipeline (same rule as the profile page).
     */
    private function lockedResponse(User $user): ?RedirectResponse
    {
        if (!$user->isProfileLocked()) {
            return null;
        }

        return back()->withErrors(['kyc' => $user->getLoanStatusMessage() ?? 'Your details are locked while a loan is in progress.']);
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
        return $employment && str_starts_with((string) $employment->notes, 'Source of funds: ')
            ? substr($employment->notes, strlen('Source of funds: '))
            : null;
    }

    private function lookupId(string $table, string $slug): ?int
    {
        static $cache = [];

        return $cache["$table.$slug"] ??= DB::table($table)->where('slug', $slug)->value('id');
    }

    private function replaceFile(?string $old, string $new): string
    {
        if ($old && $old !== $new) {
            Storage::disk('public')->delete($old);
        }

        return $new;
    }

    private function splitName(?string $name): array
    {
        $parts = preg_split('/\s+/', trim((string) $name), 2);

        return [$parts[0] ?? '', $parts[1] ?? ''];
    }
}
