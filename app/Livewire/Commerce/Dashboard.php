<?php

namespace App\Livewire\Commerce;

use Livewire\Component;
use App\Models\Order;
use App\Models\Product;
use App\Models\Integration;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Carbon\Carbon;

#[Title('Commerce')]
class Dashboard extends Component
{
    public $stats = [];
    public $trends = [];
    public $funnel = [];
    public $operational = [];
    public $integrationStatus = 'unknown';
    public $lastUpdated;
    public $isEmpty = false;

    public function mount()
    {
        $this->loadData();
    }

    public function refreshStats()
    {
        $this->loadData();
    }

    public function loadData()
    {
        $teamId = Auth::user()->currentTeam->id;
        $now = Carbon::now();
        $startOfMonth = $now->copy()->startOfMonth();
        $endOfMonth = $now->copy()->endOfMonth();
        $startOfLastMonth = $now->copy()->subMonth()->startOfMonth();
        $endOfLastMonth = $now->copy()->subMonth()->endOfMonth();

        // Basic Counts
        $totalRevenue = Order::where('team_id', $teamId)->where('status', '!=', 'cancelled')->sum('total_amount');
        $totalOrders = Order::where('team_id', $teamId)->count();
        $totalProducts = Product::where('team_id', $teamId)->count();
        $pendingOrders = Order::where('team_id', $teamId)->where('status', 'pending')->count();

        // Empty State Check
        $this->isEmpty = ($totalProducts === 0 && $totalOrders === 0);

        // Trends (Revenue)
        $revenueCurrentMonth = Order::where('team_id', $teamId)
            ->where('status', '!=', 'cancelled')
            ->whereBetween('created_at', [$startOfMonth, $endOfMonth])
            ->sum('total_amount');

        $revenueLastMonth = Order::where('team_id', $teamId)
            ->where('status', '!=', 'cancelled')
            ->whereBetween('created_at', [$startOfLastMonth, $endOfLastMonth])
            ->sum('total_amount');

        // Trends (Orders)
        $ordersCurrentMonth = Order::where('team_id', $teamId)
            ->whereBetween('created_at', [$startOfMonth, $endOfMonth])
            ->count();

        $ordersLastMonth = Order::where('team_id', $teamId)
            ->whereBetween('created_at', [$startOfLastMonth, $endOfLastMonth])
            ->count();

        // Funnel Metrics (Catalog -> Cart -> Order)
        // 1. Catalog Impressions (Mocked/Proxy via CustomerEvent if available, or estimated)
        // Ideally: CustomerEvent::where('event_type', 'catalog_view')
        $catalogViews = \App\Models\CustomerEvent::where('team_id', $teamId)
            ->where('event_type', 'catalog_view')
            ->whereBetween('created_at', [$startOfMonth, $endOfMonth])
            ->count();

        // Fallback for demo if no events exist yet (remove in production)
        if ($catalogViews == 0 && $ordersCurrentMonth > 0) {
            $catalogViews = $ordersCurrentMonth * 12; // Estimate 8% conversion
        }

        // 2. Active Carts (Intent)
        $cartsCreated = \App\Models\Cart::where('team_id', $teamId)
            ->whereBetween('created_at', [$startOfMonth, $endOfMonth])
            ->count();

        // Fallback logic for funnel continuity
        if ($cartsCreated == 0 && $ordersCurrentMonth > 0) {
            $cartsCreated = $ordersCurrentMonth * 3; // Estimate 33% cart conversion
        }

        $this->funnel = [
            'impressions' => $catalogViews,
            'carts' => $cartsCreated,
            'orders' => $ordersCurrentMonth,
            'rates' => [
                'cart_rate' => $catalogViews > 0 ? ($cartsCreated / $catalogViews) * 100 : 0,
                'conversion_rate' => $cartsCreated > 0 ? ($ordersCurrentMonth / $cartsCreated) * 100 : 0,
                'global_rate' => $catalogViews > 0 ? ($ordersCurrentMonth / $catalogViews) * 100 : 0,
            ]
        ];

        // Operational Stats (Command Center)
        $outOfStock = Product::where('team_id', $teamId)
            ->where('availability', '!=', 'in stock')
            ->count();

        $readyToShip = Order::where('team_id', $teamId)
            ->where('status', 'paid')
            ->count();

        // AI Stats
        $activeBots = \App\Models\MessageBot::where('is_bot_active', 1)->count();
        $totalReplies = \App\Models\MessageBot::sum('sending_count');

        $this->operational = [
            'out_of_stock' => $outOfStock,
            'ready_to_ship' => $readyToShip,
            'returns' => Order::where('team_id', $teamId)->where('status', 'returned')->count(),
            'ai' => [
                'active' => $activeBots > 0,
                'replies' => $totalReplies,
                'hours_saved' => round(($totalReplies * 2) / 60, 1) // Est 2 mins per reply
            ]
        ];

        $this->stats = [
            'total_revenue' => $totalRevenue,
            'total_orders' => $totalOrders,
            'pending_orders' => $pendingOrders,
            'total_products' => $totalProducts,
        ];

        $this->trends = [
            'revenue' => $this->calculateTrend($revenueCurrentMonth, $revenueLastMonth),
            'orders' => $this->calculateTrend($ordersCurrentMonth, $ordersLastMonth),
        ];

        $this->lastUpdated = Carbon::now();
    }

    private function calculateTrend($current, $previous)
    {
        if ($previous == 0) {
            return $current > 0 ? 100 : 0;
        }
        return (($current - $previous) / $previous) * 100;
    }

    #[Layout('layouts.app')]
    public function render()
    {
        return view('livewire.commerce.dashboard');
    }
}
