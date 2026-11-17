<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Suggestions (owner, 17 Nov 2026): anyone signed in (staff, borrowers, partners) sends an idea or a problem from the
 * Suggest button on every page; the sysadmin reviews them on Features and suggestions. SQL twin:
 * database/sql/2026_11_17_features_and_suggestions.sql.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('suggestions')) {
            return;
        }
        Schema::create('suggestions', function (Blueprint $t) {
            $t->bigIncrements('id');
            $t->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $t->string('kind', 20)->default('idea')->comment('idea, problem');
            $t->text('body');
            $t->string('page', 255)->nullable()->comment('the page it was sent from');
            $t->string('status', 20)->default('new')->comment('new, planned, done, declined');
            $t->text('reply')->nullable();
            $t->timestamps();
            $t->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('suggestions');
    }
};
