<?php

use App\Livewire\Books\BookManagement;
use App\Models\Book;
use App\Models\Category;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(function () {
    // The BookPolicy requires a logged-in user. A librarian can do everything except delete.
    $this->actingAs(User::factory()->create(['role' => 'admin']));
    $this->category = Category::create(['name' => 'Fiction']);
});

test('book can be added successfully', function () {
    Livewire::test(BookManagement::class)
        ->set('title', 'Harry Potter')
        ->set('author', 'J.K. Rowling')
        ->set('isbn', '1111111111')
        ->set('category_id', $this->category->id)
        ->set('publisher', 'Bloomsbury')
        ->set('publication_year', 1997)
        ->set('quantity', 10)
        ->call('saveBook')
        ->assertHasNoErrors();

    $this->assertDatabaseHas('books', [
        'title' => 'Harry Potter',
        'author' => 'J.K. Rowling',
        'isbn' => '1111111111',
        'quantity' => 10,
        'available_quantity' => 10,
    ]);
});

test('book can be added with the optional fields left blank', function () {
    Livewire::test(BookManagement::class)
        ->set('title', 'No Extras')
        ->set('author', 'Someone')
        ->set('isbn', '2222222222')
        ->set('category_id', $this->category->id)
        ->set('publisher', '')
        ->set('publication_year', '')
        ->set('quantity', 3)
        ->call('saveBook')
        ->assertHasNoErrors();

    $this->assertDatabaseHas('books', [
        'isbn' => '2222222222',
        'publisher' => null,
        'publication_year' => null,
    ]);
});

test('book cannot be added with empty required fields', function () {
    Livewire::test(BookManagement::class)
        ->set('title', '')
        ->set('author', '')
        ->set('isbn', '')
        ->set('category_id', '')
        ->set('quantity', '')
        ->call('saveBook')
        ->assertHasErrors(['title', 'author', 'isbn', 'category_id', 'quantity']);

    $this->assertDatabaseCount('books', 0);
});

test('book cannot be added with a duplicate isbn', function () {
    Book::factory()->create(['isbn' => '3333333333']);

    Livewire::test(BookManagement::class)
        ->set('title', 'Copycat')
        ->set('author', 'Someone')
        ->set('isbn', '3333333333')
        ->set('category_id', $this->category->id)
        ->set('quantity', 1)
        ->call('saveBook')
        ->assertHasErrors(['isbn']);
});

test('books can be displayed in the book management list', function () {
    Book::factory()->create(['title' => 'Harry Potter', 'author' => 'J.K. Rowling', 'category_id' => $this->category->id]);
    Book::factory()->create(['title' => 'The Hobbit', 'author' => 'J.R.R. Tolkien', 'category_id' => $this->category->id]);

    Livewire::test(BookManagement::class)
        ->assertSee('Harry Potter')
        ->assertSee('The Hobbit');
});

test('books can be searched by title', function () {
    Book::factory()->create(['title' => 'Harry Potter', 'category_id' => $this->category->id]);
    Book::factory()->create(['title' => 'The Hunger Games', 'category_id' => $this->category->id]);

    Livewire::test(BookManagement::class)
        ->set('search', 'Harry')
        ->assertSee('Harry Potter')
        ->assertDontSee('The Hunger Games');
});

test('books can be searched by author and isbn', function () {
    Book::factory()->create(['title' => 'Book A', 'author' => 'Zainal Abidin', 'isbn' => '9990000000001']);
    Book::factory()->create(['title' => 'Book B', 'author' => 'Another Person', 'isbn' => '9990000000002']);

    Livewire::test(BookManagement::class)
        ->set('search', 'Zainal')
        ->assertSee('Book A')
        ->assertDontSee('Book B')
        ->set('search', '9990000000002')
        ->assertSee('Book B')
        ->assertDontSee('Book A');
});

test('book details can be updated successfully', function () {
    $book = Book::factory()->create([
        'title' => 'Old Title',
        'author' => 'Old Author',
        'isbn' => '4444444444',
        'category_id' => $this->category->id,
        'quantity' => 10,
        'available_quantity' => 10,
    ]);

    Livewire::test(BookManagement::class)
        ->call('editBook', $book->id)
        ->assertSet('editingBookId', $book->id)
        ->set('title', 'New Title')
        ->set('author', 'New Author')
        ->set('quantity', 20)
        ->call('updateBook')
        ->assertHasNoErrors();

    $this->assertDatabaseHas('books', [
        'id' => $book->id,
        'title' => 'New Title',
        'author' => 'New Author',
        'isbn' => '4444444444',
        'quantity' => 20,
        'available_quantity' => 20,
    ]);
});

test('quantity cannot be reduced below the number of borrowed copies', function () {
    $book = Book::factory()->create([
        'category_id' => $this->category->id,
        'quantity' => 10,
        'available_quantity' => 6, // 4 copies are out
    ]);

    Livewire::test(BookManagement::class)
        ->call('editBook', $book->id)
        ->set('quantity', 3)
        ->call('updateBook')
        ->assertHasErrors(['quantity']);

    $this->assertDatabaseHas('books', ['id' => $book->id, 'quantity' => 10, 'available_quantity' => 6]);
});

test('book can be deleted successfully', function () {
    $book = Book::factory()->create(['category_id' => $this->category->id]);

    Livewire::test(BookManagement::class)
        ->call('deleteBook', $book->id)
        ->assertHasNoErrors();

    $this->assertDatabaseMissing('books', ['id' => $book->id]);
});

test('a book with copies on loan cannot be deleted', function () {
    $book = Book::factory()->create([
        'category_id' => $this->category->id,
        'quantity' => 5,
        'available_quantity' => 4,
    ]);

    Livewire::test(BookManagement::class)
        ->call('deleteBook', $book->id)
        ->assertHasErrors(['book']);

    $this->assertDatabaseHas('books', ['id' => $book->id]);
});

test('book can be borrowed successfully', function () {
    $book = Book::factory()->create(['category_id' => $this->category->id, 'quantity' => 5, 'available_quantity' => 5]);

    Livewire::test(BookManagement::class)
        ->call('borrowBook', $book->id);

    expect($book->fresh()->available_quantity)->toBe(4);
});

test('book cannot be borrowed when no copies are available', function () {
    $book = Book::factory()->unavailable()->create(['category_id' => $this->category->id]);

    Livewire::test(BookManagement::class)
        ->call('borrowBook', $book->id)
        ->assertHasErrors(['book']);

    expect($book->fresh()->available_quantity)->toBe(0);
});

test('book can be returned successfully', function () {
    $book = Book::factory()->create(['category_id' => $this->category->id, 'quantity' => 5, 'available_quantity' => 3]);

    Livewire::test(BookManagement::class)
        ->call('returnBook', $book->id);

    expect($book->fresh()->available_quantity)->toBe(4);
});

test('book cannot be returned when every copy is already in the library', function () {
    $book = Book::factory()->create(['category_id' => $this->category->id, 'quantity' => 5, 'available_quantity' => 5]);

    Livewire::test(BookManagement::class)
        ->call('returnBook', $book->id)
        ->assertHasErrors(['book']);

    expect($book->fresh()->available_quantity)->toBe(5);
});

test('the book list is paginated', function () {
    Book::factory()->count(10)->create(['category_id' => $this->category->id]);

    $component = Livewire::test(BookManagement::class);

    expect($component->viewData('books')->perPage())->toBe(8)
        ->and($component->viewData('books')->total())->toBe(10)
        ->and($component->viewData('books')->count())->toBe(8);
});
