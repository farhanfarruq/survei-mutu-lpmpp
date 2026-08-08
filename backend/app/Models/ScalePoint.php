<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['scale_id', 'code', 'numeric_value', 'label', 'position', 'is_na', 'is_neutral'])]
class ScalePoint extends Model
{
    use HasUuids;

    public function scale(): BelongsTo
    {
        return $this->belongsTo(Scale::class);
    }

    protected function casts(): array
    {
        return ['numeric_value' => 'decimal:6', 'is_na' => 'boolean', 'is_neutral' => 'boolean'];
    }
}
