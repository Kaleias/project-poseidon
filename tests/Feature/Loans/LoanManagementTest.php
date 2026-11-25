<?php

use App\Livewire\Loans\LoanManagement;
use App\Models\Book;
use App\Models\Loan;
use App\Models\Member;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->librarian = User::factory()->create(['role' => 'librarian']);
    $this->book = Book::factory()->create(['quantity' => 3, 'available_quantity' => 3]);
    $this->member = Member::factory()->create();
});

// ---------- Create ----------

test('a librarian can issue a loan and the book stock goes down', function () {
    Livewire::actingAs($this->librarian)
        ->test(LoanManagement::class)
        ->call('openCreateModal')
        ->set('book_id', $this->book->id)
        ->set('member_id', $this->member->id)
        ->set('notes', 'First loan')
        ->call('saveLoan')
        ->assertHasNoErrors()
        ->assertSet('showModal', false);

    $this->assertDatabaseHas('loans', [
        'book_id' => $this->book->id,
        'member_id' => $this->member->id,
        'issued_by' => $this->librarian->id,
        'status' => Loan::STATUS_BORROWED,
        'notes' => 'First loan',
    ]);

    expect($this->book->fresh()->available_quantity)->toBe(2);
});

test('the create form validates required fields', function () {
    Livewire::actingAs($this->librarian)
        ->test(LoanManagement::class)
        ->call('saveLoan')
        ->assertHasErrors(['book_id' => 'required', 'member_id' => 'required', 'borrowed_at' => 'required', 'due_date' => 'required']);

    $this->assertDatabaseCount('loans', 0);
});

test('the due date cannot be before the borrow date', function () {
    Livewire::actingAs($this->librarian)
        ->test(LoanManagement::class)
        ->set('book_id', $this->book->id)
        ->set('member_id', $this->member->id)
        ->set('borrowed_at', today()->toDateString())
        ->set('due_date', today()->subDay()->toDateString())
        ->call('saveLoan')
        ->assertHasErrors(['due_date' => 'after_or_equal']);
});

test('the borrow date cannot be in the future', function () {
    Livewire::actingAs($this->librarian)
        ->test(LoanManagement::class)
        ->set('book_id', $this->book->id)
        ->set('member_id', $this->member->id)
        ->set('borrowed_at', today()->addDay()->toDateString())
        ->set('due_date', today()->addDays(10)->toDateString())
        ->call('saveLoan')
        ->assertHasErrors(['borrowed_at' => 'before_or_equal']);
});

test('a book with no available copies cannot be loaned', function () {
    $book = Book::factory()->unavailable()->create();

    Livewire::actingAs($this->librarian)
        ->test(LoanManagement::class)
        ->set('book_id', $book->id)
        ->set('member_id', $this->member->id)
        ->set('borrowed_at', today()->toDateString())
        ->set('due_date', today()->addDays(14)->toDateString())
        ->call('saveLoan')
        ->assertHasErrors('book_id');

    $this->assertDatabaseCount('loans', 0);
});

test('a member cannot borrow the same book twice at the same time', function () {
    Loan::factory()->create(['book_id' => $this->book->id, 'member_id' => $this->member->id]);

    Livewire::actingAs($this->librarian)
        ->test(LoanManagement::class)
        ->set('book_id', $this->book->id)
        ->set('member_id', $this->member->id)
        ->set('borrowed_at', today()->toDateString())
        ->set('due_date', today()->addDays(14)->toDateString())
        ->call('saveLoan')
        ->assertHasErrors('book_id');

    $this->assertDatabaseCount('loans', 1);
});

// ---------- Read / search / filter ----------

test('loans are displayed in the list', function () {
    $member = Member::factory()->create(['name' => 'Aisyah Rahman']);
    $book = Book::factory()->create(['title' => 'Clean Architecture']);
    Loan::factory()->create(['member_id' => $member->id, 'book_id' => $book->id]);

    Livewire::actingAs($this->librarian)
        ->test(LoanManagement::class)
        ->assertSee('Aisyah Rahman')
        ->assertSee('Clean Architecture');
});

test('loans can be searched by member name', function () {
    $aisyah = Member::factory()->create(['name' => 'Aisyah Rahman']);
    $daniel = Member::factory()->create(['name' => 'Daniel Lee']);
    Loan::factory()->create(['member_id' => $aisyah->id]);
    Loan::factory()->create(['member_id' => $daniel->id]);

    Livewire::actingAs($this->librarian)
        ->test(LoanManagement::class)
        ->set('search', 'Aisyah')
        ->assertSee('Aisyah Rahman')
        ->assertDontSee('Daniel Lee');
});

