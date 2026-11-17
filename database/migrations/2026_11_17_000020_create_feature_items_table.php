<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * The feature tracker (owner, 17 Nov 2026): what the app does today, what needs work and what is missing, kept by the
 * sysadmin on Features and suggestions. A suggestion can become a feature (suggestion_id). Starts with the list from
 * the 15 Nov audit and the 17 Nov funding review. SQL twin: database/sql/2026_11_17_features_and_suggestions.sql.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('feature_items')) {
            Schema::create('feature_items', function (Blueprint $t) {
                $t->bigIncrements('id');
                $t->string('area', 60);
                $t->string('name', 160);
                $t->string('status', 20)->default('improve')->comment('working, improve, missing');
                $t->string('priority', 10)->default('later')->comment('now, next, later');
                $t->text('shortcoming')->nullable();
                $t->foreignId('suggestion_id')->nullable()->constrained('suggestions')->nullOnDelete();
                $t->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
                $t->timestamps();
                $t->index(['status', 'priority']);
            });
        }
        if (DB::table('feature_items')->exists()) {
            return;
        }
        $rows = [
            ['Lending', 'Loan applications and approvals', 'working', 'later', ''],
            ['Lending', 'KYC and business reviews', 'working', 'later', ''],
            ['Lending', 'Loan products and calculator', 'working', 'later', ''],
            ['Lending', 'Add Disbursement (who is behind the loan)', 'improve', 'now', 'A payout from one source writes no loan_fundings row (11 on live). The sources table (transaction details first, whose money, broker row marked) ships with this release.'],
            ['Lending', 'Add Repayment (who it goes back to)', 'improve', 'now', 'Shows each source\'s share of the payment. \'Already took it\' (a partner kept their share) is not in the modal yet.'],
            ['Lending', 'Edit lock after 3 edits', 'missing', 'next', 'Designed: who is behind the loan and their shares lock after the 3rd edit, with a reason and change history.'],
            ['Lending', 'Rollovers, payment plans and grace days', 'working', 'later', ''],
            ['Lending', 'Collections and debt recovery', 'working', 'later', ''],
            ['Lending', 'Second sign-offs', 'working', 'later', ''],
            ['Partners & funding', 'One record of who funded each loan', 'improve', 'now', 'The same fact sits in 3 tables (disbursements.investment_id, investment_fundings, loan_fundings) that drift: 11 payouts missing rows, 2 pointing at another line.'],
            ['Partners & funding', 'Shares fixed at disbursement', 'missing', 'next', 'Shares are worked out again at every repayment from 5 rate sources.'],
            ['Partners & funding', 'Partners list', 'improve', 'next', 'Lent now read an empty wallet; Out on loans and Owed to partners cards ship with this release.'],
            ['Partners & funding', 'Partner page', 'improve', 'next', '8 wallet cards in two rows and 5 overlapping sheets. Design: one row of cards, three actions.'],
            ['Partners & funding', 'Partner portal', 'improve', 'next', 'About 10 pages. Should show their investments and when each matures, like a borrower\'s loan and due date.'],
            ['Partners & funding', 'Floats: money to lend per account', 'missing', 'next', 'No page shows what each account has free to lend.'],
            ['Partners & funding', 'Facility split (loan 458)', 'working', 'later', 'Its own mechanism; fold into loan_fundings later, keeping the 76,032 then 50% penalty rule.'],
            ['Partners & funding', 'Partner payouts', 'working', 'later', ''],
            ['Partners & funding', 'Delete partner', 'improve', 'later', 'The button has never worked: nothing listens for it and the server answers JSON.'],
            ['Partners & funding', 'Reconcile by reference', 'missing', 'later', 'Check each M-Pesa or bank reference against the wallets it moved.'],
            ['Partners & funding', 'Payments linked to the partner journal', 'improve', 'later', 'partner_transaction_id on repayments and disbursements is never filled; investment_tracker wallets all 0.'],
            ['Payments', 'M-Pesa payments and payouts', 'working', 'later', ''],
            ['Payments', 'Sharet Pay', 'working', 'later', ''],
            ['Payments', 'Wallets, reconciliation and end of day', 'working', 'later', ''],
            ['Reports', 'P&L, cash and portfolio', 'improve', 'next', 'Audit 15 Nov: deleted and merged loans counted in losses, cost of funds lists differ, unit economics always all-time.'],
            ['Reports', 'Lending analytics', 'improve', 'later', 'Slow first load, N+1 queries, no CSV export, missing empty states.'],
            ['Reports', 'Partners and funding report', 'missing', 'later', 'Earned vs paid vs owed per partner, fixed-return lines coming due.'],
            ['Reports', 'Portfolio at risk', 'missing', 'later', 'PAR by days late, by product and officer; recovery rate.'],
            ['Platform', 'HTTPS redirect and security headers', 'improve', 'now', 'http:// is not redirected; no HSTS, X-Frame-Options, nosniff or Referrer-Policy.'],
            ['Platform', 'Page speed', 'improve', 'later', '0.7-1.0 s before the first byte; check caches and OPcache on deploy.'],
            ['Platform', 'Server disk clean-up', 'improve', 'later', 'Disk 76%: weekly clean of old backups and releases.'],
            ['Platform', 'Staff mail', 'working', 'later', ''],
            ['Platform', 'Roles, permissions and audit log', 'working', 'later', ''],
            ['Platform', 'Deploy with tests, backup and rollback', 'working', 'later', ''],
            ['Platform', 'Suggestions from every page', 'working', 'later', 'Staff, borrowers and partners send ideas from the Suggest button; reviewed here.'],
        ];
        DB::table('feature_items')->insert(array_map(fn ($r) => ['area' => $r[0], 'name' => $r[1], 'status' => $r[2], 'priority' => $r[3],
            'shortcoming' => $r[4] !== '' ? $r[4] : null, 'created_at' => now(), 'updated_at' => now()], $rows));
    }

    public function down(): void
    {
        Schema::dropIfExists('feature_items');
    }
};
