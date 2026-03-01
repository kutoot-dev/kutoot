<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('stamps', function (Blueprint $table) {
            $table->string('status')->default('used')->after('source');
            $table->timestamp('reserved_at')->nullable()->after('status');
            $table->timestamp('expires_at')->nullable()->after('reserved_at');

            // Efficient lookup for reservation queries:
            // "find an available/reserved stamp for a campaign that hasn't expired"
            $table->index(['status', 'campaign_id', 'expires_at'], 'stamps_reservation_lookup');

            // Prevent duplicate active reservations per user+campaign
            $table->index(['user_id', 'campaign_id', 'status'], 'stamps_user_campaign_status');
        });
    }

    public function down(): void
    {
        Schema::table('stamps', function (Blueprint $table) {
            $table->dropIndex('stamps_reservation_lookup');
            $table->dropIndex('stamps_user_campaign_status');
            $table->dropColumn(['status', 'reserved_at', 'expires_at']);
        });
    }
};
