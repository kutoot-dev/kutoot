<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('coupon_categories', function (Blueprint $table) {
            if (! Schema::hasColumn('coupon_categories', 'slug')) {
                $table->string('slug')->unique()->after('name');
            }
            if (! Schema::hasColumn('coupon_categories', 'icon')) {
                $table->string('icon')->nullable()->after('slug');
            }
        });

        Schema::table('merchant_locations', function (Blueprint $table) {
            if (! Schema::hasColumn('merchant_locations', 'is_active')) {
                $table->boolean('is_active')->default(true)->after('commission_percentage');
            }
        });

        Schema::table('campaigns', function (Blueprint $table) {
            if (! Schema::hasColumn('campaigns', 'is_active')) {
                $table->boolean('is_active')->default(true)->after('status');
            }
        });

        Schema::table('discount_coupons', function (Blueprint $table) {
            if (! Schema::hasColumn('discount_coupons', 'is_active')) {
                $table->boolean('is_active')->default(true)->after('discount_value');
            }
        });

        Schema::table('campaign_categories', function (Blueprint $table) {
            if (! Schema::hasColumn('campaign_categories', 'slug')) {
                $table->string('slug')->unique()->after('name');
            }
            if (! Schema::hasColumn('campaign_categories', 'icon')) {
                $table->string('icon')->nullable()->after('slug');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('coupon_categories', function (Blueprint $table) {
            $table->dropColumn(['slug', 'icon']);
        });

        Schema::table('merchant_locations', function (Blueprint $table) {
            $table->dropColumn('is_active');
        });

        Schema::table('campaign_categories', function (Blueprint $table) {
            $table->dropColumn(['slug', 'icon']);
        });
    }
};
