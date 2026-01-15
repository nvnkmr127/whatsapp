<?php

namespace App\Livewire\Commerce;

use App\Models\Order;
use Livewire\Component;
use Livewire\WithPagination;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;

class OrderManager extends Component
{
    use WithPagination;

    public $search = '';
    public $statusFilter = '';

    public $viewingOrder = null;
    public $showDetailsModal = false;

    // Status Update
    public $newStatus = '';

    public function updatedSearch()
    {
        $this->resetPage();
    }

    public function updatedStatusFilter()
    {
        $this->resetPage();
    }

    public function viewDetails($orderId)
    {
        $this->viewingOrder = Order::where('team_id', Auth::user()->currentTeam->id)
            ->with('contact')
            ->findOrFail($orderId);

        $this->newStatus = $this->viewingOrder->status;
        $this->showDetailsModal = true;
    }

    public function updateStatus()
    {
        if (!$this->viewingOrder) {
            return;
        }

        $this->viewingOrder->update([
            'status' => $this->newStatus
        ]);

        // TODO: Trigger WhatsApp Notification based on status change (Future)

        $this->viewingOrder = $this->viewingOrder->fresh();
        session()->flash('flash.banner', 'Order status updated to ' . ucfirst($this->newStatus));
        session()->flash('flash.bannerStyle', 'success');
    }

    #[Layout('components.layouts.app')]
    public function render()
    {
        $query = Order::where('team_id', Auth::user()->currentTeam->id);

        if ($this->search) {
            $query->where(function ($q) {
                $q->where('order_id', 'like', '%' . $this->search . '%')
                    ->orWhereHas('contact', function ($c) {
                        $c->where('name', 'like', '%' . $this->search . '%')
                            ->orWhere('phone_number', 'like', '%' . $this->search . '%');
                    });
            });
        }

        if ($this->statusFilter) {
            $query->where('status', $this->statusFilter);
        }

        $orders = $query->orderBy('created_at', 'desc')->paginate(10);

        return view('livewire.commerce.order-manager', [
            'orders' => $orders
        ]);
    }
}
