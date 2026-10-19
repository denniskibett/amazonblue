{{-- /account/repay with no open loan (Concerns\BorrowerRepayment@accountRepayStart). --}}
@extends('home.layouts.account', ['title' => 'Repay', 'tab' => 'loans'])

@section('content')
  <div class="acct-head">
    <h1>Repay</h1>
    <p>Pay your loan by M-Pesa</p>
  </div>
  <x-az.empty title="You have no loan to repay" message="When you have an active loan, the amount due and how to pay it show here. Receipts for past payments are on each loan.">
    <a class="btn btn-primary" href="{{ route('account.borrow') }}">Borrow</a>
    <a class="btn btn-ghost" href="{{ route('account.loans') }}">My loans</a>
  </x-az.empty>
@endsection
