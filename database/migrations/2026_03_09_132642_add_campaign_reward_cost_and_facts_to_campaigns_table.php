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
        Schema::table('campaigns', function (Blueprint $table) {
            // Add actual reward cost field
            $table->decimal('reward_cost', 12, 2)->nullable()->after('reward_cost_target')
                ->comment('Actual cost/value of the reward to display in campaign details');

            // Add key facts as JSON for campaign highlights
            $table->json('key_facts')->nullable()->after('reward_cost')
                ->comment('Key-value pairs for campaign facts display');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('campaigns', function (Blueprint $table) {
            $table->dropColumn(['reward_cost', 'key_facts']);
        });
    }
};
