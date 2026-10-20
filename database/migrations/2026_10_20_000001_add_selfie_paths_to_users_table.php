<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Three KYC selfies (face straight, turned left, turned right) on the private disk, next to the ID images.
 * Same change as database/sql/2026_10_20_kyc_selfies.sql.
 */
return new class extends Migration
{
    private const COLUMNS = ['selfie_center_path', 'selfie_left_path', 'selfie_right_path'];

    public function up(): void
    {
        $after = 'id_back_path';
        foreach (self::COLUMNS as $column) {
            if (! Schema::hasColumn('users', $column)) {
                Schema::table('users', function (Blueprint $table) use ($column, $after) {
                    $table->string($column)->nullable()->after($after);
                });
            }
            $after = $column;
        }
    }

    public function down(): void
    {
        foreach (self::COLUMNS as $column) {
            if (Schema::hasColumn('users', $column)) {
                Schema::table('users', fn (Blueprint $table) => $table->dropColumn($column));
            }
        }
    }
};
