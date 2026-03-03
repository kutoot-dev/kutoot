<?php

namespace App\Console\Commands;

use App\Models\Sponsor;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class MigrateSponsorMediaToLibrary extends Command
{
    /**
     * @var string
     */
    protected $signature = 'app:migrate-sponsor-media';

    /**
     * @var string
     */
    protected $description = 'Migrate existing sponsor logo and banner files from disk columns into Spatie Media Library collections.';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $sponsors = DB::table('sponsors')
            ->where(function ($query) {
                $query->whereNotNull('logo')->where('logo', '!=', '')
                    ->orWhere(function ($q) {
                        $q->whereNotNull('banner')->where('banner', '!=', '');
                    });
            })
            ->get(['id', 'name', 'logo', 'banner']);

        if ($sponsors->isEmpty()) {
            $this->info('No sponsor media to migrate.');

            return self::SUCCESS;
        }

        $this->info("Found {$sponsors->count()} sponsor(s) with media to migrate.");

        $migrated = 0;
        $skipped = 0;

        foreach ($sponsors as $row) {
            $sponsor = Sponsor::find($row->id);

            if (! $sponsor) {
                $this->warn("Sponsor #{$row->id} not found, skipping.");
                $skipped++;

                continue;
            }

            // Migrate logo
            if (! empty($row->logo) && ! $sponsor->hasMedia('logo')) {
                if (Storage::disk('public')->exists($row->logo)) {
                    $sponsor->addMediaFromDisk($row->logo, 'public')
                        ->toMediaCollection('logo');
                    $this->line("Migrated logo for \"{$row->name}\".");
                    $migrated++;
                } else {
                    $this->warn("Logo file not found for \"{$row->name}\": {$row->logo}");
                    $skipped++;
                }
            } elseif (! empty($row->logo)) {
                $this->line("Sponsor \"{$row->name}\" already has a media logo, skipping.");
                $skipped++;
            }

            // Migrate banner
            if (! empty($row->banner) && ! $sponsor->hasMedia('banner')) {
                if (Storage::disk('public')->exists($row->banner)) {
                    $sponsor->addMediaFromDisk($row->banner, 'public')
                        ->toMediaCollection('banner');
                    $this->line("Migrated banner for \"{$row->name}\".");
                    $migrated++;
                } else {
                    $this->warn("Banner file not found for \"{$row->name}\": {$row->banner}");
                    $skipped++;
                }
            } elseif (! empty($row->banner)) {
                $this->line("Sponsor \"{$row->name}\" already has a media banner, skipping.");
                $skipped++;
            }
        }

        $this->info("Migration complete: {$migrated} migrated, {$skipped} skipped.");

        return self::SUCCESS;
    }
}
