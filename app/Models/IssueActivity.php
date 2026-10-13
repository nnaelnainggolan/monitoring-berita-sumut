<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class IssueActivity extends Model
{
    protected $fillable = ['issue_id', 'user_id', 'kind', 'note', 'changes'];
    protected $casts = ['changes' => 'array'];
    public function user() { return $this->belongsTo(User::class); }
}
