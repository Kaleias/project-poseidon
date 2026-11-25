<?php

namespace App\Actions\Loans;

use App\Models\Book;
use App\Models\Loan;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ReturnLoanAction
{
    /**
     * Check a book back in: close the loan and put the copy back in stock.
     *
     * @throws ValidationException when the loan was already returned
     */
    public function execute(Loan $loan): Loan
    {
        return DB::transaction(function () use ($loan) {
            // Re-read inside the transaction so a double click cannot return twice.
            $loan = Loan::query()->lockForUpdate()->findOrFail($loan->id);

            if ($loan->isReturned()) {
                throw ValidationException::withMessages([
                    'loan' => 'This loan has already been returned.',
                ]);
            }

            $loan->update([
                'returned_at' => today()->toDateString(),
                'status' => Loan::STATUS_RETURNED,
            ]);

            // Never let available_quantity climb above the total quantity.
            Book::query()
                ->whereKey($loan->book_id)
                ->whereColumn('available_quantity', '<', 'quantity')
                ->increment('available_quantity');

            return $loan;
        });
    }
}
