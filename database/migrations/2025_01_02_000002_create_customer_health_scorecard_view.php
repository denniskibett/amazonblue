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
        if (Schema::hasView('customer_health_scorecard')) {
            return;
        }

        DB::statement("CREATE VIEW `customer_health_scorecard` AS select `b`.`user_id` AS `user_id`,`u`.`name` AS `name`,`u`.`phone` AS `phone`,count(distinct `l`.`id`) AS `total_loans`,sum(`l`.`amount`) AS `total_principal_borrowed`,sum((case when (`r`.`transaction` = 'ROLL OVER') then 1 else 0 end)) AS `count_rollovers`,sum((case when (`r`.`transaction` = 'CREDIT DISCOUNT') then 1 else 0 end)) AS `count_discounts`,sum((case when (`r`.`transaction` = 'BAD DEBT') then 1 else 0 end)) AS `count_bad_debts`,(((100 - sum((case when (`r`.`transaction` = 'ROLL OVER') then 5 else 0 end))) - sum((case when (`r`.`transaction` = 'CREDIT DISCOUNT') then 15 else 0 end))) - sum((case when (`r`.`transaction` = 'BAD DEBT') then 30 else 0 end))) AS `health_score`,(case when ((100 - ((sum((case when (`r`.`transaction` = 'ROLL OVER') then 5 else 0 end)) - sum((case when (`r`.`transaction` = 'CREDIT DISCOUNT') then 15 else 0 end))) - sum((case when (`r`.`transaction` = 'BAD DEBT') then 30 else 0 end)))) >= 80) then 'A (Low Risk)' when ((100 - ((sum((case when (`r`.`transaction` = 'ROLL OVER') then 5 else 0 end)) - sum((case when (`r`.`transaction` = 'CREDIT DISCOUNT') then 15 else 0 end))) - sum((case when (`r`.`transaction` = 'BAD DEBT') then 30 else 0 end)))) >= 60) then 'B (Medium Risk)' when ((100 - ((sum((case when (`r`.`transaction` = 'ROLL OVER') then 5 else 0 end)) - sum((case when (`r`.`transaction` = 'CREDIT DISCOUNT') then 15 else 0 end))) - sum((case when (`r`.`transaction` = 'BAD DEBT') then 30 else 0 end)))) >= 40) then 'C (High Risk)' else 'D (Very High Risk)' end) AS `health_grade`,`b`.`status` AS `is_borrower_active` from (((`borrowers` `b` join `users` `u` on((`u`.`id` = `b`.`user_id`))) left join `loans` `l` on((`l`.`user_id` = `u`.`id`))) left join `repayments` `r` on((`r`.`loan_id` = `l`.`id`))) group by `b`.`user_id`,`u`.`name`,`u`.`phone`,`b`.`status` order by (((100 - sum((case when (`r`.`transaction` = 'ROLL OVER') then 5 else 0 end))) - sum((case when (`r`.`transaction` = 'CREDIT DISCOUNT') then 15 else 0 end))) - sum((case when (`r`.`transaction` = 'BAD DEBT') then 30 else 0 end)))");
    }

    public function down(): void
    {
        DB::statement("DROP VIEW IF EXISTS `customer_health_scorecard`");
    }
};
