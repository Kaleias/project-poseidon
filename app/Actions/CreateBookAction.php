<?php

namespace App\Actions;

use App\Models\Book;

class CreateBookAction
{
    /**
     * Add a book to the catalogue. A brand-new book has every copy available.
     *
     * @param  array<string, mixed>  $data
     */
    public function execute(array $data): Book
    {
        // Blank optional inputs arrive as '' from the form; store them as NULL.
        $data['publisher'] = filled($data['publisher'] ?? null) ? $data['publisher'] : null;
        $data['publication_year'] = filled($data['publication_year'] ?? null) ? $data['publication_year'] : null;

        $data['available_quantity'] = (int) $data['quantity'];

        return Book::create($data);
    }
}
