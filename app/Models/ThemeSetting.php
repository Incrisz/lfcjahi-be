<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ThemeSetting extends Model
{
    protected $fillable = [
        'church_name',
        'logo_url',
        'tagline',
        'primary_color',
        'accent_color',
        'font_family',
        'layout_style',
    ];
}
