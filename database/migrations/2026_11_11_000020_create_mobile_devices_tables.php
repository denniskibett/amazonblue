<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Mobile app (owner, 11 Nov 2026): the phones a user signs in on (model, system, app version, push token), the
 * borrower's consent to what the app uses, and where (approximately) the app was used for applying and paying. Never
 * contacts, photos, call logs, SMS or precise location. SQL twin: database/sql/2026_11_11_mobile_api.sql.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('mobile_devices')) {
            Schema::create('mobile_devices', function (Blueprint $t) {
                $t->bigIncrements('id');
                $t->foreignId('user_id')->constrained('users')->cascadeOnDelete();
                $t->string('install_id', 64);
                $t->string('platform', 10)->default('android');
                $t->string('model', 80)->nullable();
                $t->string('os_version', 40)->nullable();
                $t->string('app_version', 20)->nullable();
                $t->string('push_token', 255)->nullable();
                $t->timestamp('first_seen_at')->nullable();
                $t->timestamp('last_seen_at')->nullable();
                $t->timestamps();
                $t->unique(['user_id', 'install_id']);
                $t->index('install_id');
            });
        }
        if (! Schema::hasTable('data_consents')) {
            Schema::create('data_consents', function (Blueprint $t) {
                $t->bigIncrements('id');
                $t->foreignId('user_id')->constrained('users')->cascadeOnDelete();
                $t->string('item', 40); // device, area, install_id, usage
                $t->boolean('granted');
                $t->string('version', 20)->default('1');
                $t->timestamps();
                $t->unique(['user_id', 'item']);
            });
        }
        if (! Schema::hasTable('device_sightings')) {
            Schema::create('device_sightings', function (Blueprint $t) {
                $t->bigIncrements('id');
                $t->foreignId('mobile_device_id')->constrained('mobile_devices')->cascadeOnDelete();
                $t->string('event', 20); // signed_in, applied, paid
                $t->string('area', 120)->nullable(); // town or estate, never coordinates
                $t->timestamp('seen_at');
                $t->index(['mobile_device_id', 'seen_at']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('device_sightings');
        Schema::dropIfExists('data_consents');
        Schema::dropIfExists('mobile_devices');
    }
};
