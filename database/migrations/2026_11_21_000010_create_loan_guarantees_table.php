<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// A guarantor signs a guarantee and release for each loan before its money goes out (owner, 21 Nov 2026).
// App\Services\Loans\Guarantees. SQL twin: database/sql/2026_11_21_guarantors_and_kin.sql
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('loan_guarantees')) {
            return;
        }
        Schema::create('loan_guarantees', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('loan_id')->unique();
            $table->unsignedBigInteger('borrower_id')->index();
            $table->unsignedBigInteger('contact_id')->nullable();
            $table->string('status', 12)->default('waiting'); // waiting | signed | waived
            $table->string('name');
            $table->string('phone', 20)->index();
            $table->string('relationship', 60)->nullable();
            $table->string('id_number', 20)->nullable();
            $table->date('dob')->nullable();
            $table->string('email')->nullable();
            $table->string('residence', 120)->nullable();
            $table->string('occupation', 120)->nullable();
            $table->string('id_front_path')->nullable();
            $table->string('id_back_path')->nullable();
            $table->string('signature_path')->nullable();
            $table->text('terms')->nullable();
            $table->char('terms_sha256', 64)->nullable();
            $table->char('token_hash', 64)->nullable()->unique();
            $table->timestamp('link_sent_at')->nullable();
            $table->timestamp('link_expires_at')->nullable();
            $table->timestamp('signed_at')->nullable();
            $table->string('signed_ip', 45)->nullable();
            $table->string('signed_agent', 255)->nullable();
            $table->unsignedBigInteger('waived_by')->nullable();
            $table->string('waived_reason', 255)->nullable();
            $table->timestamp('waived_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('loan_guarantees');
    }
};
