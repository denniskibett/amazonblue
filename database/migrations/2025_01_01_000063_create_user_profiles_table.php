<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Existing databases already have this table: leave it and its data untouched.
        if (Schema::hasTable('user_profiles')) {
            return;
        }

        Schema::create('user_profiles', function (Blueprint $table) {
            $table->collation = 'utf8mb4_unicode_ci';
            $table->charset = 'utf8mb4';

            $table->bigIncrements('id');
            $table->unsignedBigInteger('user_id')->index('idx_user_id')->comment('References users.id');
            $table->string('current_residence')->nullable();
            $table->date('current_residence_from')->nullable();
            $table->date('current_residence_to')->nullable();
            $table->unsignedBigInteger('residence_type_id')->nullable()->index('residence_type_id')->comment('References residence_types.id');
            $table->text('residence_notes')->nullable();
            $table->text('general_notes')->nullable();
            $table->unsignedBigInteger('created_by')->nullable()->index('created_by');
            $table->unsignedBigInteger('updated_by')->nullable()->index('updated_by');
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['user_id'], 'unique_user_profile');

            $table->foreign(['user_id'], 'user_profiles_ibfk_1')->references(['id'])->on('users')->onUpdate('no action')->onDelete('cascade');
            $table->foreign(['residence_type_id'], 'user_profiles_ibfk_2')->references(['id'])->on('residence_types')->onUpdate('no action')->onDelete('set null');
            $table->foreign(['created_by'], 'user_profiles_ibfk_3')->references(['id'])->on('users')->onUpdate('no action')->onDelete('set null');
            $table->foreign(['updated_by'], 'user_profiles_ibfk_4')->references(['id'])->on('users')->onUpdate('no action')->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_profiles');
    }
};
