<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();

        return response()->json([
            'notifications' => $user->notifications()
                ->latest()
                ->limit(20)
                ->get()
                ->map(fn ($notification): array => [
                    'id' => $notification->id,
                    'title' => $notification->data['title'] ?? 'Notificación',
                    'message' => $notification->data['message'] ?? '',
                    'url' => $notification->data['url'] ?? null,
                    'kind' => $notification->data['kind'] ?? 'info',
                    'read_at' => $notification->read_at?->toIso8601String(),
                    'created_at' => $notification->created_at?->toIso8601String(),
                ])
                ->values()
                ->all(),
            'unread_count' => $user->unreadNotifications()->count(),
        ]);
    }

    public function read(Request $request, string $notification): JsonResponse
    {
        $user = $request->user();

        $user->notifications()
            ->whereKey($notification)
            ->firstOrFail()
            ->markAsRead();

        return response()->json([
            'ok' => true,
            'unread_count' => $user->unreadNotifications()->count(),
        ]);
    }

    public function readAll(Request $request): JsonResponse
    {
        $user = $request->user();

        $user->unreadNotifications()->update(['read_at' => now()]);

        return response()->json([
            'ok' => true,
            'unread_count' => 0,
        ]);
    }
}
