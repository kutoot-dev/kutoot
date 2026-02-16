<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CampaignCategory extends Model
{
    /** @use HasFactory<\Database\Factories\CampaignCategoryFactory> */
    use HasFactory;

    /**
     * @return HasMany<Campaign, $this>
     */
    public function campaigns(): HasMany
    {
        return $this->hasMany(Campaign::class, 'category_id');
    }
}
