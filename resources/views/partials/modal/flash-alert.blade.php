{{--
  The app's alert modal, opened for a flashed alert: redirect()->with('alert', ['type' => 'success|error|warning|info', 'title' => ..., 'message' => ...]).
  Include once per page (pages that already include partials.modal.alert-modal only need the script part).
--}}
@unless ($skipModal ?? false)
  @include('partials.modal.alert-modal')
@endunless
{{-- the flashed alert itself is shown by partials.modal.toasts in the layout --}}
