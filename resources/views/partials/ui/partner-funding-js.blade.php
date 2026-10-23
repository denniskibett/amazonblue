{{-- Shared by the disburse form and the loan partner links page (owner, 23 Oct 2026): funding mode, term and the
     M-Pesa SMS reader (POST staff.mpesa.read-sms). Include it ABOVE the x-data that uses it. --}}
  <script>
    // inline (not pushed) so it also works when the page opens in the side drawer
    window.disburseFunding = function (defaults) {
      return {
        defaults, mode: defaults.mode || 'house', house: defaults.house, sms: '', amount: defaults.amount || '', code: defaults.code || '', date: defaults.date || '',
        term: defaults.term || 'short_term', maturity: defaults.maturity, readMsg: '', readOk: false,
        async read() {
          if (!this.sms.trim()) { this.readMsg = ''; return; }
          try {
            const res = await fetch(defaults.read, { method: 'POST', credentials: 'same-origin',
              headers: { 'Accept': 'application/json', 'Content-Type': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]')?.content },
              body: JSON.stringify({ sms: this.sms }) });
            const body = await res.json();
            if (!body.ok) { this.readOk = false; this.readMsg = body.message; return; }
            const s = body.sms;
            this.amount = s.amount; this.code = s.code; if (s.date) this.date = s.date;
            this.readOk = !body.used;
            this.readMsg = body.used ? s.code + ' has already been recorded as partner money.'
              : 'Read: ' + s.code + ' · KES ' + Number(s.amount).toLocaleString('en-KE', { minimumFractionDigits: 2 }) + (s.party ? ' · ' + s.party : '') + (s.at ? ' · ' + s.at : '') + '. Check it, then choose the partner.';
          } catch (e) { this.readOk = false; this.readMsg = 'Could not read it. Type the code and amount in.'; }
        },
      };
    };
  </script>
