<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Views are MySQL SQL; other drivers skip them.
        if (! in_array(DB::getDriverName(), ['mysql', 'mariadb'], true)) {
            return;
        }

        // Existing databases already have this view: leave it untouched.
        if (Schema::hasView('portfolio_summary')) {
            return;
        }

        DB::statement("CREATE VIEW `portfolio_summary` AS select count(0) AS `total_loans_issued`,sum(`l`.`amount`) AS `total_principal_disbursed`,sum((case when (`l`.`status` = 'repaid') then `l`.`amount` else 0 end)) AS `total_principal_repaid`,sum((case when (`l`.`status` in ('pending','approved','disbursed')) then `l`.`amount` else 0 end)) AS `total_principal_outstanding`,sum((`l`.`amount` * (`lt`.`interest_rate` / 100))) AS `total_expected_interest`,((select sum(`repayments`.`amount`) from `repayments`) - sum((case when (`l`.`status` = 'repaid') then `l`.`amount` else 0 end))) AS `total_actual_revenue_approx`,(select sum(`repayments`.`amount`) from `repayments` where (`repayments`.`transaction` in ('BAD DEBT','CREDIT DISCOUNT'))) AS `total_write_offs` from (`loans` `l` join `loan_types` `lt` on((`l`.`loan_type_id` = `lt`.`id`)))");
    }

    public function down(): void
    {
        DB::statement("DROP VIEW IF EXISTS `portfolio_summary`");
    }
};
