<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['section_id', 'indicator_id', 'scale_id', 'question_bank_entry_id', 'code', 'item_text', 'response_type', 'is_required', 'position', 'help_text', 'validation_rules', 'branch_rule', 'measurement_purpose', 'method', 'pair_code'])]
class Question extends Model
{
    use HasUuids;

    public function section(): BelongsTo
    {
        return $this->belongsTo(InstrumentSection::class);
    }

    public function indicator(): BelongsTo
    {
        return $this->belongsTo(Indicator::class);
    }

    public function scale(): BelongsTo
    {
        return $this->belongsTo(Scale::class);
    }

    public function bankEntry(): BelongsTo
    {
        return $this->belongsTo(QuestionBankEntry::class, 'question_bank_entry_id');
    }

    public function options(): HasMany
    {
        return $this->hasMany(QuestionOption::class)->orderBy('position');
    }

    protected function casts(): array
    {
        return ['is_required' => 'boolean', 'validation_rules' => 'array', 'branch_rule' => 'array'];
    }
}
