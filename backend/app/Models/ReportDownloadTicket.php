<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['report_export_id', 'requested_by', 'token_hash', 'expires_at', 'used_at'])]
class ReportDownloadTicket extends Model
{
    use HasUuids;

    public function export(): BelongsTo
    {
        return $this->belongsTo(ReportExport::class, 'report_export_id');
    }

    protected function casts(): array
    {
        return ['expires_at' => 'datetime', 'used_at' => 'datetime'];
    }
}
