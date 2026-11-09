{{-- Pay with Sharet Pay (9 Nov 2026): a Sharet pay page for the amount chosen above (inside the repay x-data:
     amount, valid, fmt). The payment shows under the loan once Sharet confirms it; nothing to type afterwards. --}}
<section class="a-card">
  <div class="a-sec-h"><h2>Pay with Sharet Pay</h2></div>
  <form method="POST" action="{{ route('account.repay.sharet', $loan) }}">
    @csrf
    <input type="hidden" name="amount" :value="amount" value="{{ $startAmount }}">
    <p class="muted rp-small">You go to a secure Sharet page to pay <b x-text="fmt(amount)">{{ $kes($startAmount) }}</b> by M-Pesa or your Sharet wallet. It shows under your loan's payments as soon as it is paid; there is no code to send us.</p>
    <button class="btn btn-primary btn-block" type="submit" :disabled="!valid" style="margin-top:14px">Pay with Sharet Pay</button>
  </form>
</section>
