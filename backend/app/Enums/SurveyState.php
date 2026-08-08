<?php

namespace App\Enums;

enum SurveyState: string
{
    case Draft = 'draft';
    case InReview = 'in_review';
    case Returned = 'returned';
    case Approved = 'approved';
    case Scheduled = 'scheduled';
    case Active = 'active';
    case Closed = 'closed';
    case Archived = 'archived';

    public function configurationEditable(): bool
    {
        return in_array($this, [self::Draft, self::Returned], true);
    }
}
