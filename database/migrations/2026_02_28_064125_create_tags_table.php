<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tags', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->timestamps();
        });

        Schema::create('merchant_location_tag', function (Blueprint $table) {
            $table->foreignId('merchant_location_id')->constrained()->cascadeOnDelete();
            $table->foreignId('tag_id')->constrained()->cascadeOnDelete();
            $table->primary(['merchant_location_id', 'tag_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('merchant_location_tag');
        Schema::dropIfExists('tags');
    }
};
