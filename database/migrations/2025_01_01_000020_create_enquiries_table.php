<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Existing databases already have this table: leave its data untouched. Some MySQL/MariaDB setups gave
        // consented_at "ON UPDATE CURRENT_TIMESTAMP", which would overwrite the consent time on every edit.
        if (Schema::hasTable('enquiries')) {
            if (in_array(DB::getDriverName(), ['mysql', 'mariadb'], true)) {
                $extra = DB::selectOne(
                    "select extra as col_extra from information_schema.columns
                     where table_schema = database() and table_name = 'enquiries' and column_name = 'consented_at'"
                )?->col_extra ?? '';
                if (stripos($extra, 'on update') !== false) {
                    DB::statement('ALTER TABLE enquiries MODIFY consented_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP');
                }
            }

            return;
        }

        Schema::create('enquiries', function (Blueprint $table) {
            $table->collation = 'utf8mb4_unicode_ci';
            $table->charset = 'utf8mb4';

            $table->bigIncrements('id');
            $table->string('topic', 30)->index();
            $table->string('name');
            $table->string('organisation')->nullable();
            $table->string('email');
            $table->string('phone', 20);
            $table->string('investor_type', 100)->nullable();
            $table->string('allocation', 100)->nullable();
            $table->text('message');
            $table->string('route_to')->comment('Desk inbox that handles this topic');
            $table->enum('status', ['new', 'in_progress', 'closed'])->default('new')->index();
            $table->timestamp('consented_at')->useCurrent();
            $table->string('ip_address', 45)->nullable();
            $table->string('user_agent')->nullable();
            $table->timestamps();

            $table->unique(['email', 'topic']);
            $table->unique(['phone', 'topic']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('enquiries');
    }
};
