<?php

namespace App\Livewire\Settings;

use Livewire\Component;
use Livewire\WithPagination;
use App\Models\Category;
use Illuminate\Support\Facades\Auth;

class CategoryManager extends Component
{
    use WithPagination;

    public $showCreateModal = false;
    public $showEditModal = false;
    public $editingCategoryId = null;

    public $name = '';
    public $description = '';
    public $color = '#3B82F6';
    public $icon = '📁';
    public $is_active = true;
    public $target_module = 'all';

    public $searchTerm = '';
    public $filterModule = '';

    protected $rules = [
        'name' => 'required|string|max:255',
        'description' => 'nullable|string|max:500',
        'color' => 'required|string|max:7',
        'icon' => 'nullable|string|max:10',
        'is_active' => 'boolean',
        'target_module' => 'required|string|in:all,contacts,products',
    ];

    public function render()
    {
        $categories = Category::where('team_id', Auth::user()->currentTeam->id)
            ->when($this->searchTerm, function ($query) {
                $query->where('name', 'like', '%' . $this->searchTerm . '%')
                    ->orWhere('description', 'like', '%' . $this->searchTerm . '%');
            })
            ->when($this->filterModule, function ($query) {
                $query->where('target_module', $this->filterModule);
            })
            ->withCount(['products', 'contacts'])
            ->orderBy('name', 'asc')
            ->paginate(15);

        return view('livewire.settings.category-manager', [
            'categories' => $categories,
        ]);
    }

    public function openCreateModal()
    {
        $this->resetForm();
        $this->showCreateModal = true;
    }

    public function openEditModal($id)
    {
        $this->resetForm();
        $category = Category::findOrFail($id);

        $this->editingCategoryId = $id;
        $this->name = $category->name;
        $this->description = $category->description;
        $this->color = $category->color ?? '#3B82F6';
        $this->icon = $category->icon ?? '📁';
        $this->is_active = $category->is_active;
        $this->target_module = $category->target_module ?? 'all';

        $this->showEditModal = true;
    }

    public function saveCategory()
    {
        $this->validate();

        $data = [
            'team_id' => Auth::user()->currentTeam->id,
            'name' => $this->name,
            'description' => $this->description,
            'color' => $this->color,
            'icon' => $this->icon,
            'is_active' => $this->is_active,
            'target_module' => $this->target_module,
        ];

        if ($this->editingCategoryId) {
            Category::findOrFail($this->editingCategoryId)->update($data);
            session()->flash('message', 'Category updated successfully.');
        } else {
            Category::create($data);
            session()->flash('message', 'Category created successfully.');
        }

        $this->closeModals();
    }

    public function deleteCategory($id)
    {
        $category = Category::findOrFail($id);

        // Check if category is in use
        if ($category->products()->count() > 0 || $category->contacts()->count() > 0) {
            session()->flash('error', 'Cannot delete category that is in use. Please reassign items first.');
            return;
        }

        $category->delete();
        session()->flash('message', 'Category deleted successfully.');
    }

    public function toggleStatus($id)
    {
        $category = Category::findOrFail($id);
        $category->update(['is_active' => !$category->is_active]);
    }

    private function resetForm()
    {
        $this->editingCategoryId = null;
        $this->name = '';
        $this->description = '';
        $this->color = '#3B82F6';
        $this->icon = '📁';
        $this->is_active = true;
        $this->target_module = 'all';
        $this->resetErrorBag();
    }

    public function closeModals()
    {
        $this->showCreateModal = false;
        $this->showEditModal = false;
        $this->resetForm();
    }
}
