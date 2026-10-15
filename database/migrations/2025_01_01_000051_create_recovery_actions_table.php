<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Existing databases already have this table: leave it and its data untouched.
        if (Schema::hasTable('recovery_actions')) {
            return;
        }

        Schema::create('recovery_actions', function (Blueprint $table) {
            $table->collation = 'utf8mb4_unicode_ci';
            $table->charset = 'utf8mb4';

            $table->bigIncrements('id');
            $table->unsignedBigInteger('case_id')->index('idx_case_id');
            $table->unsignedBigInteger('contact_id')->nullable()->index('idx_contact_id');
            $table->unsignedBigInteger('action_type_id')->index('idx_action_type_id');
            $table->dateTime('action_date')->useCurrent()->index('idx_action_date');
            $table->unsignedBigInteger('performed_by')->nullable()->index('performed_by');
            $table->string('contact_person')->nullable();
            $table->string('contact_relationship', 100)->nullable();
            $table->string('contact_phone', 50)->nullable();
            $table->string('contact_email')->nullable();
            $table->enum('outcome', ['successful', 'partial', 'failed', 'promise_to_pay', 'no_answer', 'wrong_number', 'refused', 'pending'])->nullable()->default('pending');
            $table->decimal('promised_amount', 15)->nullable();
            $table->date('promised_date')->nullable();
            $table->decimal('amount_collected', 15)->nullable()->default(0);
            $table->text('notes')->nullable();
            $table->date('follow_up_date')->nullable();
            $table->text('follow_up_notes')->nullable();
            $table->string('attachment_path')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign(['case_id'], 'recovery_actions_ibfk_1')->references(['id'])->on('debt_recovery_cases')->onUpdate('no action')->onDelete('cascade');
            $table->foreign(['contact_id'], 'recovery_actions_ibfk_2')->references(['id'])->on('contacts')->onUpdate('no action')->onDelete('set null');
            $table->foreign(['action_type_id'], 'recovery_actions_ibfk_3')->references(['id'])->on('action_types')->onUpdate('no action')->onDelete('cascade');
            $table->foreign(['performed_by'], 'recovery_actions_ibfk_4')->references(['id'])->on('users')->onUpdate('no action')->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('recovery_actions');
    }
};
