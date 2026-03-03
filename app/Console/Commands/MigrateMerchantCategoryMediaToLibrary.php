<?php

namespace App\Console\Commands;

use App\Models\MerchantCategory;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class MigrateMerchantCategoryMediaToLibrary extends Command
{
    /**
     * @var string
     */
    protected $signature = 'app:migrate-merchant-category-media';

    /**
     * @var string
     */
    protected $description = 'Migrate existing merchant category image and icon files from disk columns into Spatie Media Library collections.';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $categories = DB::table('merchant_categories')
            ->where(function ($query) {
                $query->whereNotNull('image')->where('image', '!=', '')
                    ->orWhere(function ($q) {
                        $q->whereNotNull('icon')->where('icon', '!=', '');
                    });
            })
            ->get(['id', 'name', 'image', 'icon']);

        if ($categories->isEmpty()) {
            $this->info('No merchant category media to migrate.');

            return self::SUCCESS;
        }

        $this->info("Found {$categories->count()} merchant categor(ies) with media to migrate.");

        $migrated = 0;
        $skipped = 0;

        foreach ($categories as $row) {
            $category = MerchantCategory::find($row->id);

            if (! $category) {
                $this->warn("MerchantCategory #{$row->id} not found, skipping.");
                $skipped++;

                continue;
            }

            // Migrate image
            if (! empty($row->image) && ! $category->hasMedia('image')) {
                if (Storage::disk('public')->exists($row->image)) {
                    $category->addMediaFromDisk($row->image, 'public')
                        ->toMediaCollection('image');
                    $this->line("Migrated image for \"{$row->name}\".");
                    $migrated++;
                } else {
                    $this->warn("Image file not found for \"{$row->name}\": {$row->image}");
                    $skipped++;
                }
            } elseif (! empty($row->image)) {
                $this->line("Category \"{$row->name}\" already has a media image, skipping.");
                $skipped++;
            }

            // Migrate icon
            if (! empty($row->icon) && ! $category->hasMedia('icon')) {
                if (Storage::disk('public')->exists($row->icon)) {
                    $category->addMediaFromDisk($row->icon, 'public')
                        ->toMediaCollection('icon');
                    $this->line("Migrated icon for \"{$row->name}\".");
                    $migrated++;
                } else {
                    $this->warn("Icon file not found for \"{$row->name}\": {$row->icon}");
                    $skipped++;
                }
            } elseif (! empty($row->icon)) {
                $this->line("Category \"{$row->name}\" already has a media icon, skipping.");
                $skipped++;
            }
        }

        $this->info("Migration complete: {$migrated} migrated, {$skipped} skipped.");

        return self::SUCCESS;
    }
}
