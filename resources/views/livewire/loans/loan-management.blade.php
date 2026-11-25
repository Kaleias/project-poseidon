{{--
    Loan Management view (Module 2).
    - UI built with Flux components
    - Alpine.js drives the confirmation dialog (x-data / x-show / x-on:click / x-text)
    - All dynamic output uses {{ }} (escaped) or x-text; there is no {!! !!} anywhere.
--}}
<div
    class="space-y-6"
    x-data="{
        confirmOpen: false,
        confirmAction: null,
        confirmId: null,
        confirmTitle: '',
        confirmMessage: '',
        confirmDanger: false,
        ask(action, id, title, message, danger) {
            this.confirmAction = action;
            this.confirmId = id;
            this.confirmTitle = title;
            this.confirmMessage = message;
            this.confirmDanger = danger;
            this.confirmOpen = true;
        },
        proceed() {
            $wire.call(this.confirmAction, this.confirmId);
            this.confirmOpen = false;
        },
    }"
    x-on:keydown.escape.window="confirmOpen = false"
>

    {{-- Page header --}}
    <div class="flex items-center justify-between">
        <div>
            <flux:heading size="xl" level="1">{{ __('Loan Management') }}</flux:heading>
            <flux:text class="mt-1">{{ __('Issue books to members and track returns.') }}</flux:text>
        </div>

        @can('create', \App\Models\Loan::class)
            <flux:button variant="primary" icon="plus" wire:click="openCreateModal">
                {{ __('New Loan') }}
            </flux:button>
        @endcan
    </div>

    {{-- Summary --}}
    <div class="flex flex-wrap gap-3">
        <flux:badge color="blue" size="lg">{{ $activeCount }} {{ __('active loans') }}</flux:badge>
        <flux:badge color="red" size="lg">{{ $overdueCount }} {{ __('overdue') }}</flux:badge>
    </div>

    {{-- Flash message --}}
    @if (session()->has('success'))
        <flux:callout variant="success" icon="check-circle" :heading="session('success')" />
    @endif

    {{-- Business-rule errors that are not tied to a form field (e.g. "already returned") --}}
    @if (! $showModal)
        @error('loan')
            <flux:callout variant="danger" icon="exclamation-triangle" :heading="$message" />
        @enderror
    @endif

    {{-- Search + filter --}}
    <div class="flex flex-col gap-3 sm:flex-row">
        <div class="flex-1">
            <flux:input
                wire:model.live.debounce.300ms="search"
                icon="magnifying-glass"
                placeholder="{{ __('Search by member name, member ID, book title or ISBN...') }}"
            />
        </div>

        <div class="sm:w-48">
            <flux:select wire:model.live="statusFilter">
                <flux:select.option value="all">{{ __('All loans') }}</flux:select.option>
                <flux:select.option value="borrowed">{{ __('Active') }}</flux:select.option>
                <flux:select.option value="overdue">{{ __('Overdue') }}</flux:select.option>
                <flux:select.option value="returned">{{ __('Returned') }}</flux:select.option>
            </flux:select>
        </div>
    </div>

    {{-- Loans table --}}
    <flux:table :paginate="$loans">
        <flux:table.columns>
            <flux:table.column>{{ __('Member') }}</flux:table.column>
            <flux:table.column>{{ __('Book') }}</flux:table.column>
            <flux:table.column>{{ __('Borrowed') }}</flux:table.column>
            <flux:table.column>{{ __('Due') }}</flux:table.column>
            <flux:table.column>{{ __('Status') }}</flux:table.column>
            <flux:table.column>{{ __('Actions') }}</flux:table.column>
        </flux:table.columns>

        <flux:table.rows>
            @forelse ($loans as $loan)
                @php
                    $returnMessage = 'Mark "'.$loan->book->title.'" as returned by '.$loan->member->name.'?';
                    $deleteMessage = 'Permanently delete the loan of "'.$loan->book->title.'" to '.$loan->member->name.'? This cannot be undone.';
                @endphp

                <flux:table.row :key="$loan->id">
                    <flux:table.cell>
                        <div class="font-medium">{{ $loan->member->name }}</div>
                        <div class="text-xs text-zinc-500">{{ $loan->member->member_id }}</div>
                        @if ($loan->notes)
                            <div class="mt-1 text-xs italic text-zinc-500">{{ $loan->notes }}</div>
                        @endif
                    </flux:table.cell>

                    <flux:table.cell>{{ $loan->book->title }}</flux:table.cell>

                    <flux:table.cell>{{ $loan->borrowed_at->format('d M Y') }}</flux:table.cell>

                    <flux:table.cell>
                        <span @class(['font-semibold text-red-600' => $loan->isOverdue()])>
                            {{ $loan->due_date->format('d M Y') }}
                        </span>
                    </flux:table.cell>

                    <flux:table.cell>
                        @if ($loan->isReturned())
                            <flux:badge color="green" size="sm">
                                {{ __('Returned') }} {{ $loan->returned_at->format('d M') }}
                            </flux:badge>
                        @elseif ($loan->isOverdue())
                            <flux:badge color="red" size="sm">{{ __('Overdue') }}</flux:badge>
                        @else
                            <flux:badge color="blue" size="sm">{{ __('Borrowed') }}</flux:badge>
                        @endif
                    </flux:table.cell>

                    <flux:table.cell>
                        {{-- Buttons are hidden when the policy denies them; the server re-checks anyway. --}}
                        <div class="flex flex-wrap gap-2">
                            @can('markReturned', $loan)
                                <flux:button
                                    size="sm"
                                    variant="primary"
                                    type="button"
                                    x-on:click="ask('returnLoan', {{ $loan->id }}, 'Return book', @js($returnMessage), false)"
                                >
                                    {{ __('Return') }}
                                </flux:button>
                            @endcan

                            @can('update', $loan)
                                <flux:button size="sm" wire:click="editLoan({{ $loan->id }})">
                                    {{ __('Edit') }}
                                </flux:button>
                            @endcan

                            @can('delete', $loan)
                                <flux:button
                                    size="sm"
                                    variant="danger"
                                    type="button"
                                    x-on:click="ask('deleteLoan', {{ $loan->id }}, 'Delete loan', @js($deleteMessage), true)"
                                >
                                    {{ __('Delete') }}
                                </flux:button>
                            @endcan
                        </div>
                    </flux:table.cell>
                </flux:table.row>
            @empty
                <flux:table.row>
                    <flux:table.cell colspan="6" class="py-8 text-center text-zinc-500">
                        {{ __('No loans found.') }}
                    </flux:table.cell>
                </flux:table.row>
            @endforelse
        </flux:table.rows>
    </flux:table>

    {{-- Create / edit modal (Flux) --}}
    <flux:modal wire:model.self="showModal" class="w-full md:w-[34rem]">
        <form wire:submit="{{ $editingLoanId ? 'updateLoan' : 'saveLoan' }}" class="space-y-5">
            <div>
                <flux:heading size="lg">
                    {{ $editingLoanId ? __('Edit Loan') : __('New Loan') }}
                </flux:heading>
                <flux:text class="mt-1">
                    {{ $editingLoanId
                        ? __('You can extend the due date or change the notes. Book and member are fixed.')
                        : __('Choose the book and the member borrowing it.') }}
                </flux:text>
            </div>

            @error('loan')
                <flux:callout variant="danger" icon="exclamation-triangle" :heading="$message" />
            @enderror

            @if ($editingLoan)
                {{-- Edit mode: book and member are read-only so stock can never drift --}}
                <div class="rounded-lg bg-zinc-100 p-3 text-sm dark:bg-zinc-800">
                    <div><span class="font-medium">{{ __('Book') }}:</span> {{ $editingLoan->book->title }}</div>
                    <div><span class="font-medium">{{ __('Member') }}:</span> {{ $editingLoan->member->name }} ({{ $editingLoan->member->member_id }})</div>
                </div>
            @else
                <flux:select wire:model="book_id" label="{{ __('Book') }}" placeholder="{{ __('Select an available book...') }}">
                    @foreach ($availableBooks as $book)
                        <flux:select.option value="{{ $book->id }}">
                            {{ $book->title }} ({{ $book->available_quantity }} {{ __('available') }})
                        </flux:select.option>
                    @endforeach
                </flux:select>

                <flux:select wire:model="member_id" label="{{ __('Member') }}" placeholder="{{ __('Select a member...') }}">
                    @foreach ($members as $member)
                        <flux:select.option value="{{ $member->id }}">
                            {{ $member->name }} ({{ $member->member_id }})
                        </flux:select.option>
                    @endforeach
                </flux:select>

                <flux:input type="date" wire:model="borrowed_at" label="{{ __('Borrow date') }}" />
            @endif

            <flux:input type="date" wire:model="due_date" label="{{ __('Due date') }}" />

            <flux:textarea wire:model="notes" label="{{ __('Notes (optional)') }}" rows="2" />

            <div class="flex justify-end gap-2">
                <flux:button type="button" wire:click="closeModal">{{ __('Cancel') }}</flux:button>
                <flux:button type="submit" variant="primary">
                    {{ $editingLoanId ? __('Update Loan') : __('Save Loan') }}
                </flux:button>
            </div>
        </form>
    </flux:modal>

    {{-- Alpine.js confirmation dialog.
         x-text (NOT x-html) treats the book/member names as plain text, so injected HTML never runs. --}}
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
            <h3 class="text-lg font-semibold text-zinc-900 dark:text-white" x-text="confirmTitle"></h3>
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
                    x-bind:class="confirmDanger ? 'bg-red-600 hover:bg-red-700' : 'bg-blue-600 hover:bg-blue-700'"
                    class="rounded-lg px-4 py-2 text-sm font-semibold text-white"
                >
                    {{ __('Confirm') }}
                </button>
            </div>
        </div>
    </div>

</div>
