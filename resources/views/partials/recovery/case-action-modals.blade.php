{{-- resources/views/partials/recovery/case-action-modals.blade.php
     "Add action" and "Write off" dialogs for cases/show. They open on the window events the page
     dispatches (open-add-action / open-write-off) and post plain forms to cases.action.add and
     cases.write-off, which redirect back to the case with a flash message.
     An amount collected is posted as a real Repayment (mode "recovery") with the reference entered here. --}}
@php
    $caseActionTypes = \App\Models\ActionType::orderBy('name')->get(['id', 'name']);
    $case = $caseData; $caseLoan = $case->loan ?? null;
@endphp

{{-- Add action --}}
<div x-data="{ open: {{ $errors->hasAny(['action_type_id','action_date','amount_collected','collection_reference']) ? 'true' : 'false' }}, collected: '{{ old('amount_collected') }}' }"
     @open-add-action.window="open = true"
     @keydown.escape.window="open = false"
     x-show="open" x-cloak style="display: none;"
     class="fixed inset-0 z-[99999] flex items-center justify-center p-4">
    <div class="fixed inset-0 bg-black/50" @click="open = false"></div>
    <div class="relative w-full max-w-2xl max-h-[90vh] overflow-y-auto rounded-2xl bg-white dark:bg-gray-900 shadow-xl">
        <form method="POST" action="{{ route('cases.action.add', $case->id) }}">
            @csrf
            <div class="flex items-center justify-between border-b border-gray-200 dark:border-gray-700 px-6 py-4">
                <h3 class="text-lg font-semibold text-gray-800 dark:text-white">Add recovery action</h3>
                <button type="button" @click="open = false" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-200">&times;</button>
            </div>

            <div class="grid grid-cols-1 gap-4 px-6 py-5 sm:grid-cols-2">
                @if($errors->any())
                    <div class="sm:col-span-2 rounded-lg bg-red-50 p-3 text-sm text-red-700 dark:bg-red-900/30 dark:text-red-300">
                        @foreach($errors->all() as $error)<div>{{ $error }}</div>@endforeach
                    </div>
                @endif

                <div>
                    <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">Action type *</label>
                    <select name="action_type_id" required class="h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 text-sm dark:border-gray-700 dark:bg-gray-900 dark:text-white">
                        <option value="">Select</option>
                        @foreach($caseActionTypes as $type)
                            <option value="{{ $type->id }}" @selected(old('action_type_id') == $type->id)>{{ $type->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">Date *</label>
                    <input type="date" name="action_date" required value="{{ old('action_date', now()->toDateString()) }}"
                           class="h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 text-sm dark:border-gray-700 dark:bg-gray-900 dark:text-white">
                </div>
                <div>
                    <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">Outcome</label>
                    <select name="outcome" class="h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 text-sm dark:border-gray-700 dark:bg-gray-900 dark:text-white">
                        @foreach(['pending' => 'Pending', 'successful' => 'Successful', 'partial' => 'Partial', 'promise_to_pay' => 'Promise to pay', 'no_answer' => 'No answer', 'wrong_number' => 'Wrong number', 'refused' => 'Refused', 'failed' => 'Failed'] as $value => $label)
                            <option value="{{ $value }}" @selected(old('outcome', 'pending') === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">Contact person</label>
                    <input type="text" name="contact_person" value="{{ old('contact_person') }}" maxlength="255"
                           class="h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 text-sm dark:border-gray-700 dark:bg-gray-900 dark:text-white">
                </div>
                <div>
                    <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">Promised amount</label>
                    <input type="number" step="0.01" min="0" name="promised_amount" value="{{ old('promised_amount') }}"
                           class="h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 text-sm dark:border-gray-700 dark:bg-gray-900 dark:text-white">
                </div>
                <div>
                    <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">Promised date</label>
                    <input type="date" name="promised_date" value="{{ old('promised_date') }}"
                           class="h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 text-sm dark:border-gray-700 dark:bg-gray-900 dark:text-white">
                </div>

                <div class="sm:col-span-2 rounded-lg border border-gray-200 p-4 dark:border-gray-700">
                    <p class="mb-3 text-sm text-gray-600 dark:text-gray-400">
                        Money received? It is posted as a repayment on
                        {{ $caseLoan ? 'loan #' . $caseLoan->id : 'the loan' }} (mode "recovery") and moves the borrower's balance.
                    </p>
                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <div>
                            <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">Amount collected</label>
                            <input type="number" step="0.01" min="0" name="amount_collected" x-model="collected"
                                   class="h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 text-sm dark:border-gray-700 dark:bg-gray-900 dark:text-white">
                        </div>
                        <div>
                            <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">Payment reference <span x-show="parseFloat(collected) > 0">*</span></label>
                            <input type="text" name="collection_reference" value="{{ old('collection_reference') }}" maxlength="255"
                                   :required="parseFloat(collected) > 0" placeholder="M-Pesa code, bank ref, receipt no."
                                   class="h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 text-sm dark:border-gray-700 dark:bg-gray-900 dark:text-white">
                        </div>
                    </div>
                </div>

                <div>
                    <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">Follow-up date</label>
                    <input type="date" name="follow_up_date" value="{{ old('follow_up_date') }}"
                           class="h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 text-sm dark:border-gray-700 dark:bg-gray-900 dark:text-white">
                </div>
                <div>
                    <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">Follow-up notes</label>
                    <input type="text" name="follow_up_notes" value="{{ old('follow_up_notes') }}"
                           class="h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 text-sm dark:border-gray-700 dark:bg-gray-900 dark:text-white">
                </div>
                <div class="sm:col-span-2">
                    <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">Notes</label>
                    <textarea name="notes" rows="3" class="w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm dark:border-gray-700 dark:bg-gray-900 dark:text-white">{{ old('notes') }}</textarea>
                </div>
            </div>

            <div class="flex justify-end gap-3 border-t border-gray-200 dark:border-gray-700 px-6 py-4">
                <button type="button" @click="open = false" class="rounded-lg border border-gray-300 px-4 py-2.5 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:text-gray-300 dark:hover:bg-gray-800">Cancel</button>
                <button type="submit" class="rounded-lg bg-blue-600 px-4 py-2.5 text-sm font-medium text-white hover:bg-blue-700">Save action</button>
            </div>
        </form>
    </div>
</div>

{{-- Write off --}}
<div x-data="{ open: {{ $errors->has('write_off_reason') ? 'true' : 'false' }} }"
     @open-write-off.window="open = true"
     @keydown.escape.window="open = false"
     x-show="open" x-cloak style="display: none;"
     class="fixed inset-0 z-[99999] flex items-center justify-center p-4">
    <div class="fixed inset-0 bg-black/50" @click="open = false"></div>
    <div class="relative w-full max-w-lg rounded-2xl bg-white dark:bg-gray-900 shadow-xl">
        <form method="POST" action="{{ route('cases.write-off', $case->id) }}">
            @csrf
            @method('PATCH')
            <div class="border-b border-gray-200 dark:border-gray-700 px-6 py-4">
                <h3 class="text-lg font-semibold text-gray-800 dark:text-white">Write off case {{ $case->case_number }}</h3>
            </div>
            <div class="space-y-4 px-6 py-5">
                <p class="text-sm text-gray-600 dark:text-gray-400">
                    The outstanding debt of <strong>KES {{ number_format((float) $case->total_debt_amount, 2) }}</strong> is written off.
                    The loan is marked <strong>written off</strong> (not repaid). No repayment is recorded. This is logged with your name.
                </p>
                <div>
                    <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">Reason *</label>
                    <textarea name="write_off_reason" rows="3" required maxlength="500"
                              class="w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm dark:border-gray-700 dark:bg-gray-900 dark:text-white">{{ old('write_off_reason') }}</textarea>
                    @error('write_off_reason')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                </div>
            </div>
            <div class="flex justify-end gap-3 border-t border-gray-200 dark:border-gray-700 px-6 py-4">
                <button type="button" @click="open = false" class="rounded-lg border border-gray-300 px-4 py-2.5 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:text-gray-300 dark:hover:bg-gray-800">Cancel</button>
                <button type="submit" class="rounded-lg bg-red-600 px-4 py-2.5 text-sm font-medium text-white hover:bg-red-700">Write off</button>
            </div>
        </form>
    </div>
</div>
