<?php

namespace App\Actions;

use App\Models\Book;
use Illuminate\Validation\ValidationException;

class DeleteBookAction
{
    /**
     * Remove a book from the catalogue.
     *
     * @throws ValidationException when copies are still borrowed
     */
    public function execute(Book $book): bool
    {
        // quantity - available_quantity = copies currently borrowed
        if ($book->quantity - $book->available_quantity > 0) {
            throw ValidationException::withMessages([
                'book' => 'This book cannot be deleted while copies are still borrowed.',
            ]);
        }

        return (bool) $book->delete();
    }
}
