<?php

namespace App\Actions;

use App\Models\Book;
use Illuminate\Validation\ValidationException;

class ReturnBookAction
{
    /**
     * Put one copy back in stock, never above the total quantity.
     *
     * @throws ValidationException when every copy is already in the library
     */
    public function execute(Book $book): Book
    {
        $changed = Book::query()
            ->whereKey($book->id)
            ->whereColumn('available_quantity', '<', 'quantity')
            ->increment('available_quantity');

        if ($changed === 0) {
            throw ValidationException::withMessages([
                'book' => 'All copies of this book are already in the library.',
            ]);
        }

        return $book->refresh();
    }
}
