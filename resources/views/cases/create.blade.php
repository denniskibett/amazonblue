@extends('layouts.app')

@section('content')
@php
    $input = 'h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 text-sm text-gray-800 dark:border-gray-700 dark:bg-gray-900 dark:text-white';
    $label = 'mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300';
    // Plain arrays only (no Eloquent models into JS): borrower id/name/email + their open loans.
    $createBorrowers = collect($borrowers ?? [])->map(fn ($b) => [
        'id' => $b->id,
        'name' => $b->name,
        'email' => $b->email,
        'loans' => collect($b->loans ?? [])->map(fn ($l) => [
            'id' => $l->id,
            'amount' => (float) $l->amount,
            'status' => $l->status,
        ])->values(),
    ])->values();
    $openStatus = collect($statuses ?? [])->firstWhere('slug', 'open');
    $mediumPriority = collect($priorities ?? [])->firstWhere('slug', 'medium') ?? collect($priorities ?? [])->first();
@endphp
<div class="container mx-auto px-4 py-6" x-data="caseCreatePage(@js($createBorrowers))">
    <div class="flex justify-between items-center mb-4">
        <h2 class="text-2xl font-bold text-gray-800 dark:text-white">New recovery case</h2>
        <a href="{{ route('cases.index') }}" class="rounded-lg border border-gray-300 px-4 py-2.5 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:text-gray-300 dark:hover:bg-white/[0.03]">Back to cases</a>
    </div>

    <form x-ref="form" @submit.prevent="submit()" class="rounded-2xl border border-gray-200 bg-white p-6 dark:border-gray-800 dark:bg-white/[0.03]">
        <template x-if="errors.length">
            <div class="mb-4 rounded-lg bg-red-50 p-3 text-sm text-red-700 dark:bg-red-900/30 dark:text-red-300">
                <template x-for="e in errors" :key="e"><div x-text="e"></div></template>
            </div>
        </template>

        <div class="grid grid-cols-1 gap-5 md:grid-cols-3">
            <div class="md:col-span-2">
                <label class="{{ $label }}">Borrower *</label>
                <select name="user_id" x-model="userId" required class="{{ $input }}">
                    <option value="">Select borrower</option>
                    <template x-for="b in borrowers" :key="b.id">
                        <option :value="b.id" x-text="b.name + (b.email ? ' (' + b.email + ')' : '')"></option>
                    </template>
                </select>
            </div>
            <div>
                <label class="{{ $label }}">Loan</label>
                <select name="loan_id" class="{{ $input }}">
                    <option value="">No specific loan</option>
                    <template x-for="l in loans" :key="l.id">
                        <option :value="l.id" x-text="'#' + l.id + ' · KES ' + l.amount.toLocaleString() + ' · ' + l.status"></option>
                    </template>
                </select>
            </div>

            <div>
                <label class="{{ $label }}">Total debt (KES) *</label>
                <input type="number" step="0.01" min="0.01" name="total_debt_amount" required class="{{ $input }}">
            </div>
            <div>
                <label class="{{ $label }}">Principal outstanding</label>
                <input type="number" step="0.01" min="0" name="principal_outstanding" class="{{ $input }}">
            </div>
            <div>
                <label class="{{ $label }}">Interest outstanding</label>
                <input type="number" step="0.01" min="0" name="interest_outstanding" class="{{ $input }}">
            </div>
            <div>
                <label class="{{ $label }}">Penalty outstanding</label>
                <input type="number" step="0.01" min="0" name="penalty_outstanding" class="{{ $input }}">
            </div>
            <div>
                <label class="{{ $label }}">Fees outstanding</label>
                <input type="number" step="0.01" min="0" name="fees_outstanding" class="{{ $input }}">
            </div>
            <div>
                <label class="{{ $label }}">Default date *</label>
                <input type="date" name="default_date" required value="{{ now()->toDateString() }}" class="{{ $input }}">
            </div>

            <div>
                <label class="{{ $label }}">Status *</label>
                <select name="status_id" required class="{{ $input }}">
                    @foreach($statuses ?? [] as $status)
                        @continue(in_array($status->slug, ['written_off', 'recovered', 'closed']))
                        <option value="{{ $status->id }}" @selected($openStatus && $openStatus->id === $status->id)>{{ $status->name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="{{ $label }}">Priority *</label>
                <select name="priority_id" required class="{{ $input }}">
                    @foreach($priorities ?? [] as $priority)
                        <option value="{{ $priority->id }}" @selected($mediumPriority && $mediumPriority->id === $priority->id)>{{ $priority->name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="{{ $label }}">Assigned to</label>
                <select name="assigned_to" class="{{ $input }}">
                    <option value="">Unassigned</option>
                    @foreach($officers ?? [] as $officer)
                        <option value="{{ $officer->id }}">{{ $officer->name }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="{{ $label }}">First action</label>
                <select name="initial_action_type" class="{{ $input }}">
                    <option value="">None</option>
                    @foreach($actionTypes ?? [] as $type)
                        <option value="{{ $type->id }}">{{ $type->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="md:col-span-2">
                <label class="{{ $label }}">First action notes</label>
                <input type="text" name="initial_action_notes" class="{{ $input }}">
            </div>

            <div class="md:col-span-3">
                <label class="{{ $label }}">Recovery strategy</label>
                <textarea name="recovery_strategy" rows="3" class="w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 dark:border-gray-700 dark:bg-gray-900 dark:text-white"></textarea>
            </div>
            <div class="md:col-span-3">
                <label class="{{ $label }}">Notes</label>
                <textarea name="notes" rows="3" class="w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 dark:border-gray-700 dark:bg-gray-900 dark:text-white"></textarea>
            </div>
        </div>

        <div class="mt-6 flex justify-end gap-3">
            <a href="{{ route('cases.index') }}" class="rounded-lg border border-gray-300 px-4 py-2.5 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:text-gray-300 dark:hover:bg-white/[0.03]">Cancel</a>
            <button type="submit" :disabled="saving" class="rounded-lg bg-blue-600 px-4 py-2.5 text-sm font-medium text-white hover:bg-blue-700 disabled:opacity-60" x-text="saving ? 'Saving…' : 'Create case'"></button>
        </div>
    </form>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('alpine:init', () => {
    Alpine.data('caseCreatePage', (borrowers) => ({
        borrowers: borrowers || [],
        userId: '{{ request('user_id') }}',
        errors: [],
        saving: false,
        get loans() {
            const b = this.borrowers.find(x => String(x.id) === String(this.userId));
            return b ? b.loans : [];
        },
        async submit() {
            this.errors = [];
            this.saving = true;
            try {
                const res = await fetch(@js(route('cases.store')), {
                    method: 'POST',
                    headers: {
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    },
                    body: new FormData(this.$refs.form),
                });
                const data = await res.json().catch(() => ({}));
                if (res.ok && data.success && data.case) {
                    window.location = @js(url('cases')) + '/' + data.case.id;
                    return;
                }
                this.errors = data.errors ? Object.values(data.errors).flat() : [data.message || 'Could not create the case.'];
            } catch (e) {
                this.errors = ['Could not create the case. Check your connection and try again.'];
            } finally {
                this.saving = false;
            }
        },
    }));
});
</script>
@endpush
