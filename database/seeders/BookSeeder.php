<?php

namespace Database\Seeders;

use App\Models\Book;
use App\Models\Category;
use Illuminate\Database\Seeder;

class BookSeeder extends Seeder
{
    public function run(): void
    {
        $this->call(CategorySeeder::class);

        $books = [
            ['Clean Code', 'Robert C. Martin', '9780132350884', 'Prentice Hall', 2008, 'Technology'],
            ['The Pragmatic Programmer', 'Andrew Hunt', '9780135957059', 'Addison-Wesley', 2019, 'Technology'],
            ['Introduction to Algorithms', 'Thomas H. Cormen', '9780262046305', 'MIT Press', 2022, 'Education'],
            ['A Brief History of Time', 'Stephen Hawking', '9780553380163', 'Bantam', 1998, 'Science'],
            ['Cosmos', 'Carl Sagan', '9780345539434', 'Ballantine Books', 2013, 'Science'],
            ['Sapiens', 'Yuval Noah Harari', '9780062316097', 'Harper', 2015, 'History'],
            ['The Hobbit', 'J.R.R. Tolkien', '9780547928227', 'Houghton Mifflin', 2012, 'Fantasy'],
            ['Harry Potter and the Sorcerer\'s Stone', 'J.K. Rowling', '9780590353427', 'Scholastic', 1998, 'Fantasy'],
            ['Pride and Prejudice', 'Jane Austen', '9780141439518', 'Penguin Classics', 2002, 'Romance'],
            ['To Kill a Mockingbird', 'Harper Lee', '9780061120084', 'Harper Perennial', 2006, 'Fiction'],
            ['Atomic Habits', 'James Clear', '9780735211292', 'Avery', 2018, 'Non-Fiction'],
            ['Laravel: Up & Running', 'Matt Stauffer', '9781492041214', 'O\'Reilly Media', 2019, 'Technology'],
        ];

        foreach ($books as [$title, $author, $isbn, $publisher, $year, $category]) {
            Book::firstOrCreate(['isbn' => $isbn], [
                'title' => $title,
                'author' => $author,
                'publisher' => $publisher,
                'publication_year' => $year,
                'quantity' => 5,
                'available_quantity' => 5,
                'category_id' => Category::where('name', $category)->value('id'),
            ]);
        }
    }
}
