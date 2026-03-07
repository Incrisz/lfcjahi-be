<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HomeCell extends Model
{
    protected $fillable = [
        'home_cell_zone_id',
        'name',
        'address',
        'minister',
        'phone',
        'sort_order',
    ];

    public function zone(): BelongsTo
    {
        return $this->belongsTo(HomeCellZone::class, 'home_cell_zone_id');
    }
}
