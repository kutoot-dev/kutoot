<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Deferred foreign keys for columns referencing tables that are
 * created after the parent table's migration runs.
 */
class AddDeferredForeignKeys extends Migration
{
    public function up(): void
    {
        // users.primary_campaign_id → campaigns (users created before campaigns)
        Schema::table('users', function (Blueprint $table) {
            $sm = Schema::getConnection()->getDoctrineSchemaManager();
            $existing = collect($sm->listTableForeignKeys('users'))
                ->map(fn($fk) => $fk->getName())
                ->all();
            if (! in_array('users_primary_campaign_id_foreign', $existing, true)) {
                $table->foreign('primary_campaign_id')
                    ->references('id')->on('campaigns')
                    ->nullOnDelete();
            }
        });

        // merchant_locations.merchant_category_id → merchant_categories (locations created before categories)
        Schema::table('merchant_locations', function (Blueprint $table) {
            $sm = Schema::getConnection()->getDoctrineSchemaManager();
            $existing = collect($sm->listTableForeignKeys('merchant_locations'))
                ->map(fn($fk) => $fk->getName())
                ->all();
            if (! in_array('merchant_locations_merchant_category_id_foreign', $existing, true)) {
                $table->foreign('merchant_category_id')
                    ->references('id')->on('merchant_categories')
                    ->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['primary_campaign_id']);
        });

        Schema::table('merchant_locations', function (Blueprint $table) {
            $table->dropForeign(['merchant_category_id']);
        });
    }
}
