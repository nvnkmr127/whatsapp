<?php

namespace App\Livewire\Commerce;

use App\Models\Product;
use App\Services\WhatsAppCommerceService;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

class ProductManager extends Component
{
    use WithPagination;

    public $search = '';
    public $name, $description, $price, $currency = 'USD', $retailer_id, $image_url, $url, $category_id;
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
            'retailer_id' => 'required|string|max:100|unique:products,retailer_id,' . $this->editingProductId . ',id',
            'image_url' => 'nullable|url',
            'category_id' => 'nullable|exists:categories,id',
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
            'availability' => 'in stock',
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
        ]);

        $this->resetInput();
        $this->showCreateModal = false;
        session()->flash('success', 'Product updated successfully.');
    }

    public function sync($id)
    {
        $product = Product::where('team_id', Auth::user()->currentTeam->id)->findOrFail($id);

        try {
            // In a real app, use Dependency Injection, but keeping it simple for now
            $service = new WhatsAppCommerceService();
            $service->setTeam(Auth::user()->currentTeam);

            $service->syncProductToMeta($product);

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
        $this->reset(['name', 'description', 'price', 'retailer_id', 'image_url', 'url', 'editingProductId', 'category_id']);
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
