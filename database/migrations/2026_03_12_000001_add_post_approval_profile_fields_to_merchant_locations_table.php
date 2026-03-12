<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('merchant_locations', function (Blueprint $table) {
            $table->string('store_email')->nullable()->after('branch_name');
            $table->string('year_of_establishment')->nullable()->after('store_email');
            $table->string('business_ownership_type')->nullable()->after('year_of_establishment');

            $table->string('area_locality')->nullable()->after('address');
            $table->string('city_name')->nullable()->after('area_locality');
            $table->string('state_name')->nullable()->after('city_name');
            $table->string('pin_code', 20)->nullable()->after('state_name');
            $table->text('google_maps_link')->nullable()->after('pin_code');

            $table->string('owner_name')->nullable()->after('google_maps_link');
            $table->string('owner_mobile_whatsapp')->nullable()->after('owner_name');
            $table->string('owner_email')->nullable()->after('owner_mobile_whatsapp');

            $table->boolean('has_business_partner')->default(false)->after('owner_email');
            $table->string('partner_name')->nullable()->after('has_business_partner');
            $table->string('partner_mobile')->nullable()->after('partner_name');
            $table->string('partner_role')->nullable()->after('partner_mobile');

            $table->string('average_monthly_sales_range')->nullable()->after('partner_role');
            $table->string('average_profit_margin_range')->nullable()->after('average_monthly_sales_range');
            $table->string('kutoot_customer_discount_offer')->nullable()->after('average_profit_margin_range');
            $table->boolean('exclusive_discount_for_kutoot')->nullable()->after('kutoot_customer_discount_offer');
            $table->string('max_discount_policy')->nullable()->after('exclusive_discount_for_kutoot');
            $table->decimal('minimum_bill_amount_for_discount', 12, 2)->nullable()->after('max_discount_policy');

            $table->string('creative_consent')->nullable()->after('minimum_bill_amount_for_discount');
            $table->json('requested_creatives')->nullable()->after('creative_consent');

            $table->string('gst_registration_status')->nullable()->after('requested_creatives');
            $table->string('preferred_settlement_method')->nullable()->after('gst_registration_status');
            $table->text('settlement_details')->nullable()->after('preferred_settlement_method');

            $table->boolean('declaration_accepted')->default(false)->after('settlement_details');
            $table->boolean('communication_consent')->default(false)->after('declaration_accepted');
            $table->text('additional_comments')->nullable()->after('communication_consent');

            $table->timestamp('profile_completed_at')->nullable()->after('additional_comments');
            $table->foreignId('profile_completed_by')->nullable()->after('profile_completed_at')->constrained('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('merchant_locations', function (Blueprint $table) {
            $table->dropConstrainedForeignId('profile_completed_by');
            $table->dropColumn([
                'store_email',
                'year_of_establishment',
                'business_ownership_type',
                'area_locality',
                'city_name',
                'state_name',
                'pin_code',
                'google_maps_link',
                'owner_name',
                'owner_mobile_whatsapp',
                'owner_email',
                'has_business_partner',
                'partner_name',
                'partner_mobile',
                'partner_role',
                'average_monthly_sales_range',
                'average_profit_margin_range',
                'kutoot_customer_discount_offer',
                'exclusive_discount_for_kutoot',
                'max_discount_policy',
                'minimum_bill_amount_for_discount',
                'creative_consent',
                'requested_creatives',
                'gst_registration_status',
                'preferred_settlement_method',
                'settlement_details',
                'declaration_accepted',
                'communication_consent',
                'additional_comments',
                'profile_completed_at',
            ]);
        });
    }
};
