<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['provider', 'model', 'base_url', 'secret_ciphertext', 'enabled', 'max_input_tokens', 'max_output_tokens', 'max_cost_micros', 'input_cost_micros_per_1k', 'output_cost_micros_per_1k', 'timeout_seconds', 'rate_limit_per_minute', 'connection_status', 'last_tested_at', 'created_by'])]
class AiProviderConfig extends Model
{
    use HasUuids;

    protected $hidden = ['secret_ciphertext'];

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
        return ['secret_ciphertext' => 'encrypted', 'enabled' => 'boolean', 'last_tested_at' => 'datetime'];
    }
}
