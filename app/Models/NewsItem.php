<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class NewsItem extends Model
{
    protected $fillable = [
        'title',
        'link',
        'source',
        'published_at',
        'sentiment',
        'is_urgent',
        'analysis',
        'archived',
    ];

    protected $casts = [
        'published_at' => 'datetime',
        'is_urgent'     => 'boolean',
        'archived'      => 'boolean',
    ];
}
