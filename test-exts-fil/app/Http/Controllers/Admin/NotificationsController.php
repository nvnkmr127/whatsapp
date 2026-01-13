<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Notification;
use Carbon\Carbon;

class NotificationsController extends Controller
{
    /**
     * Display a listing of notifications.
     */
    public function index()
    {
        $notifications = auth()->user()->notifications()
            ->latest()
            ->paginate(10);

        $groupedNotifications = $notifications->groupBy(function ($notification) {
            $createdAt = Carbon::parse($notification->created_at);
            if ($createdAt->isToday()) {
                return t('today');
            } elseif ($createdAt->isYesterday()) {
                return t('yesterday');
            } else {
                return $createdAt->format('F d, Y');
            }
        });

        foreach ($groupedNotifications as $date => $items) {
            foreach ($items as $notification) {
                $notification->time_ago = $this->getTimeAgo($notification->created_at);
                $notification->icon     = $this->getNotificationIcon($notification->type);
            }
        }

        if (request()->wantsJson()) {
            return response()->json($groupedNotifications);
        }

        return view('notifications.index', compact('groupedNotifications'));
    }

    /**
     * Get unread notifications count
     */
    public function getUnreadCount()
    {
        $count = auth()->user()->unreadNotifications()->count();

        return response()->json(['count' => $count]);
    }

    /**
     * Mark notification as read
     */
    public function markAsRead($id)
    {
        $notification = auth()->user()->notifications()->findOrFail($id);
        $notification->markAsRead();

        return response()->json([
            'status'  => 'success',
            'message' => t('notification_marked_as_read'),
        ]);
    }

    /**
     * Mark all notifications as read
     */
    public function markAllAsRead()
    {
        auth()->user()->unreadNotifications->markAsRead();

        return response()->json([
            'status'  => 'success',
            'message' => t('all_notification_marked_as_read'),
        ]);
    }

    /**
     * Delete a notification
     */
    public function destroy($id)
    {
        $notification = auth()->user()->notifications()->findOrFail($id);
        $notification->delete();

        return response()->json([
            'status'  => 'success',
            'message' => t('notification_delete_successfully'),
        ]);
    }

    /**
     * Clear all notifications
     */
    public function clearAll()
    {
        auth()->user()->notifications()->delete();

        return response()->json([
            'status'  => 'success',
            'message' => t('all_notification_cleared'),
        ]);
    }

    /**
     * Get formatted time ago string
     */
    private function getTimeAgo($date)
    {
        $date = Carbon::parse($date);
        $now  = Carbon::now();
        $diff = $date->diffInSeconds($now);

        if ($diff < 60) {
            return t('just_now');
        } elseif ($diff < 3600) {
            $minutes = floor($diff / 60);

            return $minutes . t('min_ago');
        } elseif ($diff < 86400) {
            $hours = floor($diff / 3600);

            return $hours . t('hours_ago');
        } else {
            $days = floor($diff / 86400);

            return $days . t('days_ago');
        }
    }

    /**
     * Get icon class based on notification type
     */
    private function getNotificationIcon($type)
    {
        return match ($type) {
            'comment' => 'mgc_message_3_line',
            'message' => 'mgc_message_1_line',
            'alert'   => 'mgc_alert_line',
            'success' => 'mgc_check_circle_line',
            'error'   => 'mgc_close_circle_line',
            default   => 'mgc_notification_line',
        };
    }

    /**
     * Get recent notifications for dropdown
     */
    public function getRecentNotifications()
    {
        $notifications = auth()->user()->notifications()
            ->latest()
            ->take(5)
            ->get()
            ->map(function ($notification) {
                return [
                    'id'      => $notification->id,
                    'title'   => 'Datacorp',
                    'message' => $notification->data['message'] ?? '',
                    'time'    => $this->getTimeAgo($notification->created_at),
                    'icon'    => $this->getNotificationIcon($notification->type),
                    'read'    => ! is_null($notification->read_at),
                    'group'   => $this->getDateGroup($notification->created_at),
                ];
            });

        return response()->json($notifications);
    }

    /**
     * Get date group for notification
     */
    private function getDateGroup($date)
    {
        $date = Carbon::parse($date);
        if ($date->isToday()) {
            return t('today');
        } elseif ($date->isYesterday()) {
            return t('yesterday');
        } else {
            return $date->format('F d, Y');
        }
    }
}
