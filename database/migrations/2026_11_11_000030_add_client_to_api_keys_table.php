<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * API keys say what uses them (owner, 11 Nov 2026): "mobile" for the mobile app (the only keys /api/mobile/v1 takes),
 * "system" for other systems on /api/v1. SQL twin: database/sql/2026_11_11_mobile_api.sql.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('api_keys', function (Blueprint $t) {
            if (! Schema::hasColumn('api_keys', 'client')) {
                $t->string('client', 20)->default('system')->after('name');
            }
        });
    }

    public function down(): void
    {
        Schema::table('api_keys', function (Blueprint $t) {
            if (Schema::hasColumn('api_keys', 'client')) {
                $t->dropColumn('client');
            }
        });
    }
};
