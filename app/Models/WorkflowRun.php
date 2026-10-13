<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class WorkflowRun extends Model
{
    use HasUuids;

    public const LABELS = ['requested' => 'Diminta', 'accepted' => 'Webhook diterima', 'running' => 'Sedang berjalan', 'succeeded' => 'Selesai', 'failed' => 'Gagal', 'unknown' => 'Belum ada konfirmasi'];

    protected $fillable = ['id', 'triggered_by', 'status', 'execution_id', 'received_count', 'message', 'started_at', 'finished_at', 'heartbeat_at'];

    protected $casts = ['started_at' => 'datetime', 'finished_at' => 'datetime', 'heartbeat_at' => 'datetime', 'received_count' => 'integer'];

    public function user()
    {
        return $this->belongsTo(User::class, 'triggered_by');
    }

    public function isTerminal(): bool
    {
        return in_array($this->status, ['succeeded', 'failed'], true);
    }
}
