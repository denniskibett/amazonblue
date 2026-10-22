@extends('layouts.app')

@php
    // documents are private: served through files.user (owner or staff only)
    $doc = fn ($path) => $path ? $user->documentUrl(array_search($path, ['id-front' => $user->id_front_path, 'id-back' => $user->id_back_path, 'photo' => $user->avatar, 'signature' => $user->signature,
        'selfie-center' => $user->selfie_center_path, 'selfie-left' => $user->selfie_left_path, 'selfie-right' => $user->selfie_right_path], true)) : null;
    $isPdf = fn ($path) => $path && str_ends_with(strtolower($path), '.pdf');
    $card = 'rounded-2xl border border-gray-200 bg-white p-5 dark:border-gray-800 dark:bg-white/[0.03]';
    $label = 'text-xs text-gray-500 dark:text-gray-400';
    $value = 'text-sm font-medium text-gray-800 dark:text-white/90';
    $badge = [
        'submitted' => 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-300',
        'approved' => 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-300',
        'rejected' => 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-300',
    ][$user->kyc_status] ?? 'bg-gray-100 text-gray-700';
    $docKeys = ['id-front' => $user->id_front_path, 'id-back' => $user->id_back_path, 'photo' => $user->avatar, 'signature' => $user->signature,
        'selfie-center' => $user->selfie_center_path, 'selfie-left' => $user->selfie_left_path, 'selfie-right' => $user->selfie_right_path];
    // a version stamp on each picture so a fresh crop shows straight away
    $ver = fn ($path) => ($f = \App\Support\PrivateFiles::absolute($path)) ? '?v=' . filemtime($f) : '';
    $canCrop = auth()->user()->can('edit users');
    $documents = [
        ($user->id_type === 'passport' ? 'Passport bio page' : 'National ID: front') => $user->id_front_path,
        'National ID: back' => $user->id_type === 'passport' ? null : $user->id_back_path,
        'Passport photo' => $user->avatar,
        'Signature' => $user->signature,
        'Selfie: straight' => $user->selfie_center_path,
        'Selfie: turned left' => $user->selfie_left_path,
        'Selfie: turned right' => $user->selfie_right_path,
    ];
@endphp

