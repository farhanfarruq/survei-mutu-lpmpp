<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Notifications\DatabaseNotification;

class NotificationController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $notifications = $request->user()->notifications()->latest()->limit(100)->get();

        return response()->json([
            'data' => $notifications->map(fn (DatabaseNotification $notification) => [
                'id' => $notification->id,
                'type' => $notification->data['event_type'] ?? 'system',
                'title' => $notification->data['title'] ?? 'Notifikasi',
                'message' => $notification->data['message'] ?? '',
                'route' => $notification->data['route'] ?? null,
                'context' => $notification->data['context'] ?? [],
                'read_at' => $notification->read_at?->toIso8601String(),
                'created_at' => $notification->created_at?->toIso8601String(),
            ]),
            'meta' => ['unread' => $request->user()->unreadNotifications()->count()],
        ]);
    }

    public function read(Request $request, string $notification): JsonResponse
    {
        $record = $request->user()->notifications()->findOrFail($notification);
        $record->markAsRead();

        return response()->json(['data' => ['id' => $record->id, 'read_at' => $record->read_at?->toIso8601String()]]);
    }
}
