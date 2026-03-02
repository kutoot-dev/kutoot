<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Sponsor extends Model
{
    /** @use HasFactory<\Database\Factories\SponsorFactory> */
    use HasFactory, \App\Traits\HasImageUrls;

    protected $fillable = [
        'name',
        'type',
        'logo',
        'banner',
        'link',
        'serial',
        'is_active',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'serial' => 'integer',
        ];
    }
}
