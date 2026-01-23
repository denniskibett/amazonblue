@extends('layouts.app')

@section('content')

    <!-- Breadcrumb Start -->
    <div x-data="{ pageName: `User Management`}">
        @include('partials.breadcrumb')
    </div>
    <!-- Breadcrumb End -->

    @include('partials.table.table-users')


@endsection