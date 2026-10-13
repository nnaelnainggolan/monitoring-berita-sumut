<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Taxonomy extends Model
{
    public const KINDS = ['category' => 'Kategori', 'region' => 'Wilayah', 'agency' => 'OPD'];

    protected $fillable = ['kind', 'name', 'is_active'];
    protected $casts = ['is_active' => 'boolean'];
}
