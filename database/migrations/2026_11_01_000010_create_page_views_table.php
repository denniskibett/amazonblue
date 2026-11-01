<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Site analytics (owner, 1 Nov 2026): one row per page someone opened (TrackPageView middleware), first-party only.
 * No IP address and no raw session id are kept: visits are told apart by a sha256 of the session id. Rows older
 * than lending.analytics.keep_days are deleted by `analytics:prune-views`. SQL twin: database/sql/2026_11_01_site_analytics.sql.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('page_views')) {
            return;
        }
        Schema::create('page_views', function (Blueprint $t) {
            $t->id();
            $t->dateTime('occurred_at');
            $t->unsignedBigInteger('user_id')->nullable();
            $t->string('role', 40)->default('visitor');
            $t->char('session_hash', 64)->nullable();
            $t->string('path', 255);
            $t->string('route_name', 120)->nullable();
            $t->unsignedSmallInteger('status')->default(200);
            $t->string('referrer_host', 120)->nullable();
            $t->string('utm_source', 100)->nullable();
            $t->string('utm_medium', 100)->nullable();
            $t->string('utm_campaign', 100)->nullable();
            $t->string('device', 10)->default('desktop');
            $t->unsignedInteger('response_ms')->nullable();
            $t->index('occurred_at', 'idx_page_views_occurred');
            $t->index(['user_id', 'occurred_at'], 'idx_page_views_user');
            $t->index(['session_hash', 'occurred_at'], 'idx_page_views_session');
            $t->index(['route_name', 'occurred_at'], 'idx_page_views_route');
            $t->foreign('user_id', 'fk_page_views_user')->references('id')->on('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('page_views');
    }
};
