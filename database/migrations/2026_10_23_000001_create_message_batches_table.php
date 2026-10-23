<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Email/SMS batches that a person approves before they go out (owner, 23 Oct 2026). SQL twin:
 * database/sql/2026_10_23_message_batches.sql.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('message_batches', function (Blueprint $t) {
            $t->id();
            $t->string('kind', 40);                       // due_soon | overdue | final_notice
            $t->string('channels', 20)->default('email'); // email | sms | email,sms
            $t->string('status', 20)->default('draft');   // draft | sending | sent | cancelled
            $t->string('subject');
            $t->unsignedInteger('recipients')->default(0);
            $t->unsignedInteger('sent')->default(0);
            $t->unsignedInteger('failed')->default(0);
            $t->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $t->foreignId('sent_by')->nullable()->constrained('users')->nullOnDelete();
            $t->timestamp('sent_at')->nullable();
            $t->text('note')->nullable();
            $t->timestamps();
            $t->index(['kind', 'status']);
        });

        Schema::create('message_batch_items', function (Blueprint $t) {
            $t->id();
            $t->foreignId('batch_id')->constrained('message_batches')->cascadeOnDelete();
            $t->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $t->unsignedBigInteger('loan_id')->nullable()->index();
            $t->decimal('owed', 15, 2)->default(0);
            $t->integer('days_late')->default(0);
            $t->date('due_date')->nullable();
            $t->string('status', 20)->default('pending'); // pending | sent | failed | skipped
            $t->string('error', 255)->nullable();
            $t->timestamp('sent_at')->nullable();
            $t->timestamps();
            $t->index(['user_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('message_batch_items');
        Schema::dropIfExists('message_batches');
    }
};
