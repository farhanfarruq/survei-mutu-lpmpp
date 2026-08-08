<?php

namespace App\Models;

use Database\Factories\SurveyPeriodFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

#[Fillable(['code', 'name', 'starts_on', 'ends_on', 'timezone', 'status'])]
class SurveyPeriod extends Model
{
    /** @use HasFactory<SurveyPeriodFactory> */
    use HasFactory, HasUuids, LogsActivity;

    public function surveys(): HasMany
    {
        return $this->hasMany(Survey::class);
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->logOnly(['code', 'name', 'starts_on', 'ends_on', 'timezone', 'status'])->logOnlyDirty()->dontLogEmptyChanges();
    }

    protected function casts(): array
    {
        return ['starts_on' => 'date', 'ends_on' => 'date'];
    }
}
