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
        if (Schema::hasView('debt_recovery_summary')) {
            return;
        }

        DB::statement("CREATE VIEW `debt_recovery_summary` AS select `drc`.`id` AS `case_id`,`drc`.`case_number` AS `case_number`,`u`.`id` AS `user_id`,`u`.`name` AS `debtor_name`,`u`.`email` AS `debtor_email`,`u`.`phone` AS `debtor_phone`,`drc`.`total_debt_amount` AS `total_debt_amount`,`drc`.`principal_outstanding` AS `principal_outstanding`,`drc`.`interest_outstanding` AS `interest_outstanding`,`drc`.`penalty_outstanding` AS `penalty_outstanding`,`drc`.`fees_outstanding` AS `fees_outstanding`,`drc`.`default_date` AS `default_date`,`drc`.`days_in_default` AS `days_in_default`,`rs`.`name` AS `status`,`rp`.`name` AS `priority`,`drc`.`assigned_to` AS `assigned_to`,`drc`.`last_contact_date` AS `last_contact_date`,`drc`.`next_action_date` AS `next_action_date`,(select sum(`recovery_actions`.`amount_collected`) from `recovery_actions` where ((`recovery_actions`.`case_id` = `drc`.`id`) and (`recovery_actions`.`outcome` = 'successful'))) AS `total_recovered`,(select count(0) from `recovery_actions` where (`recovery_actions`.`case_id` = `drc`.`id`)) AS `total_actions` from (((`debt_recovery_cases` `drc` join `users` `u` on((`drc`.`user_id` = `u`.`id`))) join `recovery_statuses` `rs` on((`drc`.`status_id` = `rs`.`id`))) join `recovery_priorities` `rp` on((`drc`.`priority_id` = `rp`.`id`)))");
    }

    public function down(): void
    {
        DB::statement("DROP VIEW IF EXISTS `debt_recovery_summary`");
    }
};
