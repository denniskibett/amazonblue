<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Integrations (owner, 3 Nov 2026): M-Pesa, SMS, mail, storage, analytics and alerts, editable on /staff/integrations.
 * settings is the integration's .env values, encrypted with APP_KEY; saved values override .env at boot
 * (Support\Integrations). Filled from .env by `php artisan integrations:import-env`.
 * SQL twin: database/sql/2026_11_03_integrations_api_keys.sql.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('integrations')) {
            return;
        }
        Schema::create('integrations', function (Blueprint $t) {
            $t->id();
            $t->string('slug', 40);
            $t->string('name', 80);
            $t->string('category', 30);
            $t->string('description', 255)->nullable();
            $t->string('logo', 255)->nullable();
            $t->boolean('enabled')->default(true);
            $t->longText('settings')->nullable();
            $t->unsignedBigInteger('updated_by')->nullable();
            $t->timestamps();
            $t->unique('slug', 'uq_integrations_slug');
            $t->foreign('updated_by', 'fk_integrations_updated_by')->references('id')->on('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('integrations');
    }
};
