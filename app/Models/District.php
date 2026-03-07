<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class District extends Model
{
    protected $fillable = [
        'name',
        'sort_order',
        'coverage_areas',
        'home_cell_pastors',
        'home_cell_minister',
        'outreach_pastor',
        'outreach_minister',
        'outreach_location',
    ];

    protected $casts = [
        'home_cell_pastors' => 'array',
    ];

    public function zones(): HasMany
    {
        return $this->hasMany(HomeCellZone::class)->orderBy('sort_order')->orderBy('name');
    }
}
