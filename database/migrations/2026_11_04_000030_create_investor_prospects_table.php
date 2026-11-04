<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Capital raising (owner, 4 Nov 2026): investment managers keep their prospects (people and companies who may invest)
 * from first call to money in, and partners record which investment manager looks after them. SQL twin:
 * database/sql/2026_11_04_investor_prospects.sql (also the "investment-manager" role and its permission).
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('investor_prospects')) {
            Schema::create('investor_prospects', function (Blueprint $t) {
                $t->bigIncrements('id');
                $t->string('name');
                $t->string('kind', 20)->default('individual'); // individual, corporate, sacco, group, institution
                $t->string('contact')->nullable();
                $t->string('wants')->nullable();
                $t->decimal('ticket', 15, 2)->nullable();
                $t->string('stage', 20)->default('first_call')->index(); // first_call, meeting, due_diligence, term_sheet, won, lost
                $t->unsignedTinyInteger('chance')->nullable();
                $t->date('next_on')->nullable();
                $t->string('next_step')->nullable();
                $t->foreignId('manager_id')->nullable()->constrained('users')->nullOnDelete();
                $t->foreignId('partner_id')->nullable()->constrained('partners')->nullOnDelete();
                $t->text('notes')->nullable();
                $t->timestamps();
            });
        }
        Schema::table('partners', function (Blueprint $t) {
            if (! Schema::hasColumn('partners', 'investment_manager_id')) {
                $t->foreignId('investment_manager_id')->nullable()->after('user_id')->constrained('users')->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        Schema::table('partners', function (Blueprint $t) {
            if (Schema::hasColumn('partners', 'investment_manager_id')) {
                $t->dropConstrainedForeignId('investment_manager_id');
            }
        });
        Schema::dropIfExists('investor_prospects');
    }
};
