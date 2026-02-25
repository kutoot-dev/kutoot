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
        Schema::table('users', function (Blueprint $table) {
            // personal information
            $table->string('gender')->nullable()->after('name');
            $table->string('country')->nullable()->after('mobile');
            $table->string('state')->nullable()->after('country');
            $table->string('city')->nullable()->after('state');
            $table->string('pin_code')->nullable()->after('city');
            $table->text('full_address')->nullable()->after('pin_code');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'gender',
                'country',
                'state',
                'city',
                'pin_code',
                'full_address',
            ]);
        });
    }
};