@section('content')
<div class="container mx-auto px-4 py-6">
    <div class="mb-6 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <a href="{{ route('kyc-reviews.index', ['status' => $user->kyc_status]) }}" class="text-sm text-brand-500 hover:underline">&larr; KYC Reviews</a>
            <h1 class="mt-1 text-2xl font-semibold text-gray-800 dark:text-white">{{ $user->name }}</h1>
            <p class="text-sm text-gray-500 dark:text-gray-400">
                Submitted {{ optional($user->kyc_submitted_at)->format('j M Y, H:i') ?? '—' }}
                @if ($loanHistory->sum() > 0)
                    · <span class="font-medium text-gray-700 dark:text-gray-300">Existing client:</span>
                    {{ $loanHistory->map(fn ($n, $s) => $n . ' ' . str_replace('_', ' ', $s))->implode(', ') }}
                @else
                    · New client
                @endif
            </p>
        </div>
        <span class="inline-flex w-fit items-center rounded-full px-3 py-1 text-sm font-medium {{ $badge }}">
            {{ \App\Http\Controllers\KycReviewController::STATUSES[$user->kyc_status] ?? ucfirst($user->kyc_status) }}
        </span>
    </div>

    <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
        <div class="space-y-6 lg:col-span-2">
            {{-- personal --}}
            <div class="{{ $card }}">
                <h2 class="mb-4 text-lg font-semibold text-gray-800 dark:text-white/90">Personal details</h2>
                <dl class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <div><dt class="{{ $label }}">Phone</dt><dd class="{{ $value }}">{{ $user->phone }}</dd></div>
                    <div><dt class="{{ $label }}">Email</dt><dd class="{{ $value }}">{{ $user->email }}</dd></div>
                    <div><dt class="{{ $label }}">{{ $user->id_type === 'passport' ? 'Passport' : 'National ID' }}</dt><dd class="{{ $value }}">{{ $user->id_number ?? '—' }}</dd></div>
                    <div><dt class="{{ $label }}">KRA PIN</dt><dd class="{{ $value }}">{{ $user->kra_pin ?? '—' }}</dd></div>
                    <div><dt class="{{ $label }}">Date of birth</dt><dd class="{{ $value }}">{{ $user->dob ? $user->dob->format('j M Y') . ' (' . $user->dob->age . ' yrs)' : '—' }}</dd></div>
                    <div><dt class="{{ $label }}">Address</dt><dd class="{{ $value }}">{{ $address ? trim($address->address_line_1 . ', ' . $address->state, ', ') : '—' }}</dd></div>
                </dl>
            </div>

            {{-- documents --}}
            <div class="{{ $card }}">
                <h2 class="mb-4 text-lg font-semibold text-gray-800 dark:text-white/90">Documents</h2>
                <div class="grid grid-cols-2 gap-4 sm:grid-cols-4">
                    @foreach ($documents as $name => $path)
                        @continue($name === 'National ID: back' && $user->id_type === 'passport')
                        <div>
                            <p class="{{ $label }} mb-1">{{ $name }}</p>
                            @if ($path)
                                <a href="{{ $doc($path) }}{{ $ver($path) }}" target="_blank" rel="noopener"
                                   class="block overflow-hidden rounded-lg border border-gray-200 bg-gray-50 dark:border-gray-700 dark:bg-gray-800">
                                    @if ($isPdf($path))
                                        <span class="flex h-28 items-center justify-center text-sm font-medium text-brand-500">Open PDF</span>
                                    @else
                                        <img src="{{ $doc($path) }}{{ $ver($path) }}" alt="{{ $name }}" class="h-28 w-full object-contain">
                                    @endif
                                </a>
                                @if ($canCrop && ! $isPdf($path) && ($key = array_search($path, $docKeys, true)))
                                    @php $abs = \App\Support\PrivateFiles::absolute($path); @endphp
                                    <button type="button" class="mt-1.5 inline-flex items-center gap-1 text-xs font-medium text-brand-500 hover:underline"
                                            @click="$dispatch('open-crop', {src: @js($doc($path)), action: @js(route('kyc-reviews.crop', $user)), document: @js($key), original: @js(\App\Services\Kyc\ImageCrop::hasOriginal($abs))})">
                                        <x-ui.icon name="edit" class="size-3.5" /> Crop
                                    </button>
                                @endif
                            @else
                                <span class="flex h-28 items-center justify-center rounded-lg border border-dashed border-gray-300 text-xs text-gray-400 dark:border-gray-700">Missing</span>
                            @endif
                        </div>
                    @endforeach
                </div>
            </div>

            {{-- income & payout --}}
            <div class="{{ $card }}">
                <h2 class="mb-4 text-lg font-semibold text-gray-800 dark:text-white/90">Income &amp; payout</h2>
                <dl class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <div><dt class="{{ $label }}">Employment</dt><dd class="{{ $value }}">{{ $user->borrower?->formatted_income_type ?? '—' }}</dd></div>
                    <div><dt class="{{ $label }}">Employer / business</dt><dd class="{{ $value }}">{{ $user->borrower?->employer_name ?? '—' }}{{ $user->borrower?->job_title ? ' · ' . $user->borrower->job_title : '' }}</dd></div>
                    <div><dt class="{{ $label }}">Monthly net income</dt><dd class="{{ $value }}">{{ $user->borrower?->net_salary ? 'KES ' . number_format($user->borrower->net_salary) : '—' }}</dd></div>
                    <div><dt class="{{ $label }}">M-Pesa</dt><dd class="{{ $value }}">{{ $mpesa?->mobile_number ?? '—' }}</dd></div>
                    @if ($bank)
                        <div><dt class="{{ $label }}">Bank</dt><dd class="{{ $value }}">{{ $bank->bank_name }} · {{ $bank->account_number }}</dd></div>
                    @endif
                </dl>
            </div>
        </div>

        <div class="space-y-6">
            {{-- people to call --}}
            <div class="{{ $card }}">
                <h2 class="mb-1 text-lg font-semibold text-gray-800 dark:text-white/90">Call to confirm</h2>
                <p class="mb-4 text-xs text-gray-500 dark:text-gray-400">Confirm they know {{ strtok($user->name, ' ') }} and the details match.</p>
                <div class="space-y-4">
                    <div class="rounded-xl bg-gray-50 p-4 dark:bg-gray-800/60">
                        <p class="{{ $label }}">Next of kin · {{ $user->kin_relation ?? '—' }}</p>
                        <p class="{{ $value }}">{{ $user->kin_name ?? 'Not provided' }}</p>
                        @if ($user->kin_phone)
                            <a href="tel:{{ $user->kin_phone }}" class="mt-1 inline-block text-sm font-medium text-brand-500 hover:underline">{{ $user->kin_phone }}</a>
                        @endif
                    </div>
                    <div class="rounded-xl bg-gray-50 p-4 dark:bg-gray-800/60">
                        <p class="{{ $label }}">Guarantor</p>
                        <p class="{{ $value }}">{{ $guarantor?->name ?? 'Not provided' }}</p>
                        @if ($guarantor?->phone)
                            <a href="tel:{{ $guarantor->phone }}" class="mt-1 inline-block text-sm font-medium text-brand-500 hover:underline">{{ $guarantor->phone }}</a>
                        @endif
                    </div>
                </div>
            </div>

            {{-- decision --}}
            <div class="{{ $card }}">
                @if ($user->kyc_status === 'submitted')
                    <h2 class="mb-4 text-lg font-semibold text-gray-800 dark:text-white/90">Decision</h2>
                    @if ($errors->any())
                        <div class="mb-4 rounded-lg bg-red-50 p-3 text-sm text-red-700 dark:bg-red-900/20 dark:text-red-300">{{ $errors->first() }}</div>
                    @endif
                    <form method="POST" x-data="{ action: '' }" :action="action" class="space-y-4">
                        @csrf
                        <label class="flex items-start gap-3 text-sm text-gray-700 dark:text-gray-300">
                            <input type="checkbox" name="called_next_of_kin" value="1" class="mt-0.5 h-4 w-4 rounded border-gray-300" @checked(old('called_next_of_kin'))>
                            I called the next of kin and they confirmed
                        </label>
                        <label class="flex items-start gap-3 text-sm text-gray-700 dark:text-gray-300">
                            <input type="checkbox" name="called_guarantor" value="1" class="mt-0.5 h-4 w-4 rounded border-gray-300" @checked(old('called_guarantor'))>
                            I called the guarantor and they agreed to vouch
                        </label>
                        <div>
                            <label for="notes" class="{{ $label }}">Notes (required to reject; the borrower sees them)</label>
                            <textarea id="notes" name="notes" rows="3"
                                      class="mt-1 w-full rounded-lg border border-gray-300 bg-transparent px-3 py-2 text-sm text-gray-800 focus:border-brand-300 focus:outline-hidden focus:ring-3 focus:ring-brand-500/10 dark:border-gray-700 dark:text-white/90">{{ old('notes') }}</textarea>
                        </div>
                        <div class="flex gap-3">
                            <button type="submit" @click="action = '{{ route('kyc-reviews.approve', $user) }}'"
                                    class="flex-1 rounded-lg bg-green-600 px-4 py-2.5 text-sm font-medium text-white hover:bg-green-700">Approve</button>
                            <button type="submit" @click="action = '{{ route('kyc-reviews.reject', $user) }}'"
                                    class="flex-1 rounded-lg border border-red-300 px-4 py-2.5 text-sm font-medium text-red-600 hover:bg-red-50 dark:border-red-800 dark:hover:bg-red-900/20">Reject</button>
                        </div>
                    </form>
                @else
                    <h2 class="mb-3 text-lg font-semibold text-gray-800 dark:text-white/90">Outcome</h2>
                    <dl class="space-y-3">
                        <div><dt class="{{ $label }}">Reviewed by</dt><dd class="{{ $value }}">{{ $reviewer?->name ?? '—' }}, {{ optional($user->kyc_reviewed_at)->format('j M Y, H:i') }}</dd></div>
                        <div><dt class="{{ $label }}">Calls made</dt>
                            <dd class="{{ $value }}">
                                Next of kin <x-ui.icon :name="($user->kyc_checks['next_of_kin'] ?? false) ? 'check' : 'x'" class="inline-block size-4 align-[-3px]" /> ·
                                Guarantor <x-ui.icon :name="($user->kyc_checks['guarantor'] ?? false) ? 'check' : 'x'" class="inline-block size-4 align-[-3px]" />
                            </dd></div>
                        <div><dt class="{{ $label }}">Notes</dt><dd class="{{ $value }}">{{ $user->kyc_review_notes ?: '—' }}</dd></div>
                    </dl>
                @endif
            </div>
        </div>
    </div>
</div>

@include('partials.modal.flash-alert')
    @include('partials.ui.crop-modal')
@endsection
