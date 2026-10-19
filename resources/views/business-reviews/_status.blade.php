{{-- Business review status pill (colours from the loan status tokens). $status --}}
@php
  $tone = ['submitted' => 'pending', 'approved' => 'repaid', 'rejected' => 'defaulted', 'draft' => 'rejected'][$status] ?? 'rejected';
@endphp
<x-staff.status-badge :status="$tone" :label="\App\Http\Controllers\BusinessReviewController::STATUSES[$status] ?? ucfirst($status)" />
