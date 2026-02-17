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
        Schema::create('qr_codes', function (Blueprint $table) {
            $table->id();
            $table->string('unique_code')->unique(); // KUT-1234
            $table->string('token')->unique(); // Secret tokens for URLs
            $table->foreignId('merchant_location_id')->nullable()->constrained('merchant_locations')->nullOnDelete();
            $table->boolean('status')->default(true); // true = active/available, false = inactive/linked
            $table->timestamp('linked_at')->nullable();
            $table->foreignId('linked_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('qr_codes');
    }
};
