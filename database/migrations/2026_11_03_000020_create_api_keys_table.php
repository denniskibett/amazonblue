<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * API keys (owner, 3 Nov 2026): keys for other systems calling /api/v1, managed on /staff/api-keys. Only a
 * SHA-256 hash of the key is stored; the key itself is shown once, when it is made.
 * SQL twin: database/sql/2026_11_03_integrations_api_keys.sql.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('api_keys')) {
            return;
        }
        Schema::create('api_keys', function (Blueprint $t) {
            $t->id();
            $t->string('name', 80);
            $t->string('prefix', 16);
            $t->char('token_hash', 64);
            $t->char('last_four', 4);
            $t->enum('status', ['active', 'disabled'])->default('active');
            $t->unsignedBigInteger('created_by')->nullable();
            $t->timestamp('last_used_at')->nullable();
            $t->string('last_used_ip', 45)->nullable();
            $t->timestamps();
            $t->unique('token_hash', 'uq_api_keys_token_hash');
            $t->foreign('created_by', 'fk_api_keys_created_by')->references('id')->on('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('api_keys');
    }
};
