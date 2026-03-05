<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('stamps', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('campaign_id')->constrained()->cascadeOnDelete();
            $table->foreignId('transaction_id')->nullable()->constrained()->nullOnDelete();
            $table->string('code')->unique();
            $table->string('source')->default('bill_payment');
            $table->dateTime('editable_until')->nullable();
            $table->string('status')->default('used');
            $table->timestamp('reserved_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();

            $table->index('source');
            $table->index('created_at');
            $table->index(['user_id', 'campaign_id']);
            $table->index(['status', 'campaign_id', 'expires_at'], 'stamps_reservation_lookup');
            $table->index(['user_id', 'campaign_id', 'status'], 'stamps_user_campaign_status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stamps');
    }
};
