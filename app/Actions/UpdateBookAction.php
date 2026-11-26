<?php

namespace App\Actions;

use App\Models\Book;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class UpdateBookAction
{
    /**
     * Update a book's details. If the total quantity changes, the number of
     * available copies is recalculated so copies already on loan stay on loan.
     *
     * @param  array<string, mixed>  $data
     *
     * @throws ValidationException when quantity is lower than the copies currently borrowed
     */
    public function execute(Book $book, array $data): Book
    {
        return DB::transaction(function () use ($book, $data) {
            $book = Book::query()->lockForUpdate()->findOrFail($book->id);

            $borrowed = $book->quantity - $book->available_quantity;
            $quantity = (int) $data['quantity'];

            if ($quantity < $borrowed) {
                throw ValidationException::withMessages([
                    'quantity' => 'Quantity cannot be less than the number of borrowed books.',
                ]);
            }

            $data['publisher'] = filled($data['publisher'] ?? null) ? $data['publisher'] : null;
            $data['publication_year'] = filled($data['publication_year'] ?? null) ? $data['publication_year'] : null;
            $data['available_quantity'] = $quantity - $borrowed;

            $book->update($data);

            return $book;
        });
    }
}
