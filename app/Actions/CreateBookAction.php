<?php

namespace App\Actions;

use App\Models\Book;

class CreateBookAction
{
    public function execute(array $data): Book
    {
        return Book::create($data);
    }
}
