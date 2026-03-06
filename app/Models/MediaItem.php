<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MediaItem extends Model
{
    protected $fillable = [
        'title',
        'description',
        'category',
        'subcategory',
        'speaker',
        'media_date',
        'thumbnail_url',
        'media_url',
        'media_source_type',
        'is_published',
    ];

    protected $casts = [
        'media_date' => 'date',
        'is_published' => 'boolean',
    ];
}
