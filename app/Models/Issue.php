<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class Issue extends Model
{
    public const STATUSES = ['new' => 'Baru', 'verified' => 'Diverifikasi', 'in_progress' => 'Ditindaklanjuti', 'resolved' => 'Selesai'];
    public const PRIORITIES = ['low' => 'Rendah', 'normal' => 'Normal', 'high' => 'Tinggi', 'critical' => 'Kritis'];
    public const PRIORITY_RANK = ['low' => 1, 'normal' => 2, 'high' => 3, 'critical' => 4];
    protected $fillable = ['title', 'summary', 'status', 'priority', 'created_by', 'assigned_to', 'category_id', 'region_id', 'agency_id', 'due_at', 'resolved_at'];
    protected $attributes = ['status' => 'new', 'priority' => 'normal', 'version' => 0];
    protected $casts = ['due_at' => 'datetime', 'resolved_at' => 'datetime', 'version' => 'integer', 'assigned_to' => 'integer', 'created_by' => 'integer'];
    public function assignee() { return $this->belongsTo(User::class, 'assigned_to'); }
    public function creator() { return $this->belongsTo(User::class, 'created_by'); }
    public function category() { return $this->belongsTo(Taxonomy::class, 'category_id'); }
    public function region() { return $this->belongsTo(Taxonomy::class, 'region_id'); }
    public function agency() { return $this->belongsTo(Taxonomy::class, 'agency_id'); }
    public function news() { return $this->hasMany(NewsItem::class); }
    public function activities() { return $this->hasMany(IssueActivity::class)->latest('id'); }
    public function isOverdue(): bool { return $this->status !== 'resolved' && $this->due_at?->isPast(); }
}
