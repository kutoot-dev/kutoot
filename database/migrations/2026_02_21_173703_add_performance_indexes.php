<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Transactions: payment_status is filtered frequently
        Schema::table('transactions', function (Blueprint $table) {
            $table->index('payment_status');
            $table->index('type');
            $table->index('created_at');
            $table->index(['user_id', 'payment_status']);
            $table->index(['user_id', 'type', 'created_at']);
        });

        // Discount coupons: active+date filtering
        Schema::table('discount_coupons', function (Blueprint $table) {
            $table->index('is_active');
            $table->index(['is_active', 'starts_at', 'expires_at']);
        });

        // Coupon redemptions: usage tracking
        Schema::table('coupon_redemptions', function (Blueprint $table) {
            $table->index(['user_id', 'coupon_id']);
        });

        // User subscriptions: active sub lookup
        Schema::table('user_subscriptions', function (Blueprint $table) {
            $table->index('status');
            $table->index(['user_id', 'status']);
        });

        // Subscription plans: default plan lookup
        Schema::table('subscription_plans', function (Blueprint $table) {
            $table->index('is_default');
        });

        // Activity log: pruning and filtering
        Schema::table('activity_log', function (Blueprint $table) {
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->dropIndex(['payment_status']);
            $table->dropIndex(['type']);
            $table->dropIndex(['created_at']);
            $table->dropIndex(['user_id', 'payment_status']);
            $table->dropIndex(['user_id', 'type', 'created_at']);
        });

        Schema::table('discount_coupons', function (Blueprint $table) {
            $table->dropIndex(['is_active']);
            $table->dropIndex(['is_active', 'starts_at', 'expires_at']);
        });

        Schema::table('coupon_redemptions', function (Blueprint $table) {
            $table->dropIndex(['user_id', 'coupon_id']);
        });

        Schema::table('user_subscriptions', function (Blueprint $table) {
            $table->dropIndex(['status']);
            $table->dropIndex(['user_id', 'status']);
        });

        Schema::table('subscription_plans', function (Blueprint $table) {
            $table->dropIndex(['is_default']);
        });

        Schema::table('activity_log', function (Blueprint $table) {
            $table->dropIndex(['created_at']);
        });
    }
};
