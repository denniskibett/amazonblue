{{-- The cards for a page, worked out for the signed-in user (App\Support\PageMetrics). <x-ui.page-metrics page="users" /> --}}
@props(['page', 'cols' => null])
<x-ui.metrics :items="\App\Support\PageMetrics::for($page, auth()->user())" :cols="$cols" />
