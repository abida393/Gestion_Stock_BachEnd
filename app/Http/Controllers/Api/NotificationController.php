<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\NotificationResource;
use App\Models\Notification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class NotificationController extends Controller
{
    /**
     * Get current user's notifications
     */
    public function index()
    {
        return NotificationResource::collection(
            Auth::user()->notifications()->latest()->paginate(20)
        );
    }

    /**
     * Mark notification as read (lu=true)
     */
    public function markAsRead(Notification $notification)
    {
        if ($notification->utilisateur_id !== Auth::id()) {
            abort(403);
        }
        
        $notification->update(['lu' => true]);
        
        return new NotificationResource($notification);
    }

    /**
     * Mark all as read
     */
    public function markAllAsRead()
    {
        Auth::user()->notifications()->where('lu', false)->update(['lu' => true]);
        
        return response()->json([
            'message' => 'Toutes les notifications ont été marquées comme lues'
        ]);
    }

    /**
     * Count of unread (for badge)
     */
    public function unreadCount()
    {
        return response()->json([
            'count' => Auth::user()->notifications()->where('lu', false)->count()
        ]);
    }

    /**
     * Delete notification
     */
    public function destroy(Notification $notification)
    {
        if ($notification->utilisateur_id !== Auth::id()) {
            abort(403);
        }
        
        $notification->delete();
        
        return response()->json(null, 204);
    }
}
