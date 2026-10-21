<form method="POST" action="{{ route('staff.operations-review.repair', $loan) }}" class="inline-flex items-center gap-1">@csrf
  <input type="hidden" name="action" value="{{ $action }}">
  @foreach ($extra as $k => $v)<input type="hidden" name="{{ $k }}" value="{{ $v }}">@endforeach
  <span class="text-xs text-gray-600 dark:text-gray-300">{{ $label }}:</span>
  <button name="preview" value="1" class="{{ $btn }}">Preview</button>
  <button class="{{ $cls }}">Apply</button>
</form>
