<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    use ApiResponse;

    /**
     * List notifications for the authenticated user.
     * GET /api/v1/notifications
     */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        $perPage = min((int) ($request->per_page ?? 20), 50);

        $notifications = $user->notifications()->paginate($perPage);

        return $this->success('Notifications retrieved successfully', [
            'notifications' => $notifications->items(),
            'unread_count'  => $user->unreadNotifications()->count(),
            'pagination'    => [
                'current_page' => $notifications->currentPage(),
                'last_page'    => $notifications->lastPage(),
                'per_page'     => $notifications->perPage(),
                'total'        => $notifications->total(),
            ],
        ]);
    }

    /**
     * Get unread notifications count.
     * GET /api/v1/notifications/unread-count
     */
    public function unreadCount(Request $request): JsonResponse
    {
        return $this->success('Unread count retrieved', [
            'unread_count' => $request->user()->unreadNotifications()->count(),
        ]);
    }

    /**
     * Mark a single notification as read.
     * PUT /api/v1/notifications/{id}/read
     */
    public function markAsRead(Request $request, string $id): JsonResponse
    {
        $notification = $request->user()->notifications()->find($id);

        if (!$notification) {
            return $this->notFound('Notification not found');
        }

        $notification->markAsRead();

        return $this->success('Notification marked as read');
    }

    /**
     * Mark all notifications as read.
     * PUT /api/v1/notifications/read-all
     */
    public function markAllAsRead(Request $request): JsonResponse
    {
        $request->user()->unreadNotifications()->update(['read_at' => now()]);

        return $this->success('All notifications marked as read');
    }
}
