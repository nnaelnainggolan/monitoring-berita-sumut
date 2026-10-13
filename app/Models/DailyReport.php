<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DailyReport extends Model
{
    protected $fillable = ['report_date', 'data', 'generated_by', 'generated_at'];

    protected $casts = ['report_date' => 'date', 'data' => 'array', 'generated_at' => 'datetime'];
}
