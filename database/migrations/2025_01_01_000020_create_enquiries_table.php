<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Existing databases already have this table: leave it and its data untouched.
        if (Schema::hasTable('enquiries')) {
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
            $table->timestamp('consented_at');
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
