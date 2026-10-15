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
        if (Schema::hasView('borrower_contact_network')) {
            return;
        }

        DB::statement("CREATE VIEW `borrower_contact_network` AS select `u`.`id` AS `user_id`,`u`.`name` AS `borrower_name`,`c`.`id` AS `contact_id`,`c`.`name` AS `contact_name`,`c`.`phone` AS `contact_phone`,`c`.`email` AS `contact_email`,`ct`.`name` AS `contact_type`,`c`.`relationship_specific` AS `relationship_specific`,`c`.`is_primary_contact` AS `is_primary_contact`,`c`.`priority` AS `priority` from ((`users` `u` left join `contacts` `c` on((`u`.`id` = `c`.`user_id`))) left join `contact_types` `ct` on((`c`.`contact_type_id` = `ct`.`id`))) where (`u`.`role` in ('borrower','partner'))");
    }

    public function down(): void
    {
        DB::statement("DROP VIEW IF EXISTS `borrower_contact_network`");
    }
};
