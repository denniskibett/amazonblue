<form method="POST" action="{{ route('staff.operations-review.repair', $loan) }}" class="ml-auto inline-flex flex-wrap items-center gap-1 text-xs">@csrf
  <input type="hidden" name="action" value="move">
  <label for="move-to-{{ $loan }}" class="text-gray-600 dark:text-gray-300">Move receivable to loan #</label>
  <input id="move-to-{{ $loan }}" name="to" type="number" min="1" required class="w-20 rounded-md border-gray-300 py-1 text-xs dark:border-gray-700 dark:bg-gray-900">
  <label for="move-amt-{{ $loan }}" class="text-gray-600 dark:text-gray-300">KES</label>
  <input id="move-amt-{{ $loan }}" name="amount" type="number" step="0.01" min="0.01" value="{{ number_format($amount, 2, '.', '') }}" class="w-28 rounded-md border-gray-300 py-1 text-xs dark:border-gray-700 dark:bg-gray-900">
  <button name="preview" value="1" class="{{ $btn }}">Preview</button>
  <button class="{{ $btn }}">Apply</button>
</form>
