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
        if (Schema::hasView('recovery_officer_workload')) {
            return;
        }

        DB::statement("CREATE VIEW `recovery_officer_workload` AS select `u`.`id` AS `officer_id`,`u`.`name` AS `officer_name`,`u`.`email` AS `officer_email`,count(distinct `drc`.`id`) AS `total_cases`,count(distinct (case when (`rs`.`slug` in ('open','in_progress','negotiation')) then `drc`.`id` end)) AS `active_cases`,count(distinct (case when (`rp`.`slug` = 'urgent') then `drc`.`id` end)) AS `urgent_cases`,sum(`drc`.`total_debt_amount`) AS `total_debt_value` from (((`users` `u` left join `debt_recovery_cases` `drc` on((`u`.`id` = `drc`.`assigned_to`))) left join `recovery_statuses` `rs` on((`drc`.`status_id` = `rs`.`id`))) left join `recovery_priorities` `rp` on((`drc`.`priority_id` = `rp`.`id`))) where (`u`.`role` in ('admin','teller')) group by `u`.`id`,`u`.`name`,`u`.`email`");
    }

    public function down(): void
    {
        DB::statement("DROP VIEW IF EXISTS `recovery_officer_workload`");
    }
};
