<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Existing databases already have this table: leave it and its data untouched.
        if (Schema::hasTable('audit_logs')) {
            return;
        }

        Schema::create('audit_logs', function (Blueprint $table) {
            $table->collation = 'utf8mb4_unicode_ci';
            $table->charset = 'utf8mb4';

            $table->bigIncrements('id');
            $table->string('table_name', 100);
            $table->unsignedBigInteger('record_id');
            $table->enum('action', ['create', 'update', 'delete', 'restore', 'force_delete'])->index('idx_action');
            $table->longText('old_values')->collation('utf8mb4_bin')->nullable();
            $table->longText('new_values')->collation('utf8mb4_bin')->nullable();
            $table->unsignedBigInteger('user_id')->nullable()->index('idx_user_id');
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->timestamp('created_at')->nullable()->index('idx_created_at');

            $table->index(['table_name', 'record_id'], 'idx_table_record');

            $table->foreign(['user_id'], 'audit_logs_ibfk_1')->references(['id'])->on('users')->onUpdate('no action')->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('audit_logs');
    }
};
