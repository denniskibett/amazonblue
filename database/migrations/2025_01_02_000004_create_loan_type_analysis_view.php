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
        if (Schema::hasView('loan_type_analysis')) {
            return;
        }

        DB::statement("CREATE VIEW `loan_type_analysis` AS select `lt`.`id` AS `id`,`lt`.`name` AS `name`,`lt`.`interest_rate` AS `interest_rate`,count(`l`.`id`) AS `times_issued`,sum(`l`.`amount`) AS `total_principal`,avg(`l`.`amount`) AS `avg_principal_size`,round(((sum((case when (`r`.`transaction` is not null) then 1 else 0 end)) / count(`l`.`id`)) * 100),2) AS `percentage_with_problems`,round(avg(`chs`.`health_score`),2) AS `avg_borrower_health_score` from (((`loan_types` `lt` left join `loans` `l` on((`l`.`loan_type_id` = `lt`.`id`))) left join `repayments` `r` on(((`r`.`loan_id` = `l`.`id`) and (`r`.`transaction` in ('ROLL OVER','CREDIT DISCOUNT','BAD DEBT'))))) left join `customer_health_scorecard` `chs` on((`l`.`user_id` = `chs`.`user_id`))) group by `lt`.`id`,`lt`.`name`,`lt`.`interest_rate` order by sum(`l`.`amount`) desc");
    }

    public function down(): void
    {
        DB::statement("DROP VIEW IF EXISTS `loan_type_analysis`");
    }
};
