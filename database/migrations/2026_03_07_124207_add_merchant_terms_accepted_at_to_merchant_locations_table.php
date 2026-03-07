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
        Schema::table('merchant_locations', function (Blueprint $table) {
            $table->timestamp('terms_accepted_at')->nullable();
            $table->unsignedBigInteger('terms_version_id')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('merchant_locations', function (Blueprint $table) {
            $table->dropColumn(['terms_accepted_at', 'terms_version_id']);
        });
    }
};
