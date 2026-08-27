<?php

namespace App\Models;

use Database\Factories\QuestionBankEntryFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

#[Fillable(['owner_unit_id', 'code', 'family_code', 'method', 'category_label', 'indicator_label', 'item_text', 'response_type', 'help_text', 'measurement_purpose', 'default_options', 'is_active', 'is_default', 'created_by'])]
class QuestionBankEntry extends Model
{
    /** @use HasFactory<QuestionBankEntryFactory> */
    use HasFactory, HasUuids, LogsActivity;

    public function ownerUnit(): BelongsTo
    {
        return $this->belongsTo(OrganizationalUnit::class, 'owner_unit_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function questions(): HasMany
    {
        return $this->hasMany(Question::class);
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->logOnly(['owner_unit_id', 'code', 'family_code', 'method', 'category_label', 'indicator_label', 'item_text', 'response_type', 'help_text', 'measurement_purpose', 'default_options', 'is_active', 'is_default'])->logOnlyDirty()->dontLogEmptyChanges();
    }

    protected function casts(): array
    {
        return ['default_options' => 'array', 'is_active' => 'boolean', 'is_default' => 'boolean'];
    }
}
