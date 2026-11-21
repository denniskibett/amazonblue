<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// The next of kin confirms from a texted link that they know the client, with their ID (owner, 21 Nov 2026).
// App\Services\Kyc\KinConfirmations. SQL twin: database/sql/2026_11_21_guarantors_and_kin.sql
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('kin_confirmations')) {
            return;
        }
        Schema::create('kin_confirmations', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->index();
            $table->string('status', 12)->default('waiting'); // waiting | confirmed | denied
            $table->string('name');
            $table->string('phone', 20)->index();
            $table->string('relation', 60)->nullable();
            $table->string('stated_relation', 60)->nullable();
            $table->string('id_number', 20)->nullable();
            $table->string('id_front_path')->nullable();
            $table->string('id_back_path')->nullable();
            $table->char('token_hash', 64)->nullable()->unique();
            $table->timestamp('link_sent_at')->nullable();
            $table->timestamp('link_expires_at')->nullable();
            $table->timestamp('answered_at')->nullable();
            $table->string('answered_ip', 45)->nullable();
            $table->string('answered_agent', 255)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('kin_confirmations');
    }
};
