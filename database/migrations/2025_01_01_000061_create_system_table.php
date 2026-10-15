<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Existing databases already have this table: leave it and its data untouched.
        if (Schema::hasTable('system')) {
            return;
        }

        Schema::create('system', function (Blueprint $table) {
            $table->collation = 'utf8mb4_unicode_ci';
            $table->charset = 'utf8mb4';

            $table->bigIncrements('id');
            $table->string('name')->nullable();
            $table->string('logo')->nullable();
            $table->string('logo_dark')->nullable();
            $table->string('logo_icon')->nullable();
            $table->string('favicon')->nullable();
            $table->string('slogan')->nullable();
            $table->string('timezone', 100)->default('UTC');
            $table->string('date_format', 50)->default('d-m-Y');
            $table->string('time_format', 50)->default('H:i:s');
            $table->string('currency', 50)->default('KES');
            $table->string('currency_symbol', 50)->default('KSh');
            $table->string('primary_color', 50)->nullable();
            $table->string('secondary_color', 50)->nullable();
            $table->string('contact_email')->nullable();
            $table->string('contact_phone', 50)->nullable();
            $table->text('address')->nullable();
            $table->longText('location')->collation('utf8mb4_bin')->nullable();
            $table->text('meta_description')->nullable();
            $table->text('meta_keywords')->nullable();
            $table->boolean('maintenance_mode')->default(false);
            $table->integer('pagination_limit')->default(15);
            $table->text('custom_css')->nullable();
            $table->text('custom_js')->nullable();
            $table->longText('settings')->collation('utf8mb4_bin')->nullable();
            $table->longText('website_pages')->collation('utf8mb4_bin')->nullable();
            $table->longText('social_media')->collation('utf8mb4_bin')->nullable();
            $table->timestamp('created_at')->nullable()->useCurrent();
            $table->timestamp('updated_at')->useCurrentOnUpdate()->nullable()->useCurrent();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('system');
    }
};
