<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('merchant_applications', function (Blueprint $table) {
            $table->id();

            // Store details
            $table->string('store_name');
            $table->string('store_type');

            // Contact
            $table->string('owner_mobile');
            $table->string('owner_email');
            $table->boolean('phone_verified')->default(false);
            $table->boolean('email_verified')->default(false);

            // Address & tax
            $table->text('address')->nullable();
            $table->string('gst_number')->nullable();
            $table->string('pan_number')->nullable();

            // Banking
            $table->string('bank_name')->nullable();
            $table->string('sub_bank_name')->nullable();
            $table->string('account_number')->nullable();
            $table->string('ifsc_code')->nullable();
            $table->string('upi_id')->nullable();

            // Processing
            $table->string('status')->default('pending');
            $table->text('admin_notes')->nullable();
            $table->foreignId('processed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('processed_at')->nullable();

            // Link to created location after approval
            $table->foreignId('merchant_location_id')->nullable()->constrained()->nullOnDelete();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('merchant_applications');
    }
};
