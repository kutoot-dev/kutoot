<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('campaigns', function (Blueprint $table) {
            // store the id of the primary/featured sponsor for quick lookups
            $table->foreignId('primary_sponsor_id')
                ->nullable()
                ->constrained('sponsors')
                ->nullOnDelete()
                ->after('category_id');

            $table->index('primary_sponsor_id');
        });

        // backfill existing data from the pivot table so we don't lose the
        // designation that was already stored in the many-to-many record.
        // We iterate through the campaigns rather than attempting a join so
        // the migration is compatible with SQLite (test environment).
        $campaigns = DB::table('campaigns')->select('id')->get();
        foreach ($campaigns as $campaign) {
            $primary = DB::table('campaign_sponsor')
                ->where('campaign_id', $campaign->id)
                ->where('is_primary', true)
                ->value('sponsor_id');

            if ($primary) {
                DB::table('campaigns')
                    ->where('id', $campaign->id)
                    ->update(['primary_sponsor_id' => $primary]);
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('campaigns', function (Blueprint $table) {
            $table->dropIndex(['primary_sponsor_id']);
            $table->dropForeign(['primary_sponsor_id']);
            $table->dropColumn('primary_sponsor_id');
        });
    }
};
