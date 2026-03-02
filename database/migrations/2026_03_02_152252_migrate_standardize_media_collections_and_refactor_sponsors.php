<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use App\Models\Sponsor;
use Illuminate\Support\Facades\Storage;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Standardize existing media collections
        DB::table('media')
            ->whereIn('collection_name', ['image', 'media'])
            ->update(['collection_name' => 'images']);

        // 2. Migrate Sponsors to Spatie Media Library
        $sponsors = Sponsor::all();

        foreach ($sponsors as $sponsor) {
            // Migrate logo
            if ($sponsor->logo && Storage::disk('public')->exists($sponsor->logo)) {
                try {
                    $sponsor->addMediaFromDisk($sponsor->logo, 'public')
                        ->toMediaCollection('logo');
                } catch (\Exception $e) {
                    logger()->error("Failed to migrate logo for Sponsor ID {$sponsor->id}: " . $e->getMessage());
                }
            }

            // Migrate banner
            if ($sponsor->banner && Storage::disk('public')->exists($sponsor->banner)) {
                try {
                    $sponsor->addMediaFromDisk($sponsor->banner, 'public')
                        ->toMediaCollection('banner');
                } catch (\Exception $e) {
                    logger()->error("Failed to migrate banner for Sponsor ID {$sponsor->id}: " . $e->getMessage());
                }
            }
        }

        // 3. Drop old columns from sponsors
        Schema::table('sponsors', function (Blueprint $table) {
            $table->dropColumn(['logo', 'banner']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('sponsors', function (Blueprint $table) {
            $table->string('logo')->nullable();
            $table->string('banner')->nullable();
        });

        // Mapping back is difficult because we lost original paths, 
        // but generally Spatie is the target state.
        
        DB::table('media')
            ->where('collection_name', 'images')
            ->update(['collection_name' => 'image']);
    }
};
