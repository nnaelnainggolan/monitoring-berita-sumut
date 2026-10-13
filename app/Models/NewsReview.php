<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class NewsReview extends Model
{
    protected $fillable = ['news_item_id', 'user_id', 'before', 'after', 'note'];

    protected $casts = ['before' => 'array', 'after' => 'array'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
