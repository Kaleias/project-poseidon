<div
    class="p-6 space-y-6 text-gray-900 dark:text-white"
    x-data="{
        showDeleteModal: false,
        categoryToDelete: null,
        categoryName: '',
        bookCount: 0
    }"
>

    <!-- Page Heading -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold">Category Management</h1>
            <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
                Organise your library books into categories.
            </p>
        </div>

        @can('create', App\Models\Category::class)
            <button
                wire:click="openAddModal"
                class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">
                + Add Category
            </button>
        @endcan
    </div>

    <!-- Flash Messages -->
    @if (session()->has('success'))
        <div class="p-3 bg-green-100 text-green-800 rounded-lg">
            {{ session('success') }}
        </div>
    @endif

    @if (session()->has('error'))
        <div class="p-3 bg-red-100 text-red-800 rounded-lg">
            {{ session('error') }}
        </div>
    @endif

    <!-- Search -->
    <div>
        <input
            type="text"
            wire:model.live="search"
            placeholder="Search by category name..."
            class="w-full sm:w-1/2 p-3 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-zinc-900 text-gray-900 dark:text-white placeholder-gray-400">
    </div>

    <!-- Categories Table -->
    <div class="overflow-x-auto bg-white dark:bg-zinc-900 rounded-lg shadow border border-gray-200 dark:border-gray-700">
        <table class="w-full text-left">
            <thead class="bg-gray-100 dark:bg-zinc-800">
                <tr>
                    <th class="p-3">#</th>
                    <th class="p-3">Name</th>
                    <th class="p-3">Books</th>
                    <th class="p-3">Actions</th>
                </tr>
            </thead>

            <tbody>
                @forelse ($categories as $category)
                    <tr class="border-t border-gray-200 dark:border-gray-700">
                        <td class="p-3">{{ $categories->firstItem() + $loop->index }}</td>
                        <td class="p-3">{{ $category->name }}</td>
                        <td class="p-3">{{ $category->books_count }}</td>

                        <td class="p-3">
                            <div class="flex gap-3">
                                @can('update', $category)
                                    <button
                                        wire:click="editCategory({{ $category->id }})"
                                        class="text-blue-600 hover:underline">
                                        Edit
                                    </button>
                                @endcan

                                @can('delete', $category)
                                    <button
                                        type="button"
                                        x-on:click="
                                            categoryToDelete = {{ $category->id }};
                                            categoryName = @js($category->name);
                                            bookCount = {{ $category->books_count }};
                                            showDeleteModal = true;
                                        "
                                        class="text-red-600 hover:underline">
                                        Delete
                                    </button>
                                @endcan
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4" class="p-6 text-center text-gray-500 dark:text-gray-400">
                            No categories found.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <!-- Pagination -->
    <div>
        {{ $categories->links() }}
    </div>

    <!-- Add / Edit Category Modal -->
    @if ($showModal)
        <div class="fixed inset-0 z-[100] flex items-center justify-center bg-black/60 p-4">

            <div class="bg-white dark:bg-zinc-800 text-gray-900 dark:text-white rounded-xl shadow-2xl w-full max-w-md p-6">

                <h2 class="text-xl font-bold mb-5">
                    {{ $editingCategoryId ? 'Edit Category' : 'Add New Category' }}
                </h2>

                <form wire:submit="{{ $editingCategoryId ? 'updateCategory' : 'saveCategory' }}">

                    <div>
                        <label class="block text-sm font-medium mb-1">Category Name</label>
                        <input
                            type="text"
                            wire:model="name"
                            placeholder="Example: Science Fiction"
                            class="w-full border border-gray-300 dark:border-gray-600 bg-white dark:bg-zinc-900 text-gray-900 dark:text-white rounded-lg p-2">
                        @error('name')
                            <span class="text-red-500 text-sm">{{ $message }}</span>
                        @enderror
                    </div>

                    <div class="flex justify-end gap-3 mt-6">
                        <button
                            type="button"
                            wire:click="$set('showModal', false)"
                            class="px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg hover:bg-gray-100 dark:hover:bg-zinc-700">
                            Cancel
                        </button>

                        <button
                            type="submit"
                            wire:loading.attr="disabled"
                            class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 disabled:opacity-50">
                            <span wire:loading.remove>
                                {{ $editingCategoryId ? 'Update Category' : 'Save Category' }}
                            </span>
                            <span wire:loading>Saving...</span>
                        </button>
                    </div>

                </form>
            </div>
        </div>
    @endif

    <!-- Alpine.js Delete Confirmation Modal -->
    <div
        x-cloak
        x-show="showDeleteModal"
        x-transition.opacity
        class="fixed inset-0 z-[200] flex items-center justify-center bg-black/60 p-4"
    >
        <div
            x-show="showDeleteModal"
            x-transition
            x-on:click.outside="showDeleteModal = false"
            class="bg-white dark:bg-zinc-800 text-gray-900 dark:text-white rounded-xl shadow-2xl w-full max-w-md p-6"
        >
            <div class="text-center">

                <div class="mx-auto mb-4 flex items-center justify-center w-14 h-14 rounded-full bg-red-100">
                    <span class="text-3xl text-red-600">!</span>
                </div>

                <h2 class="text-xl font-bold mb-2">Delete Category?</h2>

                <p class="text-gray-600 dark:text-gray-300 mb-2">
                    Are you sure you want to delete
                    <strong x-text="categoryName"></strong>?
                </p>

                <!-- Warning shown only when the category still has books -->
                <p
                    x-show="bookCount > 0"
                    class="text-sm text-red-600 mb-4">
                    This category has <span x-text="bookCount"></span> book(s) and cannot be deleted.
                </p>

                <div class="flex justify-center gap-3 mt-4">
                    <button
                        type="button"
                        x-on:click="showDeleteModal = false"
                        class="px-5 py-2 rounded-lg border border-gray-300 dark:border-gray-600 hover:bg-gray-100 dark:hover:bg-zinc-700">
                        Cancel
                    </button>

                    <button
                        type="button"
                        x-bind:disabled="bookCount > 0"
                        x-on:click="
                            $wire.deleteCategory(categoryToDelete);
                            showDeleteModal = false;
                            categoryToDelete = null;
                            categoryName = '';
                            bookCount = 0;
                        "
                        class="px-5 py-2 rounded-lg bg-red-600 text-white hover:bg-red-700 disabled:opacity-40 disabled:cursor-not-allowed">
                        Yes, Delete
                    </button>
                </div>
            </div>
        </div>
    </div>

</div>
