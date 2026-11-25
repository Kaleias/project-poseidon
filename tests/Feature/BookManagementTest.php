<?php

use App\Livewire\Books\BookManagement;
use App\Models\Book;
use App\Models\Category;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->category = Category::create([
        'name' => 'Fiction',
    ]);
});

test('book can be added successfully', function () {
    Livewire::test(BookManagement::class)
        ->set('title', 'Test Book')
        ->set('author', 'Test Author')
        ->set('isbn', '1234567890')
        ->set('category_id', $this->category->id)
        ->set('publisher', 'Test Publisher')
        ->set('publication_year', 2024)
        ->set('quantity', 10)
        ->call('saveBook')
        ->assertHasNoErrors();

    $this->assertDatabaseHas('books', [
        'title' => 'Test Book',
        'isbn' => '1234567890',
        'quantity' => 10,
        'available_quantity' => 10,
    ]);
});

test('book can be deleted successfully', function () {
    $book = Book::create([
        'title' => 'Delete Test',
        'author' => 'Test Author',
        'isbn' => '9876543210',
        'category_id' => $this->category->id,
        'quantity' => 5,
        'available_quantity' => 5,
    ]);

    Livewire::test(BookManagement::class)
        ->call('deleteBook', $book->id);

    $this->assertDatabaseMissing('books', [
        'id' => $book->id,
    ]);
});

test('books can be displayed in the book management list', function () {
    $book = Book::create([
        'title' => 'Display Test Book',
        'author' => 'Test Author',
        'isbn' => '1112223334',
        'category_id' => $this->category->id,
        'quantity' => 10,
        'available_quantity' => 10,
    ]);

    Livewire::test(BookManagement::class)
        ->assertSee('Display Test Book')
        ->assertSee('Test Author');
});

test('books can be searched by title', function () {
    Book::create([
        'title' => 'Harry Potter',
        'author' => 'J.K. Rowling',
        'isbn' => '1111111111',
        'category_id' => $this->category->id,
        'quantity' => 10,
        'available_quantity' => 10,
    ]);

    Book::create([
        'title' => 'The Hunger Games',
        'author' => 'Suzanne Collins',
        'isbn' => '2222222222',
        'category_id' => $this->category->id,
        'quantity' => 5,
        'available_quantity' => 5,
    ]);

    Livewire::test(BookManagement::class)
        ->set('search', 'Harry')
        ->assertSee('Harry Potter')
        ->assertDontSee('The Hunger Games');
});

test('book details can be updated successfully', function () {
    $book = Book::create([
        'title' => 'Old Book Title',
        'author' => 'Old Author',
        'isbn' => '3333333333',
        'category_id' => $this->category->id,
        'quantity' => 10,
        'available_quantity' => 10,
    ]);

    Livewire::test(BookManagement::class)
        ->call('editBook', $book->id)
        ->set('title', 'Updated Book Title')
        ->set('author', 'Updated Author')
        ->set('isbn', '4444444444')
        ->set('category_id', $this->category->id)
        ->set('quantity', 20)
        ->call('updateBook')
        ->assertHasNoErrors();

    $this->assertDatabaseHas('books', [
        'id' => $book->id,
        'title' => 'Updated Book Title',
        'author' => 'Updated Author',
        'isbn' => '4444444444',
        'quantity' => 20,
        'available_quantity' => 20,
    ]);
});

test('book cannot be added with empty required fields', function () {
    Livewire::test(BookManagement::class)
        ->call('saveBook')
        ->assertHasErrors([
            'title',
            'author',
            'isbn',
            'category_id',
        ]);

    $this->assertDatabaseCount('books', 0);
});

test('book can be borrowed successfully', function () {
    $book = Book::create([
        'title' => 'Borrow Test Book',
        'author' => 'Test Author',
        'isbn' => '5555555555',
        'category_id' => $this->category->id,
        'quantity' => 10,
        'available_quantity' => 10,
    ]);

    Livewire::test(BookManagement::class)
        ->call('borrowBook', $book->id)
        ->assertHasNoErrors();

    $this->assertDatabaseHas('books', [
        'id' => $book->id,
        'quantity' => 10,
        'available_quantity' => 9,
    ]);
});

test('book can be returned successfully', function () {
    $book = Book::create([
        'title' => 'Return Test Book',
        'author' => 'Test Author',
        'isbn' => '6666666666',
        'category_id' => $this->category->id,
        'quantity' => 10,
        'available_quantity' => 9,
    ]);

    Livewire::test(BookManagement::class)
        ->call('returnBook', $book->id)
        ->assertHasNoErrors();

    $this->assertDatabaseHas('books', [
        'id' => $book->id,
        'quantity' => 10,
        'available_quantity' => 10,
    ]);
});