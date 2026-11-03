# Live status, 20 October 2026 (moved from DEPLOY.md on 3 Nov 2026)

History only. Item 1 (cron) was done by the 23 Oct server read; the rest are tracked in [../CURRENT_STATE.md §10](../CURRENT_STATE.md#10-open-items-owner-and-server).


**Live:** `stage-2-ui` (`f7a5ef4`, Stage 4 Phase 0) was deployed with `scripts/push-live.sh`. Smoke test 200/200. Previous release: `releases/laravel_20261020_013126`. Before that: `a2c9732` (Stage 2); old agreement URLs return 404.

**Phase 0 on live (20 Oct):**

- Both `2026_10_20_*.sql` scripts ran. The broker has no `view users`. Only sysadmin (#113) can open `/system`.
- First `loans:check-integrity` run found 443 findings:
  - 6 repaid loans that still owe (#7, 97, 164, 253, 344, 502);
  - 434 repaid loans with an active cycle;
  - 3 repayments dated before their cycle (#369, 370, 450).
- The CSV is in `storage/app/private/reports/`.
- Restore test passed ([RETENTION.md](../stage4/RETENTION.md)).

**Confirmed on live:**

- `APP_DEBUG=false`.
- All SQL twins applied.
- Stage 1 data repairs done.
- `wallets:check` reconciles.
- SSH key login from the Mac works.

**Open (owner, then close Stage 2):**

1. **Cron:** `crontab -u loans4537` is empty, so the nightly status/NPL jobs don't run. Add `* * * * * cd /home/loans.sharet.africa/laravel && php artisan schedule:run >> /dev/null 2>&1` for `loans4537`.
2. **Email:** sent from `noreply@loans.sharet.africa`. Gmail accepts it but quarantines it (DMARC), because there is no SPF/DKIM. Add SPF, DKIM (CyberPanel › Email › DKIM Manager) and the `mail.loans` A record **at Olitt**, not CyberPanel DNS.
3. **`.env`:** remove the duplicate `LOG_STACK=single` (the first value wins) and add `LOG_DAILY_DAYS=30`.
4. **Secrets:** rotate `DB_PASSWORD`, which was pasted in chat.
5. **sysadmin:** set a strong password and confirm `getRoleNames()` = `["super-admin"]`.
6. **Browser checks:** `/system` funding card (sysadmin), Business Reviews in the staff sidebar, Repay for a borrower.
7. **Later:** Ubuntu 20.04 is past standard support (enable Ubuntu Pro or upgrade); the server has 2 GB RAM with swap half used.
