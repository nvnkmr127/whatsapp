<?php

namespace App\Livewire\Commerce;

use App\Models\Product;
use App\Models\Integration;
use App\Services\WhatsAppCommerceService;
use App\Services\Integrations\MetaCommerceService;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

class ProductManager extends Component
{
    use WithPagination;

    public $search = '';
    public $name, $description, $price, $currency = 'USD', $retailer_id, $image_url, $url, $category_id;
    public $stock_quantity = 0, $manage_stock = false, $availability = 'in stock', $is_active = true;
    public $editingProductId = null;
    public $showCreateModal = false;

    // Reset pagination when searching
    public function updatedSearch()
    {
        $this->resetPage();
    }

    protected function rules()
    {
        return [
            'name' => 'required|string|max:255',
            'price' => 'required|numeric|min:0',
            'retailer_id' => 'required|string|max:100|regex:/^[a-zA-Z0-9\-_]+$/|unique:products,retailer_id,' . $this->editingProductId . ',id',
            'image_url' => 'required|url', // Meta requires image
            'category_id' => 'nullable|exists:categories,id',
            'stock_quantity' => 'required_if:manage_stock,true|numeric|min:0',
            'description' => 'required|string|min:10',
        ];
    }

    public function create()
    {
        $this->resetInput();
        $this->showCreateModal = true;
    }

    public function store()
    {
        $this->validate();

        Product::create([
            'team_id' => Auth::user()->currentTeam->id,
            'name' => $this->name,
            'description' => $this->description,
            'price' => $this->price,
            'currency' => $this->currency,
            'retailer_id' => $this->retailer_id,
            'image_url' => $this->image_url,
            'url' => $this->url,
            'category_id' => $this->category_id ?: null,
            'availability' => $this->availability,
            'stock_quantity' => $this->stock_quantity,
            'manage_stock' => $this->manage_stock,
            'is_active' => $this->is_active,
            'sync_state' => 'local',
        ]);

        $this->resetInput();
        $this->showCreateModal = false;
        session()->flash('success', 'Product created successfully.');
    }

    public function edit($id)
    {
        $product = Product::where('team_id', Auth::user()->currentTeam->id)->findOrFail($id);
        $this->editingProductId = $product->id;
        $this->name = $product->name;
        $this->description = $product->description;
        $this->price = $product->price;
        $this->currency = $product->currency;
        $this->retailer_id = $product->retailer_id;
        $this->image_url = $product->image_url;
        $this->url = $product->url;
        $this->category_id = $product->category_id;
        $this->stock_quantity = $product->stock_quantity;
        $this->manage_stock = $product->manage_stock;
        $this->availability = $product->availability;
        $this->is_active = $product->is_active;

        $this->showCreateModal = true;
    }

    public function update()
    {
        $this->validate();

        $product = Product::where('team_id', Auth::user()->currentTeam->id)->findOrFail($this->editingProductId);
        $product->update([
            'name' => $this->name,
            'description' => $this->description,
            'price' => $this->price,
            'currency' => $this->currency,
            'retailer_id' => $this->retailer_id,
            'image_url' => $this->image_url,
            'url' => $this->url,
            'category_id' => $this->category_id ?: null,
            'stock_quantity' => $this->stock_quantity,
            'manage_stock' => $this->manage_stock,
            'availability' => $this->availability,
            'is_active' => $this->is_active,
            'sync_state' => 'local', // Reset sync state on update
        ]);

        $this->resetInput();
        $this->showCreateModal = false;
        session()->flash('success', 'Product updated successfully.');
    }

    public function sync($id)
    {
        $product = Product::where('team_id', Auth::user()->currentTeam->id)->findOrFail($id);

        try {
            // 1. Check if there's a dedicated Meta Commerce Integration
            $integration = Integration::where('team_id', Auth::user()->currentTeam->id)
                ->where('type', 'meta_commerce')
                ->where('status', 'active')
                ->first();

            if ($integration) {
                $service = new MetaCommerceService($integration);
                $service->syncSingleProduct($product);
            } else {
                // 2. Fallback to basic WhatsApp commerce using Team tokens
                $service = new WhatsAppCommerceService();
                $service->setTeam(Auth::user()->currentTeam);
                $service->syncProductToMeta($product);
            }

            session()->flash('success', 'Product synced to Meta Catalog.');
        } catch (\Exception $e) {
            session()->flash('error', 'Sync Failed: ' . $e->getMessage());
        }
    }

    public function delete($id)
    {
        Product::where('team_id', Auth::user()->currentTeam->id)->findOrFail($id)->delete();
        session()->flash('success', 'Product deleted.');
    }

    public function resetInput()
    {
        $this->reset(['name', 'description', 'price', 'retailer_id', 'image_url', 'url', 'editingProductId', 'category_id', 'stock_quantity', 'manage_stock', 'availability', 'is_active']);
    }

    #[Layout('components.layouts.app')]
    public function render()
    {
        $products = Product::where('team_id', Auth::user()->currentTeam->id)
            ->where(function ($q) {
                $q->where('name', 'like', '%' . $this->search . '%')
                    ->orWhere('retailer_id', 'like', '%' . $this->search . '%');
            })
            ->orderBy('created_at', 'desc')
            ->paginate(12);

        $categories = \App\Models\Category::where('team_id', Auth::user()->currentTeam->id)
            ->whereIn('target_module', ['all', 'products'])
            ->get();

        return view('livewire.commerce.product-manager', [
            'products' => $products,
            'categories' => $categories
        ]);
    }
}
