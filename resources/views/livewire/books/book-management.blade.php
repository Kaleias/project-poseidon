{{--
    Book Management view.
    - Built with Flux components
    - Alpine.js drives the delete confirmation (x-data / x-show / x-on:click / x-text)
    - All dynamic output uses {{ }} (escaped) or x-text. There is no {!! !!}.
--}}
<div
    class="space-y-6"
    x-data="{
        confirmOpen: false,
        confirmId: null,
        confirmMessage: '',
        ask(id, message) {
            this.confirmId = id;
            this.confirmMessage = message;
            this.confirmOpen = true;
        },
        proceed() {
            $wire.deleteBook(this.confirmId);
            this.confirmOpen = false;
        },
    }"
    x-on:keydown.escape.window="confirmOpen = false"
>

    {{-- Page header --}}
    <div class="flex items-center justify-between">
        <div>
            <flux:heading size="xl" level="1">{{ __('Book Management') }}</flux:heading>
            <flux:text class="mt-1">{{ __('Manage the books available in the library.') }}</flux:text>
        </div>

        @can('create', \App\Models\Book::class)
            <flux:button variant="primary" icon="plus" wire:click="openForm">
                {{ __('Add Book') }}
            </flux:button>
        @endcan
    </div>

    {{-- Flash message --}}
    @if (session()->has('success'))
        <flux:callout variant="success" icon="check-circle" :heading="session('success')" />
    @endif

    {{-- Business-rule errors not tied to a form field (e.g. "no copies available") --}}
    @if (! $showForm)
        @error('book')
            <flux:callout variant="danger" icon="exclamation-triangle" :heading="$message" />
        @enderror
    @endif

    {{-- Search --}}
    <flux:input
        wire:model.live.debounce.300ms="search"
        icon="magnifying-glass"
        placeholder="{{ __('Search by title, author or ISBN...') }}"
    />

    {{-- Book table --}}
    <flux:table :paginate="$books">
        <flux:table.columns>
            <flux:table.column>{{ __('Title') }}</flux:table.column>
            <flux:table.column>{{ __('ISBN') }}</flux:table.column>
            <flux:table.column>{{ __('Category') }}</flux:table.column>
            <flux:table.column>{{ __('Available') }}</flux:table.column>
            @can('create', \App\Models\Book::class)
                <flux:table.column>{{ __('Stock') }}</flux:table.column>
                <flux:table.column>{{ __('Actions') }}</flux:table.column>
            @endcan
        </flux:table.columns>

        <flux:table.rows>
            @forelse ($books as $book)
                @php
                    $deleteMessage = 'Permanently delete "'.$book->title.'"? This cannot be undone.';
                @endphp

                <flux:table.row :key="$book->id">
                    <flux:table.cell>
                        <div class="font-medium">{{ $book->title }}</div>
                        <div class="text-xs text-zinc-500">{{ $book->author }}</div>
                    </flux:table.cell>

                    <flux:table.cell>{{ $book->isbn }}</flux:table.cell>

                    <flux:table.cell>
                        <flux:badge size="sm">{{ $book->category?->name ?? __('Uncategorized') }}</flux:badge>
                    </flux:table.cell>

                    <flux:table.cell>
                        <flux:badge size="sm" :color="$book->available_quantity > 0 ? 'green' : 'red'">
                            {{ $book->available_quantity }} / {{ $book->quantity }}
                        </flux:badge>
                    </flux:table.cell>

                    @can('update', $book)
                        <flux:table.cell>
                            <div class="flex items-center gap-2">
                                <flux:button
                                    size="xs"
                                    wire:click="returnBook({{ $book->id }})"
                                    :disabled="$book->available_quantity >= $book->quantity"
                                >
                                    {{ __('Return') }}
                                </flux:button>

                                <span class="text-sm font-semibold">
                                    {{ $book->quantity - $book->available_quantity }} {{ __('out') }}
                                </span>

                                <flux:button
                                    size="xs"
                                    wire:click="borrowBook({{ $book->id }})"
                                    :disabled="$book->available_quantity <= 0"
                                >
                                    {{ __('Borrow') }}
                                </flux:button>
                            </div>
                        </flux:table.cell>

                        <flux:table.cell>
                            {{-- Buttons are hidden when the policy denies them; the server re-checks anyway. --}}
                            <div class="flex gap-2">
                                <flux:button size="sm" wire:click="editBook({{ $book->id }})">
                                    {{ __('Edit') }}
                                </flux:button>

                                @can('delete', $book)
                                    <flux:button
                                        size="sm"
                                        variant="danger"
                                        type="button"
                                        x-on:click="ask({{ $book->id }}, @js($deleteMessage))"
                                    >
                                        {{ __('Delete') }}
                                    </flux:button>
                                @endcan
                            </div>
                        </flux:table.cell>
                    @endcan
                </flux:table.row>
            @empty
                <flux:table.row>
                    <flux:table.cell colspan="6" class="py-8 text-center text-zinc-500">
                        {{ __('No books found.') }}
                    </flux:table.cell>
                </flux:table.row>
            @endforelse
        </flux:table.rows>
    </flux:table>

    {{-- Add / edit modal (Flux). Staff only. --}}
    @can('create', \App\Models\Book::class)
        <flux:modal wire:model.self="showForm" class="w-full md:w-[34rem]">
            <form wire:submit="{{ $editingBookId ? 'updateBook' : 'saveBook' }}" class="space-y-5">
                <flux:heading size="lg">
                    {{ $editingBookId ? __('Edit Book') : __('Add New Book') }}
                </flux:heading>

                @error('book')
                    <flux:callout variant="danger" icon="exclamation-triangle" :heading="$message" />
                @enderror

                <flux:input wire:model="title" label="{{ __('Book Title') }}" />

                <flux:input wire:model="author" label="{{ __('Author') }}" />

                <flux:input wire:model="isbn" label="{{ __('ISBN') }}" />

                <flux:select wire:model="category_id" label="{{ __('Category') }}" placeholder="{{ __('Select Category') }}">
                    @foreach ($categories as $category)
                        <flux:select.option value="{{ $category->id }}">{{ $category->name }}</flux:select.option>
                    @endforeach
                </flux:select>

                <flux:input wire:model="publisher" label="{{ __('Publisher (Optional)') }}" />

                <flux:input type="number" wire:model="publication_year" label="{{ __('Publication Year (Optional)') }}" />

                <flux:input type="number" min="0" wire:model="quantity" label="{{ __('Quantity') }}" />

                <div class="flex justify-end gap-2">
                    <flux:button type="button" wire:click="closeForm">{{ __('Cancel') }}</flux:button>
                    <flux:button type="submit" variant="primary">
                        {{ $editingBookId ? __('Update Book') : __('Save Book') }}
                    </flux:button>
                </div>
            </form>
        </flux:modal>
    @endcan

    {{-- Alpine.js delete confirmation.
         x-text (NOT x-html) shows the book title as plain text, so injected HTML never runs. --}}
    <div
        x-show="confirmOpen"
        x-transition.opacity
        style="display: none;"
        class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4"
    >
        <div
            x-on:click.outside="confirmOpen = false"
            class="w-full max-w-md rounded-xl bg-white p-6 shadow-xl dark:bg-zinc-800"
        >
            <h3 class="text-lg font-semibold text-zinc-900 dark:text-white">{{ __('Please confirm') }}</h3>
            <p class="mt-2 text-sm text-zinc-600 dark:text-zinc-300" x-text="confirmMessage"></p>

            <div class="mt-6 flex justify-end gap-2">
                <button
                    type="button"
                    x-on:click="confirmOpen = false"
                    class="rounded-lg border border-zinc-300 px-4 py-2 text-sm dark:border-zinc-600 dark:text-white"
                >
                    {{ __('Cancel') }}
                </button>

                <button
                    type="button"
                    x-on:click="proceed()"
                    class="rounded-lg bg-red-600 px-4 py-2 text-sm font-semibold text-white hover:bg-red-700"
                >
                    {{ __('Confirm') }}
                </button>
            </div>
        </div>
    </div>

</div>
