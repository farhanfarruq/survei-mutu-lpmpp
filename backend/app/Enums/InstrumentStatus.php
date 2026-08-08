<?php

namespace App\Enums;

enum InstrumentStatus: string
{
    case Draft = 'draft';
    case InReview = 'in_review';
    case Returned = 'returned';
    case Approved = 'approved';
    case Retired = 'retired';

    public function editable(): bool
    {
        return in_array($this, [self::Draft, self::Returned], true);
    }
}
