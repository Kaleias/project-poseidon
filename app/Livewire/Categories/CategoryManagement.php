<?php

namespace App\Livewire\Categories;

use App\Actions\Categories\CreateCategoryAction;
use App\Actions\Categories\DeleteCategoryAction;
use App\Actions\Categories\UpdateCategoryAction;
use App\Http\Requests\StoreCategoryRequest;
use App\Models\Category;
use DomainException;
use Livewire\Component;
use Livewire\WithPagination;

class CategoryManagement extends Component
{
    use WithPagination;

    public $name = '';
    public $search = '';
    public $editingCategoryId = null;
    public $showModal = false;

    // Check permission when the component loads
    public function mount()
    {
        $this->authorize('viewAny', Category::class);
    }

    public function updatedSearch()
    {
        $this->resetPage();
    }

    public function openAddModal()
    {
        $this->authorize('create', Category::class);

        $this->resetForm();
        $this->showModal = true;
    }

    public function saveCategory(CreateCategoryAction $action)
    {
        $this->authorize('create', Category::class);

        $validated = $this->validate(StoreCategoryRequest::rulesFor());

        $action->execute($validated);

        $this->resetForm();
        $this->showModal = false;

        session()->flash('success', 'Category added successfully!');
    }

    public function editCategory($id)
    {
        $category = Category::findOrFail($id);

        $this->authorize('update', $category);

        $this->editingCategoryId = $category->id;
        $this->name = $category->name;

        $this->resetValidation();
        $this->showModal = true;
    }

    public function updateCategory(UpdateCategoryAction $action)
    {
        $category = Category::findOrFail($this->editingCategoryId);

        $this->authorize('update', $category);

        $validated = $this->validate(StoreCategoryRequest::rulesFor($category->id));

        $action->execute($category, $validated);

        $this->resetForm();
        $this->showModal = false;

        session()->flash('success', 'Category updated successfully!');
    }

    public function deleteCategory($id, DeleteCategoryAction $action)
    {
        $category = Category::findOrFail($id);

        $this->authorize('delete', $category);

        try {
            $action->execute($category);
        } catch (DomainException $e) {
            session()->flash('error', $e->getMessage());

            return;
        }

        session()->flash('success', 'Category deleted successfully!');
    }

    public function resetForm()
    {
        $this->reset(['name', 'editingCategoryId']);
        $this->resetValidation();
    }

    public function render()
    {
        $categories = Category::withCount('books')
            ->where('name', 'like', '%' . $this->search . '%')
            ->orderBy('name')
            ->paginate(5);

        return view('livewire.categories.category-management', [
            'categories' => $categories,
        ]);
    }
}
