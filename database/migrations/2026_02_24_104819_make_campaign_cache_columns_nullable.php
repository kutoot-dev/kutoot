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
            $table->decimal('collected_commission_cache', 15, 2)->nullable()->change();
            $table->bigInteger('issued_stamps_cache')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('campaigns', function (Blueprint $table) {
            $table->decimal('collected_commission_cache', 15, 2)->nullable(false)->change();
            $table->bigInteger('issued_stamps_cache')->nullable(false)->change();
        });
    }
};
