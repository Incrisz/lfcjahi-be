<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Event extends Model
{
    protected $fillable = [
        'name',
        'event_date',
        'description',
        'media_url',
        'registration_enabled',
    ];

    protected $casts = [
        'event_date' => 'date',
        'registration_enabled' => 'boolean',
    ];
}
