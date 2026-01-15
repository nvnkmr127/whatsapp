<?php

namespace App\Livewire\Commerce;

use Livewire\Component;
use App\Models\Order;
use App\Models\Product;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;

class Dashboard extends Component
{
    public $stats = [];

    public function mount()
    {
        $teamId = Auth::user()->currentTeam->id;

        $this->stats = [
            'total_revenue' => Order::where('team_id', $teamId)->where('status', '!=', 'cancelled')->sum('total_amount'),
            'total_orders' => Order::where('team_id', $teamId)->count(),
            'pending_orders' => Order::where('team_id', $teamId)->where('status', 'pending')->count(),
            'total_products' => Product::where('team_id', $teamId)->count(),
        ];
    }

    #[Layout('layouts.app')]
    public function render()
    {
        return view('livewire.commerce.dashboard');
    }
}