test('loans can be filtered by status', function () {
    $overdueMember = Member::factory()->create(['name' => 'Overdue Person']);
    $activeMember = Member::factory()->create(['name' => 'Active Person']);
    $returnedMember = Member::factory()->create(['name' => 'Returned Person']);

    Loan::factory()->overdue()->create(['member_id' => $overdueMember->id]);
    Loan::factory()->create(['member_id' => $activeMember->id]);
    Loan::factory()->returned()->create(['member_id' => $returnedMember->id]);

    Livewire::actingAs($this->librarian)
        ->test(LoanManagement::class)
        ->set('statusFilter', 'overdue')
        ->assertSee('Overdue Person')
        ->assertDontSee('Active Person')
        ->assertDontSee('Returned Person')
        ->set('statusFilter', 'returned')
        ->assertSee('Returned Person')
        ->assertDontSee('Overdue Person');
});

// ---------- Update ----------

test('a librarian can extend the due date and edit notes', function () {
    $loan = Loan::factory()->create(['book_id' => $this->book->id, 'member_id' => $this->member->id]);
    $newDue = today()->addDays(30)->toDateString();

    Livewire::actingAs($this->librarian)
        ->test(LoanManagement::class)
        ->call('editLoan', $loan->id)
        ->assertSet('showModal', true)
        ->assertSet('editingLoanId', $loan->id)
        ->set('due_date', $newDue)
        ->set('notes', 'Extended for exams')
        ->call('updateLoan')
        ->assertHasNoErrors();

    $loan->refresh();

    expect($loan->due_date->toDateString())->toBe($newDue)
        ->and($loan->notes)->toBe('Extended for exams');
});

test('a returned loan cannot be edited', function () {
    $loan = Loan::factory()->returned()->create();

    Livewire::actingAs($this->librarian)
        ->test(LoanManagement::class)
        ->call('editLoan', $loan->id)
        ->assertForbidden();
});

test('the editing loan id cannot be tampered with from the browser', function () {
    $other = Loan::factory()->create();

    expect(fn () => Livewire::actingAs($this->librarian)
        ->test(LoanManagement::class)
        ->set('editingLoanId', $other->id))
        ->toThrow(Exception::class);
});

// ---------- Return ----------

test('returning a loan closes it and restores the book stock', function () {
    $book = Book::factory()->create(['quantity' => 3, 'available_quantity' => 2]);
    $loan = Loan::factory()->create(['book_id' => $book->id]);

    Livewire::actingAs($this->librarian)
        ->test(LoanManagement::class)
        ->call('returnLoan', $loan->id)
        ->assertHasNoErrors();

    $loan->refresh();

    expect($loan->status)->toBe(Loan::STATUS_RETURNED)
        ->and($loan->returned_at)->not->toBeNull()
        ->and($book->fresh()->available_quantity)->toBe(3);
});

test('a loan cannot be returned twice', function () {
    $book = Book::factory()->create(['quantity' => 3, 'available_quantity' => 3]);
    $loan = Loan::factory()->returned()->create(['book_id' => $book->id]);

    Livewire::actingAs($this->librarian)
        ->test(LoanManagement::class)
        ->call('returnLoan', $loan->id)
        ->assertForbidden();

    expect($book->fresh()->available_quantity)->toBe(3);
});

// ---------- Delete ----------

test('an admin can delete an active loan and the stock is restored', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    $book = Book::factory()->create(['quantity' => 3, 'available_quantity' => 2]);
    $loan = Loan::factory()->create(['book_id' => $book->id]);

    Livewire::actingAs($admin)
        ->test(LoanManagement::class)
        ->call('deleteLoan', $loan->id)
        ->assertHasNoErrors();

    $this->assertDatabaseMissing('loans', ['id' => $loan->id]);
    expect($book->fresh()->available_quantity)->toBe(3);
});

// ---------- Database rules ----------

test('a book that still has loans cannot be deleted (restrictOnDelete)', function () {
    $loan = Loan::factory()->create();

    expect(fn () => $loan->book->delete())->toThrow(Illuminate\Database\QueryException::class);

    $this->assertDatabaseHas('books', ['id' => $loan->book_id]);
});
