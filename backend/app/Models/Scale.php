<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['instrument_version_id', 'code', 'name', 'scale_type', 'min_value', 'max_value', 'na_allowed', 'missing_policy'])]
class Scale extends Model
{
    use HasUuids;

    public function version(): BelongsTo
    {
        return $this->belongsTo(InstrumentVersion::class, 'instrument_version_id');
    }

    public function points(): HasMany
    {
        return $this->hasMany(ScalePoint::class)->orderBy('position');
    }

    public function questions(): HasMany
    {
        return $this->hasMany(Question::class);
    }

    protected function casts(): array
    {
        return ['min_value' => 'decimal:6', 'max_value' => 'decimal:6', 'na_allowed' => 'boolean'];
    }
}
