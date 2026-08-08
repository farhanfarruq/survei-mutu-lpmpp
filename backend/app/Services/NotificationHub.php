<?php

namespace App\Services;

use App\Jobs\SendGovernedNotification;
use App\Models\User;
use InvalidArgumentException;

final class NotificationHub
{
    private const EVENTS = ['survey_availability', 'survey_reminder', 'survey_closing', 'report_completion', 'ai_failure', 'low_response_rate', 'follow_up_deadline', 'verification_result'];

    public function send(User $user, string $eventType, string $title, string $message, ?string $route, array $context, string $objectKey): void
    {
        if (! in_array($eventType, self::EVENTS, true)) {
            throw new InvalidArgumentException('Notification event is not allowlisted.');
        }
        $encoded = json_encode([$title, $message, $context], JSON_THROW_ON_ERROR);
        if (preg_match('/response[_ -]?content|raw[_ -]?comment|api[_ -]?key|secret|password|answer/i', $encoded)) {
            throw new InvalidArgumentException('Notification contains forbidden data.');
        }
        $logicalKey = hash('sha256', implode('|', [$eventType, $user->id, $objectKey]));
        SendGovernedNotification::dispatch($user->id, $eventType, mb_substr($title, 0, 160), mb_substr($message, 0, 1000), $route, $context, $logicalKey);
    }
}
