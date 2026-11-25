<?php

namespace App\Actions\Loans;

use App\Models\Book;
use App\Models\Loan;
use App\Models\Member;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CreateLoanAction
{
    /** Maximum number of unreturned loans one member may hold. */
    public const MAX_ACTIVE_LOANS = 5;

    /**
     * Issue a book to a member and reduce the book's available stock.
     *
     * @param  array{book_id: int|string, member_id: int|string, borrowed_at: string, due_date: string, notes?: string|null}  $data
     *
     * @throws ValidationException when a business rule is broken
     */
    public function execute(array $data, ?User $issuedBy = null): Loan
    {
        return DB::transaction(function () use ($data, $issuedBy) {
            // Lock the row so two librarians cannot lend the last copy at once.
            $book = Book::query()->lockForUpdate()->findOrFail($data['book_id']);
            $member = Member::query()->findOrFail($data['member_id']);

            if ($book->available_quantity < 1) {
                throw ValidationException::withMessages([
                    'book_id' => 'No copies of this book are currently available.',
                ]);
            }

            $activeLoans = Loan::query()->active()->where('member_id', $member->id);

            if ($activeLoans->clone()->count() >= self::MAX_ACTIVE_LOANS) {
                throw ValidationException::withMessages([
                    'member_id' => 'This member has reached the limit of '.self::MAX_ACTIVE_LOANS.' active loans.',
                ]);
            }

            if ($activeLoans->clone()->where('book_id', $book->id)->exists()) {
                throw ValidationException::withMessages([
                    'book_id' => 'This member already has this book on loan.',
                ]);
            }

            $loan = Loan::create([
                'book_id' => $book->id,
                'member_id' => $member->id,
                'issued_by' => $issuedBy?->id,
                'borrowed_at' => $data['borrowed_at'],
                'due_date' => $data['due_date'],
                'status' => Loan::STATUS_BORROWED,
                'notes' => $data['notes'] ?? null,
            ]);

            $book->decrement('available_quantity');

            return $loan;
        });
    }
}
