<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('subscription_plans', function (Blueprint $table) {
            $table->unsignedInteger('sort_order')->default(0)->after('name');
        });

        // Set initial sort_order: default plan = 0, others by price ascending
        $plans = \App\Models\SubscriptionPlan::orderBy('is_default', 'desc')
            ->orderBy('price', 'asc')
            ->get();

        $order = 0;
        foreach ($plans as $plan) {
            $plan->update(['sort_order' => $order]);
            $order++;
        }
    }

    public function down(): void
    {
        Schema::table('subscription_plans', function (Blueprint $table) {
            $table->dropColumn('sort_order');
        });
    }
};
