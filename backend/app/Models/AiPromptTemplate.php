<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['use_case', 'version', 'system_prompt', 'output_schema', 'active', 'checksum', 'created_by'])]
class AiPromptTemplate extends Model
{
    use HasUuids;

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function jobs(): HasMany
    {
        return $this->hasMany(AiJob::class);
    }

    protected function casts(): array
    {
        return ['output_schema' => 'array', 'active' => 'boolean'];
    }
}
