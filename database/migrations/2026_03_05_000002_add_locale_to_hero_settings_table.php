<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('hero_settings', function (Blueprint $table) {
            // add a locale column after `is_active` so existing structure is preserved
            // make it non-nullable with a default so this migration can run on real data
            $table->string('locale')
                  ->default(config('app.locale'))
                  ->after('is_active')
                  ->index();
        });

        // backfill any existing rows with the default application locale
        DB::table('hero_settings')
            ->whereNull('locale')
            ->orWhere('locale', '')
            ->update(['locale' => config('app.locale')]);
    }

    public function down(): void
    {
        Schema::table('hero_settings', function (Blueprint $table) {
            $table->dropIndex(['locale']);
            $table->dropColumn('locale');
        });
    }
};
