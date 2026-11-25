<?php

namespace App\Actions\Loans;

use App\Models\Book;
use App\Models\Loan;
use Illuminate\Support\Facades\DB;

class DeleteLoanAction
{
    /**
     * Delete a loan record. If the book was still out, put the copy back in stock.
     */
    public function execute(Loan $loan): bool
    {
        return DB::transaction(function () use ($loan) {
            if (! $loan->isReturned()) {
                Book::query()
                    ->whereKey($loan->book_id)
                    ->whereColumn('available_quantity', '<', 'quantity')
                    ->increment('available_quantity');
            }

            return (bool) $loan->delete();
        });
    }
}
