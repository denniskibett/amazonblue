<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Views are MySQL SQL; other drivers (the SQLite test database) skip them.
        if (! in_array(DB::getDriverName(), ['mysql', 'mariadb'], true)) {
            return;
        }

        // Existing databases already have this view: leave it untouched.
        if (Schema::hasView('broker_performance_report')) {
            return;
        }

        DB::statement("CREATE VIEW `broker_performance_report` AS select `brok`.`user_id` AS `broker_user_id`,`u`.`name` AS `broker_name`,count(distinct `b`.`id`) AS `clients_referred`,count(distinct `l`.`id`) AS `loans_from_clients`,sum(`l`.`amount`) AS `total_principal_from_clients`,sum((case when (`r`.`transaction` = 'ROLL OVER') then 1 else 0 end)) AS `client_rollovers`,sum((case when (`r`.`transaction` = 'CREDIT DISCOUNT') then 1 else 0 end)) AS `client_discounts`,sum((case when (`r`.`transaction` = 'BAD DEBT') then 1 else 0 end)) AS `client_bad_debts`,sum(((`l`.`amount` * (`lt`.`interest_rate` / 100)) * (`brok`.`interest_broker` / 100))) AS `estimated_broker_interest_commission`,sum(((`l`.`amount` * (`lt`.`penalty_rate` / 100)) * (`brok`.`penalty_broker` / 100))) AS `estimated_broker_penalty_commission` from (((((`brokers` `brok` join `users` `u` on((`u`.`id` = `brok`.`user_id`))) left join `borrowers` `b` on((`b`.`broker_id` = `brok`.`id`))) left join `loans` `l` on((`l`.`user_id` = `b`.`user_id`))) left join `loan_types` `lt` on((`l`.`loan_type_id` = `lt`.`id`))) left join `repayments` `r` on((`r`.`loan_id` = `l`.`id`))) group by `brok`.`user_id`,`u`.`name` order by sum(`l`.`amount`) desc");
    }

    public function down(): void
    {
        DB::statement("DROP VIEW IF EXISTS `broker_performance_report`");
    }
};
