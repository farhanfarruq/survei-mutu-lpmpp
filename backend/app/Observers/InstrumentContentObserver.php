<?php

namespace App\Observers;

use App\Exceptions\DomainRuleViolation;
use App\Models\Category;
use App\Models\Indicator;
use App\Models\InstrumentSection;
use App\Models\InstrumentVersion;
use App\Models\Question;
use App\Models\QuestionOption;
use App\Models\Scale;
use App\Models\ScalePoint;
use Illuminate\Database\Eloquent\Model;

class InstrumentContentObserver
{
    public function saving(Model $model): void
    {
        $this->assertEditable($model);
    }

    public function deleting(Model $model): void
    {
        $this->assertEditable($model);
    }

    private function assertEditable(Model $model): void
    {
        $version = match (true) {
            $model instanceof InstrumentSection, $model instanceof Category, $model instanceof Scale => $model->version,
            $model instanceof Indicator => $model->category->version,
            $model instanceof ScalePoint => $model->scale->version,
            $model instanceof Question => $model->section->version,
            $model instanceof QuestionOption => $model->question->section->version,
            default => null,
        };

        if ($version instanceof InstrumentVersion && ! $version->isEditable()) {
            throw new DomainRuleViolation('instrument_content_locked', 'Konten hanya dapat diubah pada versi draft atau returned.');
        }
    }
}
