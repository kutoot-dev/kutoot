<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * When a merchant location (or merchant) is deleted, cascade delete all related data:
     * - discount_coupons (deals)
     * - qr_codes
     * - transactions
     * - merchant_applications
     */
    public function up(): void
    {
        Schema::table('discount_coupons', function (Blueprint $table) {
            $table->dropForeign(['merchant_location_id']);
            $table->foreign('merchant_location_id')
                ->references('id')->on('merchant_locations')
                ->cascadeOnDelete();
        });

        Schema::table('qr_codes', function (Blueprint $table) {
            $table->dropForeign(['merchant_location_id']);
            $table->foreign('merchant_location_id')
                ->references('id')->on('merchant_locations')
                ->cascadeOnDelete();
        });

        Schema::table('transactions', function (Blueprint $table) {
            $table->dropForeign(['merchant_location_id']);
            $table->foreign('merchant_location_id')
                ->references('id')->on('merchant_locations')
                ->cascadeOnDelete();
        });

        Schema::table('merchant_applications', function (Blueprint $table) {
            $table->dropForeign(['merchant_location_id']);
            $table->foreign('merchant_location_id')
                ->references('id')->on('merchant_locations')
                ->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('discount_coupons', function (Blueprint $table) {
            $table->dropForeign(['merchant_location_id']);
            $table->foreign('merchant_location_id')
                ->references('id')->on('merchant_locations')
                ->nullOnDelete();
        });

        Schema::table('qr_codes', function (Blueprint $table) {
            $table->dropForeign(['merchant_location_id']);
            $table->foreign('merchant_location_id')
                ->references('id')->on('merchant_locations')
                ->nullOnDelete();
        });

        Schema::table('transactions', function (Blueprint $table) {
            $table->dropForeign(['merchant_location_id']);
            $table->foreign('merchant_location_id')
                ->references('id')->on('merchant_locations')
                ->nullOnDelete();
        });

        Schema::table('merchant_applications', function (Blueprint $table) {
            $table->dropForeign(['merchant_location_id']);
            $table->foreign('merchant_location_id')
                ->references('id')->on('merchant_locations')
                ->nullOnDelete();
        });
    }
};
