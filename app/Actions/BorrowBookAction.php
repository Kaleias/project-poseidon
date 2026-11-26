<?php

namespace App\Actions;

use App\Models\Book;
use Illuminate\Validation\ValidationException;

class BorrowBookAction
{
    /**
     * Take one copy out of stock. The "> 0" check and the decrement happen in a
     * single SQL statement, so two clicks at once can never push stock below zero.
     *
     * @throws ValidationException when no copy is available
     */
    public function execute(Book $book): Book
    {
        $changed = Book::query()
            ->whereKey($book->id)
            ->where('available_quantity', '>', 0)
            ->decrement('available_quantity');

        if ($changed === 0) {
            throw ValidationException::withMessages([
                'book' => 'No copies of this book are currently available.',
            ]);
        }

        return $book->refresh();
    }
}
