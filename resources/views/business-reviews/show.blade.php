@extends('layouts.app')

@php
    $card = 'rounded-2xl border border-gray-200 bg-white p-5 dark:border-gray-800 dark:bg-white/[0.03]';
    $label = 'text-xs text-gray-500 dark:text-gray-400';
    $value = 'text-sm font-medium text-gray-800 dark:text-white/90';
    $pct = fn ($n) => rtrim(rtrim(number_format((float) $n, 2), '0'), '.') . '%';
    $total = $business->ownershipTotal();
    $canDecide = $business->status === 'submitted' && auth()->user()->can('edit users');
    $owner = $business->owner;
@endphp

@section('content')
<div class="container mx-auto px-4 py-6">
    <div class="mb-6 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <div class="min-w-0">
            <a href="{{ route('business-reviews.index', ['status' => $business->status]) }}" class="text-sm text-brand-500 hover:underline">&larr; Business reviews</a>
            <h1 class="mt-1 text-2xl font-semibold text-gray-800 dark:text-white">{{ $business->legal_name }}</h1>
            <p class="text-sm text-gray-500 dark:text-gray-400">
                {{ $business->reference() }}{{ $business->trading_name ? ' · t/a ' . $business->trading_name : '' }}
                · Submitted {{ optional($business->submitted_at)->format('j M Y, H:i') ?? '—' }}
                @if ($business->handle) · <span class="font-outfit">@<span>{{ $business->handle }}</span></span>@endif
            </p>
        </div>
        @include('business-reviews._status', ['status' => $business->status])
    </div>

    @if ($duplicates->isNotEmpty())
        <x-staff.alert type="warn" title="Same registration number on another business" class="mb-6">
            @foreach ($duplicates as $d)
                <a class="underline" href="{{ route('business-reviews.show', $d) }}">{{ $d->legal_name }}</a> ({{ $d->reference() }}, {{ $d->owner?->name }}, {{ strtolower(\App\Http\Controllers\BusinessReviewController::STATUSES[$d->status] ?? $d->status) }}){{ $loop->last ? '' : '; ' }}
            @endforeach
        </x-staff.alert>
    @endif

    <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
        <div class="min-w-0 space-y-6 lg:col-span-2">
            <div class="{{ $card }}">
                <h2 class="mb-4 text-lg font-semibold text-gray-800 dark:text-white/90">Business details</h2>
                <dl class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <div><dt class="{{ $label }}">Type</dt><dd class="{{ $value }}">{{ \App\Models\Business::REGISTRATION_TYPES[$business->registration_type] ?? '—' }}</dd></div>
                    <div><dt class="{{ $label }}">Registration number</dt><dd class="{{ $value }} font-outfit">{{ $business->registration_number ?? '—' }}</dd></div>
                    <div><dt class="{{ $label }}">KRA PIN</dt><dd class="{{ $value }} font-outfit">{{ $business->kra_pin ?? '—' }}</dd></div>
                    <div><dt class="{{ $label }}">Registered</dt><dd class="{{ $value }}">{{ optional($business->date_registered)->format('j M Y') ?? '—' }}{{ $business->date_registered ? ' (' . $business->date_registered->diffForHumans(null, true) . ')' : '' }}</dd></div>
                    <div><dt class="{{ $label }}">Industry</dt><dd class="{{ $value }}">{{ $business->industry ?? '—' }}</dd></div>
                    <div><dt class="{{ $label }}">Address</dt><dd class="{{ $value }}">{{ collect([$business->address_line_1, $business->address_line_2, $business->city, $business->county, $business->postal_code])->filter()->unique()->implode(', ') ?: '—' }}</dd></div>
                    <div><dt class="{{ $label }}">Phone / email</dt><dd class="{{ $value }}">{{ $business->phone ?? '—' }}{{ $business->email ? ' · ' . $business->email : '' }}</dd></div>
                    <div><dt class="{{ $label }}">Website</dt><dd class="{{ $value }} break-all">{{ $business->website ?? '—' }}</dd></div>
                    <div><dt class="{{ $label }}">Monthly revenue</dt><dd class="{{ $value }}">@if ($business->monthly_revenue !== null)<x-staff.money :amount="$business->monthly_revenue" />@else — @endif</dd></div>
                    <div><dt class="{{ $label }}">Employees</dt><dd class="{{ $value }}">{{ $business->employees ?? '—' }}</dd></div>
                    <div class="sm:col-span-2"><dt class="{{ $label }}">Bank</dt><dd class="{{ $value }}">{{ $business->bank_name ?? '—' }}{{ $business->bank_account ? ' · ' . $business->bank_account : '' }}</dd></div>
                </dl>
            </div>

            <div class="{{ $card }}">
                <div class="mb-4 flex items-center justify-between gap-3">
                    <h2 class="text-lg font-semibold text-gray-800 dark:text-white/90">Directors &amp; owners</h2>
                    <span class="text-sm {{ $total > 100 ? 'text-error-500' : 'text-gray-500' }} font-outfit">{{ $pct($total) }} listed</span>
                </div>
                <x-staff.table :headers="['Person', 'Role', ['Owns', 'num'], 'KYC']" caption="Directors and owners">
                    @foreach ($business->members as $m)
                        <tr>
                            <td>
                                <p class="font-medium text-gray-800 dark:text-white/90">{{ $m->full_name }}{{ $m->user_id === $business->owner_user_id ? ' (applicant)' : '' }}</p>
                                <p class="font-outfit text-xs text-gray-500">ID {{ $m->id_number ?? '—' }}{{ $m->kra_pin ? ' · ' . $m->kra_pin : '' }}{{ $m->phone ? ' · ' . $m->phone : '' }}</p>
                            </td>
                            <td>{{ $m->roleLabel() }}{{ $m->is_signatory ? ' · signs' : '' }}</td>
                            <td class="num">{{ $pct($m->ownership_pct) }}</td>
                            <td>
                                @if ($m->user && $m->user->kyc_status === 'approved')
                                    @can('view users')<a href="{{ route('kyc-reviews.show', $m->user) }}" class="hover:underline">@endcan<x-staff.status-badge status="repaid" label="Verified" />@can('view users')</a>@endcan
                                @else
                                    <x-staff.status-badge status="pending" label="Not verified" />
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </x-staff.table>
                <div class="mt-4 space-y-2">
                    @foreach ($business->members as $m)
                        <div class="flex items-center gap-3 text-sm">
                            <span class="w-36 truncate text-gray-700 dark:text-gray-300">{{ $m->full_name }}</span>
                            <span class="h-2 flex-1 overflow-hidden rounded-full bg-gray-100 dark:bg-gray-800"><span class="block h-2 rounded-full bg-az-ink-blue" style="width: {{ min(100, (float) $m->ownership_pct) }}%"></span></span>
                            <span class="w-14 text-right font-outfit text-gray-700 dark:text-gray-300">{{ $pct($m->ownership_pct) }}</span>
                        </div>
                    @endforeach
                </div>
            </div>

            <div class="{{ $card }}">
                <h2 class="mb-4 text-lg font-semibold text-gray-800 dark:text-white/90">Documents</h2>
                @if ($missing)
                    <x-staff.alert type="err" title="Missing required documents" class="mb-4">
                        {{ collect($missing)->map(fn ($t) => \App\Models\BusinessDocument::TYPES[$t][0])->implode(', ') }}
                    </x-staff.alert>
                @endif
                <div class="grid grid-cols-2 gap-4 sm:grid-cols-3">
                    @forelse ($business->documents as $doc)
                        <div class="min-w-0">
                            <p class="{{ $label }} mb-1 truncate">{{ $doc->label() }}</p>
                            <a href="{{ $doc->url() }}" target="_blank" rel="noopener"
                               class="block overflow-hidden rounded-lg border border-gray-200 bg-gray-50 dark:border-gray-700 dark:bg-gray-800">
                                @if ($doc->isImage())
                                    <img src="{{ $doc->url() }}" alt="{{ $doc->label() }}" class="h-32 w-full object-contain">
                                @else
                                    <span class="flex h-32 flex-col items-center justify-center gap-1 text-sm font-medium text-brand-500">
                                        <svg class="h-7 w-7" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" aria-hidden="true"><path d="M7 3h7l5 5v13H7zM14 3v5h5" /></svg>
                                        Open PDF
                                    </span>
                                @endif
                            </a>
                            <p class="mt-1 truncate text-xs text-gray-500" title="{{ $doc->original_name }}">{{ $doc->original_name }} · {{ max(1, round(($doc->size ?? 0) / 1024)) }} KB</p>
                        </div>
                    @empty
                        <p class="col-span-full text-sm text-gray-500">No documents uploaded.</p>
                    @endforelse
                </div>
            </div>
        </div>

        <div class="min-w-0 space-y-6">
            <div class="{{ $card }}">
                <h2 class="mb-1 text-lg font-semibold text-gray-800 dark:text-white/90">Applicant</h2>
                <p class="{{ $value }}">{{ $owner?->name ?? '—' }}</p>
                <p class="text-sm text-gray-500">{{ $owner?->email }}</p>
                @if ($owner?->phone)<a href="tel:{{ $owner->phone }}" class="mt-1 inline-block text-sm font-medium text-brand-500 hover:underline">{{ $owner->phone }}</a>@endif
                <p class="mt-2 text-xs text-gray-500">Individual KYC: {{ ['approved' => 'approved', 'submitted' => 'waiting', 'rejected' => 'rejected'][$owner?->kyc_status] ?? 'not done' }}
                    @if ($owner && $owner->kyc_status)· <a class="text-brand-500 hover:underline" href="{{ route('kyc-reviews.show', $owner) }}">open</a>@endif</p>
            </div>

            <div class="{{ $card }}">
                @if ($canDecide)
                    <h2 class="mb-4 text-lg font-semibold text-gray-800 dark:text-white/90">Decision</h2>
                    @if ($errors->any())
                        <x-staff.alert type="err" class="mb-4">{{ $errors->first() }}</x-staff.alert>
                    @endif
                    <form method="POST" x-data="{ action: '' }" :action="action" class="space-y-4">
                        @csrf
                        @foreach (\App\Models\Business::CHECKS as $key => $text)
                            <label class="flex items-start gap-3 text-sm text-gray-700 dark:text-gray-300">
                                <input type="checkbox" name="checks[{{ $key }}]" value="1" class="mt-0.5 h-4 w-4 rounded border-gray-300" @checked(old("checks.$key"))>
                                {{ $text }}
                            </label>
                        @endforeach
                        <x-staff.field name="notes" type="textarea" label="Notes (required to reject; the borrower sees them)" rows="3" />
                        <div class="flex gap-3">
                            <button type="submit" @click="action = '{{ route('business-reviews.approve', $business) }}'"
                                    class="flex-1 rounded-lg bg-green-600 px-4 py-2.5 text-sm font-medium text-white hover:bg-green-700">Approve</button>
                            <button type="submit" @click="action = '{{ route('business-reviews.reject', $business) }}'"
                                    class="flex-1 rounded-lg border border-red-300 px-4 py-2.5 text-sm font-medium text-red-600 hover:bg-red-50 dark:border-red-800 dark:hover:bg-red-900/20">Reject</button>
                        </div>
                    </form>
                @elseif ($business->status === 'submitted')
                    <h2 class="mb-2 text-lg font-semibold text-gray-800 dark:text-white/90">Decision</h2>
                    <p class="text-sm text-gray-500">You can view this business. Approving or rejecting needs the <em>edit users</em> permission.</p>
                @elseif ($business->status === 'draft')
                    <h2 class="mb-2 text-lg font-semibold text-gray-800 dark:text-white/90">Not submitted yet</h2>
                    <p class="text-sm text-gray-500">The borrower is still filling this in. Last saved {{ $business->updated_at->diffForHumans() }}.</p>
                @else
                    <h2 class="mb-3 text-lg font-semibold text-gray-800 dark:text-white/90">Outcome</h2>
                    <dl class="space-y-3">
                        <div><dt class="{{ $label }}">Reviewed by</dt><dd class="{{ $value }}">{{ $business->reviewer?->name ?? '—' }}, {{ optional($business->reviewed_at)->format('j M Y, H:i') }}</dd></div>
                        <div><dt class="{{ $label }}">Checks</dt>
                            <dd class="space-y-1 text-sm text-gray-700 dark:text-gray-300">
                                @foreach (\App\Models\Business::CHECKS as $key => $text)
                                    <p><x-ui.icon :name="($business->checks[$key] ?? false) ? 'check' : 'x'" class="inline-block size-4 align-[-3px]" /> {{ $text }}</p>
                                @endforeach
                            </dd></div>
                        <div><dt class="{{ $label }}">Notes</dt><dd class="{{ $value }}">{{ $business->review_notes ?: '—' }}</dd></div>
                    </dl>
                @endif
            </div>

            <x-staff.placeholder dependency="AML / sanctions and PEP screening provider" title="AML screening">Screen the business and every director against sanctions and PEP lists before approval.</x-staff.placeholder>
            <x-staff.placeholder dependency="business loan products, limits and trade finance" title="Business limit">Set a business credit limit and open trade finance or asset-backed products.</x-staff.placeholder>
        </div>
    </div>
</div>

@include('partials.modal.flash-alert')
@endsection
