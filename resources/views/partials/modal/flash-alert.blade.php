{{--
  The app's alert modal, opened for a flashed alert: redirect()->with('alert', ['type' => 'success|error|warning|info', 'title' => ..., 'message' => ...]).
  Include once per page (pages that already include partials.modal.alert-modal only need the script part).
--}}
@unless ($skipModal ?? false)
  @include('partials.modal.alert-modal')
@endunless
@if (session('alert'))
  <script>
    (function () {
      var shown = false;
      document.addEventListener('alpine:initialized', function () {
        if (shown || !window.showAlert) return;
        shown = true;
        var a = @json(session('alert'));
        window.showAlert(a.type, a.title, a.message);
      });
    })();
  </script>
@endif
