<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class HomeCellZone extends Model
{
    protected $fillable = [
        'district_id',
        'name',
        'zone_minister',
        'sort_order',
    ];

    public function district(): BelongsTo
    {
        return $this->belongsTo(District::class);
    }

    public function cells(): HasMany
    {
        return $this->hasMany(HomeCell::class)->orderBy('sort_order')->orderBy('name');
    }
}
