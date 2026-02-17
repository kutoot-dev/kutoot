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
            $table->foreignId('category_id')->constrained('campaign_categories')->cascadeOnDelete();
            $table->string('creator_type');
            $table->foreignId('creator_id')->constrained('users')->cascadeOnDelete();
            $table->string('reward_name');
            $table->string('status')->default('active');
            $table->date('start_date');
            $table->decimal('reward_cost_target', 15, 2);
            $table->bigInteger('stamp_target');
            $table->decimal('collected_commission_cache', 15, 2)->default(0);
            $table->bigInteger('issued_stamps_cache')->default(0);
            $table->dateTime('winner_announcement_date')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('campaigns');
    }
};
