<?php

namespace App\Livewire\Loans;

use App\Actions\Loans\CreateLoanAction;
use App\Actions\Loans\DeleteLoanAction;
use App\Actions\Loans\ReturnLoanAction;
use App\Actions\Loans\UpdateLoanAction;
use App\Http\Requests\StoreLoanRequest;
use App\Http\Requests\UpdateLoanRequest;
use App\Models\Book;
use App\Models\Loan;
use App\Models\Member;
use Livewire\Attributes\Locked;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * Loan Management (Module 2).
 *
 * The component is a slim orchestrator: it authorizes with the LoanPolicy,
 * validates with the Form Request rules, and hands the real work to Action classes.
 */
class LoanManagement extends Component
{
    use WithPagination;

    // Form fields
    public $book_id = '';

    public $member_id = '';

    public $borrowed_at = '';

    public $due_date = '';

    public $notes = '';

    // List controls
    public $search = '';

    public $statusFilter = 'all';

    public $showModal = false;

    /**
     * #[Locked] stops the browser from tampering with this id through
     * Livewire's request payload (otherwise a user could edit any loan).
     */
    #[Locked]
    public $editingLoanId = null;

    public function mount(): void
    {
        $this->authorize('viewAny', Loan::class);
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedStatusFilter(): void
    {
        $this->resetPage();
    }

    public function openCreateModal(): void
    {
        $this->authorize('create', Loan::class);

        $this->resetForm();
        $this->borrowed_at = today()->toDateString();
        $this->due_date = today()->addDays(14)->toDateString();
        $this->showModal = true;
    }

    public function saveLoan(CreateLoanAction $action): void
    {
        $this->authorize('create', Loan::class);

        $validated = $this->validate((new StoreLoanRequest)->rules());

        $action->execute($validated, auth()->user());

        $this->closeModal();

        session()->flash('success', 'Loan recorded successfully!');
    }

    public function editLoan(int $id): void
    {
        $loan = Loan::findOrFail($id);

        $this->authorize('update', $loan);

        $this->editingLoanId = $loan->id;
        $this->book_id = $loan->book_id;
        $this->member_id = $loan->member_id;
        $this->borrowed_at = $loan->borrowed_at->toDateString();
        $this->due_date = $loan->due_date->toDateString();
        $this->notes = $loan->notes ?? '';

        $this->resetValidation();
        $this->showModal = true;
    }

    public function updateLoan(UpdateLoanAction $action): void
    {
        // Re-fetch and re-authorize: never trust state coming from the browser.
        $loan = Loan::findOrFail($this->editingLoanId);

        $this->authorize('update', $loan);

        $validated = $this->validate((new UpdateLoanRequest)->rules());

        $action->execute($loan, $validated);

        $this->closeModal();

        session()->flash('success', 'Loan updated successfully!');
    }

    public function returnLoan(int $id, ReturnLoanAction $action): void
    {
        $loan = Loan::findOrFail($id);

        $this->authorize('markReturned', $loan);

        $action->execute($loan);

        session()->flash('success', 'Book returned successfully!');
    }

    public function deleteLoan(int $id, DeleteLoanAction $action): void
    {
        $loan = Loan::findOrFail($id);

        $this->authorize('delete', $loan);

        $action->execute($loan);

        session()->flash('success', 'Loan deleted successfully!');
    }

    public function closeModal(): void
    {
        $this->showModal = false;
        $this->resetForm();
    }

    public function resetForm(): void
    {
        $this->reset([
            'book_id',
            'member_id',
            'borrowed_at',
            'due_date',
            'notes',
            'editingLoanId',
        ]);

        $this->resetValidation();
    }

    public function render()
    {
        $term = '%'.$this->search.'%';

        $loans = Loan::query()
            ->with(['book', 'member', 'issuer'])
            ->when($this->search !== '', function ($query) use ($term) {
                // One grouped WHERE so the OR conditions cannot leak past the other filters.
                $query->where(function ($query) use ($term) {
                    $query
                        ->whereHas('member', fn ($member) => $member
                            ->where('name', 'like', $term)
                            ->orWhere('member_id', 'like', $term))
                        ->orWhereHas('book', fn ($book) => $book
                            ->where('title', 'like', $term)
                            ->orWhere('isbn', 'like', $term));
                });
            })
            ->when($this->statusFilter === 'borrowed', fn ($query) => $query->active())
            ->when($this->statusFilter === 'overdue', fn ($query) => $query->overdue())
            ->when($this->statusFilter === 'returned', fn ($query) => $query->returned())
            ->latest('borrowed_at')
            ->latest('id')
            ->paginate(8);

        return view('livewire.loans.loan-management', [
            'loans' => $loans,
            'editingLoan' => $this->editingLoanId ? Loan::with(['book', 'member'])->find($this->editingLoanId) : null,
            'availableBooks' => Book::where('available_quantity', '>', 0)->orderBy('title')->get(['id', 'title', 'available_quantity']),
            'members' => Member::orderBy('name')->get(['id', 'member_id', 'name']),
            'activeCount' => Loan::active()->count(),
            'overdueCount' => Loan::overdue()->count(),
        ]);
    }
}
