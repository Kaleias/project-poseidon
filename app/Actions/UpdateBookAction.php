<?php

namespace App\Actions;

use App\Models\Book;

class UpdateBookAction
{
    public function execute(Book $book, array $data): Book
    {
        $book->update($data);

        return $book;
    }
}