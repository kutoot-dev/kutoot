<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('discount_coupons', function (Blueprint $table) {
            $table->id();
            $table->foreignId('coupon_category_id')->constrained('coupon_categories')->cascadeOnDelete();
            $table->foreignId('merchant_location_id')->nullable()->constrained()->nullOnDelete();
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('discount_type');
            $table->decimal('discount_value', 15, 2);
            $table->decimal('min_order_value', 15, 2)->nullable();
            $table->decimal('max_discount_amount', 15, 2)->nullable();
            $table->string('code')->unique();
            $table->integer('usage_limit')->nullable();
            $table->integer('usage_per_user')->default(1);
            $table->dateTime('starts_at');
            $table->dateTime('expires_at')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('discount_coupons');
    }
};
