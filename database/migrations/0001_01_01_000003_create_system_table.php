<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('system', function (Blueprint $table) {
            $table->id();
            $table->string('name')->nullable();
            $table->string('logo')->nullable();
            $table->string('logo_dark')->nullable();
            $table->string('logo_icon')->nullable();
            $table->string('favicon')->nullable();
            $table->string('slogan')->nullable();
            $table->string('timezone')->default('UTC');
            $table->string('date_format')->default('d-m-Y');
            $table->string('time_format')->default('H:i:s');
            $table->string('currency')->default('KES');
            $table->string('currency_symbol')->default('KSh');
            $table->string('primary_color')->nullable();
            $table->string('secondary_color')->nullable();
            $table->string('contact_email')->nullable();
            $table->string('contact_phone')->nullable();
            $table->text('address')->nullable();            
            $table->json('location')->nullable();
            $table->text('meta_description')->nullable();
            $table->text('meta_keywords')->nullable();
            $table->boolean('maintenance_mode')->default(false);
            $table->integer('pagination_limit')->default(15);
            $table->text('custom_css')->nullable();
            $table->text('custom_js')->nullable();
            $table->json('settings')->nullable();
            $table->json('website_pages')->nullable();
            $table->json('social_media')->nullable();
            $table->timestamps();
        });

        // Insert default system configuration
        DB::table('system')->insert([
            'name' => 'Your System Name',
            'slogan' => 'Your system slogan here',
            'timezone' => 'UTC',
            'currency' => 'Kenya Shilling',
            'currency_symbol' => 'KES',
            'date_format' => 'd-m-Y',
            'time_format' => 'H:i:s',
            'pagination_limit' => 15,
            'maintenance_mode' => false,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('system');
    }
};