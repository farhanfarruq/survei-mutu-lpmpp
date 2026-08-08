<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['instrument_version_id', 'code', 'title', 'description', 'position', 'branch_rule'])]
class InstrumentSection extends Model
{
    use HasUuids;

    public function version(): BelongsTo
    {
        return $this->belongsTo(InstrumentVersion::class, 'instrument_version_id');
    }

    public function questions(): HasMany
    {
        return $this->hasMany(Question::class, 'section_id')->orderBy('position');
    }

    protected function casts(): array
    {
        return ['branch_rule' => 'array'];
    }
}
