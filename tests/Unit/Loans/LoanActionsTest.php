<?php

use App\Actions\Loans\CreateLoanAction;
use App\Actions\Loans\DeleteLoanAction;
use App\Actions\Loans\ReturnLoanAction;
use App\Actions\Loans\UpdateLoanAction;
use App\Models\Book;
use App\Models\Loan;
use App\Models\Member;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

function loanData(Book $book, Member $member): array
{
    return [
        'book_id' => $book->id,
        'member_id' => $member->id,
        'borrowed_at' => today()->toDateString(),
        'due_date' => today()->addDays(14)->toDateString(),
        'notes' => null,
    ];
}

// ---------- CreateLoanAction ----------

test('CreateLoanAction creates the loan and reduces available stock', function () {
    $book = Book::factory()->create(['quantity' => 2, 'available_quantity' => 2]);
    $member = Member::factory()->create();
    $staff = User::factory()->create(['role' => 'librarian']);

    $loan = (new CreateLoanAction)->execute(loanData($book, $member), $staff);

    expect($loan->status)->toBe(Loan::STATUS_BORROWED)
        ->and($loan->issued_by)->toBe($staff->id)
        ->and($book->fresh()->available_quantity)->toBe(1);
});

test('CreateLoanAction refuses a book with no available copies', function () {
    $book = Book::factory()->unavailable()->create();

    expect(fn () => (new CreateLoanAction)->execute(loanData($book, Member::factory()->create())))
        ->toThrow(ValidationException::class);

    expect(Loan::count())->toBe(0);
});

test('CreateLoanAction enforces the maximum number of active loans per member', function () {
    $member = Member::factory()->create();
    Loan::factory()->count(CreateLoanAction::MAX_ACTIVE_LOANS)->create(['member_id' => $member->id]);
    $book = Book::factory()->create();

    expect(fn () => (new CreateLoanAction)->execute(loanData($book, $member)))
        ->toThrow(ValidationException::class);
});

test('CreateLoanAction does not count returned loans against the limit', function () {
    $member = Member::factory()->create();
    Loan::factory()->returned()->count(CreateLoanAction::MAX_ACTIVE_LOANS)->create(['member_id' => $member->id]);
    $book = Book::factory()->create();

    $loan = (new CreateLoanAction)->execute(loanData($book, $member));

    expect($loan->exists)->toBeTrue();
});

// ---------- UpdateLoanAction ----------

test('UpdateLoanAction changes only the due date and notes', function () {
    $loan = Loan::factory()->create();
    $originalBook = $loan->book_id;
    $newDue = today()->addDays(40)->toDateString();

    $updated = (new UpdateLoanAction)->execute($loan, [
        'due_date' => $newDue,
        'notes' => 'Extended',
        'book_id' => 999, // must be ignored
    ]);

    expect($updated->due_date->toDateString())->toBe($newDue)
        ->and($updated->notes)->toBe('Extended')
        ->and($updated->book_id)->toBe($originalBook);
});

test('UpdateLoanAction refuses to edit a returned loan', function () {
    $loan = Loan::factory()->returned()->create();

    expect(fn () => (new UpdateLoanAction)->execute($loan, ['due_date' => today()->addDay()->toDateString()]))
        ->toThrow(ValidationException::class);
});

// ---------- ReturnLoanAction ----------

test('ReturnLoanAction closes the loan and restores stock', function () {
    $book = Book::factory()->create(['quantity' => 3, 'available_quantity' => 2]);
    $loan = Loan::factory()->create(['book_id' => $book->id]);

    $returned = (new ReturnLoanAction)->execute($loan);

    expect($returned->status)->toBe(Loan::STATUS_RETURNED)
        ->and($returned->returned_at->toDateString())->toBe(today()->toDateString())
        ->and($book->fresh()->available_quantity)->toBe(3);
});

test('ReturnLoanAction never pushes available stock above the total quantity', function () {
    $book = Book::factory()->create(['quantity' => 3, 'available_quantity' => 3]);
    $loan = Loan::factory()->create(['book_id' => $book->id]);

    (new ReturnLoanAction)->execute($loan);

    expect($book->fresh()->available_quantity)->toBe(3);
});

test('ReturnLoanAction refuses a loan that is already returned', function () {
    $loan = Loan::factory()->returned()->create();

    expect(fn () => (new ReturnLoanAction)->execute($loan))
        ->toThrow(ValidationException::class);
});

// ---------- DeleteLoanAction ----------

test('DeleteLoanAction restores stock when an active loan is deleted', function () {
    $book = Book::factory()->create(['quantity' => 3, 'available_quantity' => 2]);
    $loan = Loan::factory()->create(['book_id' => $book->id]);

    (new DeleteLoanAction)->execute($loan);

    expect(Loan::find($loan->id))->toBeNull()
        ->and($book->fresh()->available_quantity)->toBe(3);
});

test('DeleteLoanAction leaves stock alone when a returned loan is deleted', function () {
    $book = Book::factory()->create(['quantity' => 3, 'available_quantity' => 3]);
    $loan = Loan::factory()->returned()->create(['book_id' => $book->id]);

    (new DeleteLoanAction)->execute($loan);

    expect($book->fresh()->available_quantity)->toBe(3);
});
