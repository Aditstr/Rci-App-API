<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\NotificationResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    /**
     * Dapatkan daftar notifikasi untuk user terautentikasi.
     * 
     * GET /api/v1/notifications
     */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        
        $query = $user->notifications();

        if ($request->boolean('unread')) {
            $query->unread();
        }

        $notifications = $query->paginate(15);

        return response()->json([
            'success' => true,
            'message' => 'Daftar notifikasi berhasil dimuat.',
            'data'    => NotificationResource::collection($notifications)->response()->getData(true),
        ]);
    }

    /**
     * Tandai sebuah notifikasi sebagai terbaca.
     * 
     * POST /api/v1/notifications/{id}/read
     */
    public function markAsRead(Request $request, string $id): JsonResponse
    {
        $user = $request->user();
        
        $notification = $user->notifications()->findOrFail($id);
        
        $notification->markAsRead();

        return response()->json([
            'success' => true,
            'message' => 'Notifikasi berhasil ditandai sebagai terbaca.',
            'data'    => new NotificationResource($notification),
        ]);
    }

    /**
     * Tandai semua notifikasi user sebagai terbaca.
     * 
     * POST /api/v1/notifications/read-all
     */
    public function markAllAsRead(Request $request): JsonResponse
    {
        $user = $request->user();
        
        $user->unreadNotifications->markAsRead();

        return response()->json([
            'success' => true,
            'message' => 'Semua notifikasi berhasil ditandai sebagai terbaca.',
        ]);
    }

    /**
     * Dapatkan jumlah notifikasi yang belum terbaca.
     * 
     * GET /api/v1/notifications/unread-count
     */
    public function unreadCount(Request $request): JsonResponse
    {
        $user = $request->user();
        
        $count = $user->unreadNotifications()->count();

        return response()->json([
            'success' => true,
            'message' => 'Jumlah notifikasi belum terbaca.',
            'data'    => [
                'unread_count' => $count,
            ],
        ]);
    }
}
