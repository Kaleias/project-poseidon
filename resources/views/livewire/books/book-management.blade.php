<div class="space-y-6">

    {{-- Page Header --}}
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-bold text-zinc-900 dark:text-white">
                Book Management
            </h1>
            <p class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">
                Manage the books available in the library.
            </p>
        </div>

        <button
            wire:click="openForm"
            class="rounded-lg bg-blue-600 px-4 py-2 text-sm font-semibold text-white hover:bg-blue-700">
            + Add Book
        </button>
    </div>

    {{-- Success Message --}}
    @if (session()->has('success'))
        <div class="rounded-lg bg-green-100 p-3 text-green-700">
            {{ session('success') }}
        </div>
    @endif

    {{-- Add Book Form --}}
    @if ($showForm)
        <div class="rounded-xl border border-zinc-200 bg-white p-6 shadow-sm dark:border-zinc-700 dark:bg-zinc-900">

            <h2 class="mb-5 text-lg font-semibold text-zinc-900 dark:text-white">
                {{ $isEditMode ? 'Edit Book' : 'Add New Book' }}
            </h2>

            <form wire:submit="{{ $isEditMode ? 'updateBook' : 'saveBook' }}" class="space-y-4">

                <div>
                    <label class="mb-1 block text-sm font-medium">Book Title</label>
                    <input type="text" wire:model.live="title"
                        class="w-full rounded-lg border border-zinc-300 bg-white px-3 py-2 dark:border-zinc-700 dark:bg-zinc-800 dark:text-white">
                    @error('title') <span class="text-sm text-red-500">{{ $message }}</span> @enderror
                </div>

                <div>
                    <label class="mb-1 block text-sm font-medium">Author</label>
                    <input type="text" wire:model="author"
                        class="w-full rounded-lg border border-zinc-300 bg-white px-3 py-2 dark:border-zinc-700 dark:bg-zinc-800 dark:text-white">
                    @error('author') <span class="text-sm text-red-500">{{ $message }}</span> @enderror
                </div>

                <div>
                    <label class="mb-1 block text-sm font-medium">ISBN</label>
                    <input type="text" wire:model="isbn"
                        class="w-full rounded-lg border border-zinc-300 bg-white px-3 py-2 dark:border-zinc-700 dark:bg-zinc-800 dark:text-white">
                    @error('isbn') <span class="text-sm text-red-500">{{ $message }}</span> @enderror
                </div>

                <div>
                    <label class="mb-1 block text-sm font-medium">Category</label>
                    <select wire:model.live="category_id"
                        class="w-full rounded-lg border border-zinc-300 bg-white px-3 py-2 dark:border-zinc-700 dark:bg-zinc-800 dark:text-white">
                        <option value="">Select Category</option>
                        @foreach ($categories as $category)
                            <option value="{{ $category->id }}">{{ $category->name }}</option>
                        @endforeach
                    </select>
                    @error('category_id') <span class="text-sm text-red-500">{{ $message }}</span> @enderror
                </div>

                <div>
                    <label class="mb-1 block text-sm font-medium">Publisher (Optional)</label>
                    <input type="text" wire:model="publisher"
                        class="w-full rounded-lg border border-zinc-300 bg-white px-3 py-2 dark:border-zinc-700 dark:bg-zinc-800 dark:text-white">
                    @error('publisher') <span class="text-sm text-red-500">{{ $message }}</span> @enderror
                </div>

                <div>
                    <label class="mb-1 block text-sm font-medium">Publication Year (Optional)</label>
                    <input type="number" wire:model="publication_year"
                        class="w-full rounded-lg border border-zinc-300 bg-white px-3 py-2 dark:border-zinc-700 dark:bg-zinc-800 dark:text-white">
                    @error('publication_year') <span class="text-sm text-red-500">{{ $message }}</span> @enderror
                </div>

                <div>
                    <label class="mb-1 block text-sm font-medium">Quantity</label>
                    <input type="number" min="0" wire:model.live="quantity"
                        class="w-full rounded-lg border border-zinc-300 bg-white px-3 py-2 dark:border-zinc-700 dark:bg-zinc-800 dark:text-white">
                    @error('quantity') <span class="text-sm text-red-500">{{ $message }}</span> @enderror
                </div>

                <div class="flex justify-end gap-3 pt-3">
                    <button type="button" wire:click="closeForm"
                        class="rounded-lg border border-zinc-300 px-4 py-2 text-sm dark:border-zinc-600 dark:text-white">
                        Cancel
                    </button>

                    <button type="submit"
                        class="rounded-lg bg-blue-600 px-4 py-2 text-sm font-semibold text-white hover:bg-blue-700">
                        {{ $isEditMode ? 'Update Book' : 'Save Book' }}
                    </button>
                </div>

            </form>
        </div>
    @endif

    {{-- Search Bar --}}
    <div>
        <input
            type="text"
            wire:model.live="search"
            placeholder="Search by title, author or ISBN..."
            class="w-full rounded-lg border border-zinc-300 bg-white px-4 py-2 text-sm text-zinc-900 dark:border-zinc-700 dark:bg-zinc-900 dark:text-white">
    </div>

    {{-- Book Table --}}
    <div class="overflow-x-auto rounded-xl border border-zinc-200 dark:border-zinc-700">
        <table class="w-full text-left text-sm">
            <thead class="bg-zinc-100 text-zinc-700 dark:bg-zinc-800 dark:text-zinc-300">
                <tr>
                    <th class="px-4 py-3">Book ID</th>
                    <th class="px-4 py-3">Title</th>
                    <th class="px-4 py-3">Author</th>
                    <th class="px-4 py-3">ISBN</th>
                    <th class="px-4 py-3">Category</th>
                    <th class="px-4 py-3">Quantity</th>
                    <th class="px-4 py-3">Available</th>
                    <th class="px-4 py-3">Action</th>
                    <th class="px-4 py-3">Borrow</th>
                </tr>
            </thead>

            <tbody class="divide-y divide-zinc-200 dark:divide-zinc-700">
                @forelse ($books as $book)
                    <tr class="text-zinc-700 dark:text-zinc-300">
                        <td class="px-4 py-3">{{ $book->id }}</td>
                        <td class="px-4 py-3 font-medium">{{ $book->title }}</td>
                        <td class="px-4 py-3">{{ $book->author }}</td>
                        <td class="px-4 py-3">{{ $book->isbn }}</td>
                        <td class="px-4 py-3">{{ $book->category?->name ?? 'Uncategorized' }}</td>
                        <td class="px-4 py-3">{{ $book->quantity }}</td>
                        <td class="px-4 py-3">{{ $book->available_quantity }}</td>
                        <td class="px-4 py-3">
                            <button wire:click="editBook({{ $book->id }})"
                                class="px-3 py-1 bg-blue-600 text-white rounded">
                                Edit
                            </button>

                            <button wire:click="deleteBook({{ $book->id }})"
                                wire:confirm="Are you sure you want to delete this book?"
                                class="px-3 py-1 bg-red-600 text-white rounded">
                                Delete
                            </button>
                        </td>
                        <td class="px-4 py-3">
                            <div class="flex items-center gap-2">

                                <button
                                    wire:click="returnBook({{ $book->id }})"
                                    @disabled($book->available_quantity >= $book->quantity)
                                    class="px-3 py-1 bg-gray-500 text-white rounded disabled:opacity-40">
                                    -
                                </button>

                                <span class="font-semibold">
                                    {{ $book->quantity - $book->available_quantity }}
                                </span>

                                <button
                                    wire:click="borrowBook({{ $book->id }})"
                                    @disabled($book->available_quantity <= 0)
                                    class="px-3 py-1 bg-green-600 text-white rounded disabled:opacity-40">
                                    +
                                </button>

                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="9" class="px-4 py-8 text-center text-zinc-500">
                            No books found.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

</div>
