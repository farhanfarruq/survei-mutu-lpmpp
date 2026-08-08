<?php

namespace App\Jobs;

use App\Models\NotificationDelivery;
use App\Models\User;
use App\Notifications\GovernedSystemNotification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Cache;

class SendGovernedNotification implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public array $backoff = [10, 60, 300];

    public function __construct(public readonly int $userId, public readonly string $eventType, public readonly string $title, public readonly string $message, public readonly ?string $route, public readonly array $context, public readonly string $logicalKey) {}

    public function handle(): void
    {
        $user = User::find($this->userId);
        if (! $user) {
            return;
        }
        $notification = new GovernedSystemNotification($this->eventType, $this->title, $this->message, $this->route, $this->context);
        $failures = [];
        foreach (['database', 'mail'] as $channel) {
            Cache::lock("notification:{$this->logicalKey}:{$channel}", 30)->block(2, function () use ($user, $notification, $channel, &$failures): void {
                $delivery = NotificationDelivery::firstOrNew(['user_id' => $user->id, 'channel' => $channel, 'logical_key' => $this->logicalKey]);
                if ($delivery->state === 'sent') {
                    return;
                }
                $delivery->fill([
                    'event_type' => $this->eventType,
                    'state' => 'processing',
                    'failure_code' => null,
                    'attempt_count' => ((int) $delivery->attempt_count) + 1,
                    'last_attempt_at' => now(),
                ])->save();
                try {
                    $user->notifyNow($notification, [$channel]);
                    $delivery->update(['state' => 'sent', 'sent_at' => now()]);
                } catch (\Throwable $error) {
                    $delivery->update(['state' => 'failed', 'failure_code' => class_basename($error)]);
                    $failures[] = $channel;
                }
            });
        }
        if ($failures !== []) {
            throw new \RuntimeException('Notification delivery failed for: '.implode(', ', $failures));
        }
    }
}
