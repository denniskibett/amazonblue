@extends('layouts.app')

@section('content')

    <x-ui.page-header title="User Management" sub="Borrowers, partners and staff, with their profile and loan standing." />
    <x-ui.page-metrics page="users" />

    @include('partials.table.table-users')


@endsection