<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('campaigns', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique()->nullable();
            $table->foreignId('category_id')->constrained('campaign_categories')->cascadeOnDelete();
            $table->string('creator_type');
            $table->foreignId('creator_id')->constrained('users')->cascadeOnDelete();
            $table->string('reward_name');
            $table->text('description')->nullable();
            $table->string('status')->default('active');
            $table->date('start_date');
            $table->decimal('reward_cost_target', 15, 2);
            $table->bigInteger('stamp_target');
            $table->unsignedTinyInteger('stamp_slots')->nullable();
            $table->unsignedInteger('stamp_slot_min')->nullable();
            $table->unsignedInteger('stamp_slot_max')->nullable();
            $table->boolean('stamp_editable_on_plan_purchase')->default(false);
            $table->boolean('stamp_editable_on_coupon_redemption')->default(false);
            $table->decimal('collected_commission_cache', 15, 2)->nullable()->default(0);
            $table->bigInteger('issued_stamps_cache')->nullable()->default(0);
            $table->unsignedTinyInteger('marketing_bounty_percentage')->default(0);
            $table->dateTime('winner_announcement_date')->nullable();
            $table->boolean('is_active')->default(true);
            $table->boolean('is_premium')->default(false);
            $table->timestamps();

            $table->index('status');
            $table->index('is_active');
            $table->index(['status', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('campaigns');
    }
};
