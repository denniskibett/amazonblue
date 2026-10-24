<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * AML screening (owner, 23 Oct 2026): the one new table. It holds the sanctions list staff upload (e.g. the UN
 * consolidated list), one row per listed person or entity, so names can be matched before a business, director or
 * partner is approved. Nothing else stores a list like this. The screening result itself is kept with the record
 * it belongs to (businesses.checks, users.kyc_checks). SQL twin: database/sql/2026_10_24_sanctions_entries.sql.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('sanctions_entries')) {
            return;
        }
        Schema::create('sanctions_entries', function (Blueprint $t) {
            $t->id();
            $t->string('list_name', 60);
            $t->string('reference', 60)->nullable();
            $t->enum('kind', ['individual', 'entity'])->default('individual');
            $t->string('name', 255);
            $t->string('name_key', 255)->index('idx_sanctions_entries_name_key');
            $t->string('dob', 40)->nullable();
            $t->string('country', 80)->nullable();
            $t->unsignedBigInteger('uploaded_by')->nullable();
            $t->timestamps();
            $t->index('list_name', 'idx_sanctions_entries_list');
            $t->foreign('uploaded_by', 'fk_sanctions_entries_user')->references('id')->on('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sanctions_entries');
    }
};
