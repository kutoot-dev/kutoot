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
            try {
                $table->foreign('primary_campaign_id')
                    ->references('id')->on('campaigns')
                    ->nullOnDelete();
            } catch (\Illuminate\Database\QueryException $e) {
                // Ignore if foreign key already exists (error 1826 or duplicate constraint)
                if (str_contains($e->getMessage(), 'Duplicate foreign key') ||
                    str_contains($e->getMessage(), '1826')) {
                    return;
                }
                throw $e;
            }
        });

        // merchant_locations.merchant_category_id → merchant_categories (locations created before categories)
        Schema::table('merchant_locations', function (Blueprint $table) {
            try {
                $table->foreign('merchant_category_id')
                    ->references('id')->on('merchant_categories')
                    ->nullOnDelete();
            } catch (\Illuminate\Database\QueryException $e) {
                // Ignore if foreign key already exists (error 1826 or duplicate constraint)
                if (str_contains($e->getMessage(), 'Duplicate foreign key') ||
                    str_contains($e->getMessage(), '1826')) {
                    return;
                }
                throw $e;
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
