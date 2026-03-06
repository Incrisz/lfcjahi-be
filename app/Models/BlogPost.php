<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BlogPost extends Model
{
    protected $fillable = [
        'title',
        'slug',
        'excerpt',
        'content',
        'publish_date',
        'status',
    ];

    protected $casts = [
        'publish_date' => 'date',
    ];
}
