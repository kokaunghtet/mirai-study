<?php

namespace App\Http\Controllers;

use App\Models\Notification;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    public function index(Request $request)
    {
        $notifications = $request->user()
            ->appNotifications()
            ->with('sender')
            ->latest('created_at')
            ->paginate(20);

        return view('notifications.index', compact('notifications'));
    }

    public function markRead(Request $request, Notification $notification)
    {
        // Only mark your own notifications
        abort_if($notification->user_id !== $request->user()->id, 403);

        $notification->markAsRead();

        return back();
    }

    public function markAllRead(Request $request)
    {
        $request->user()
            ->appNotifications()
            ->whereNull('read_at')
            ->update(['read_at' => now()]);

        return back()->with('success', 'All notifications marked as read.');
    }
}
