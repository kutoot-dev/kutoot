<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('subscription_plans', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('best_value')->default(false);
            $table->decimal('price', 15, 2)->default(0)->comment('Plan purchase price');
            $table->decimal('original_price', 15, 2)->nullable()->comment('Marketing/MRP price shown as strike-through');
            $table->boolean('is_default')->default(false);
            $table->integer('stamps_on_purchase')->default(0);
            $table->decimal('stamp_denomination', 15, 2)->default(100);
            $table->integer('stamps_per_denomination')->default(1);
            $table->integer('max_discounted_bills');
            $table->decimal('max_redeemable_amount', 15, 2);
            $table->unsignedInteger('duration_days')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('subscription_plans');
    }
};
