<?php

namespace App\Livewire;

use Livewire\Component;
use Illuminate\Support\Facades\Auth;

class NotificationDropdown extends Component
{
    public $notifications = [];
    public $unreadCount = 0;
    public $isOpen = false;

    // Refresh notifications every 10 seconds while open, or just on load/events
    // For now, let's load on mount and support polling if needed, or stick to simple
    // lazy loading when opened.

    public function mount()
    {
        $this->loadNotifications();
    }

    public function loadNotifications()
    {
        if (Auth::check()) {
            // Get latest 10 notifications
            $this->notifications = Auth::user()->notifications()->take(10)->get();
            $this->unreadCount = Auth::user()->unreadNotifications()->count();
        }
    }

    public function markAsRead($notificationId)
    {
        $notification = Auth::user()->notifications()->find($notificationId);

        if ($notification) {
            $notification->markAsRead();
            $this->loadNotifications(); // Refresh list to update UI state
        }
    }

    public function markAllAsRead()
    {
        Auth::user()->unreadNotifications->markAsRead();
        $this->loadNotifications();
    }

    public function toggle()
    {
        $this->isOpen = !$this->isOpen;
        if ($this->isOpen) {
            $this->loadNotifications();
        }
    }

    public function render()
    {
        return view('livewire.notification-dropdown');
    }
}
