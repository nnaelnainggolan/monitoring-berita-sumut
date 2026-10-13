<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class NewsItem extends Model
{
    protected $attributes = ['verification_status' => 'pending', 'review_version' => 0];

    protected $fillable = [
        'title',
        'summary',
        'link',
        'source',
        'published_at',
        'sentiment',
        'is_urgent',
        'analysis',
        'archived', 'category_id', 'region_id', 'agency_id', 'verification_status', 'ai_payload', 'verified_by', 'verified_at',
    ];

    protected $casts = [
        'published_at' => 'datetime',
        'is_urgent' => 'boolean',
        'archived' => 'boolean',
        'ai_payload' => 'array', 'verified_at' => 'datetime', 'review_version' => 'integer',
    ];

    public function issue()
    {
        return $this->belongsTo(Issue::class);
    }

    public function category()
    {
        return $this->belongsTo(Taxonomy::class, 'category_id');
    }

    public function region()
    {
        return $this->belongsTo(Taxonomy::class, 'region_id');
    }

    public function agency()
    {
        return $this->belongsTo(Taxonomy::class, 'agency_id');
    }

    public function reviews()
    {
        return $this->hasMany(NewsReview::class)->latest('id');
    }

    public function verifier()
    {
        return $this->belongsTo(User::class, 'verified_by');
    }
}
