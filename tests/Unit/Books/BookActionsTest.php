<?php

use App\Actions\BorrowBookAction;
use App\Actions\CreateBookAction;
use App\Actions\DeleteBookAction;
use App\Actions\ReturnBookAction;
use App\Actions\UpdateBookAction;
use App\Models\Book;
use App\Models\Category;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

// ---------- CreateBookAction ----------

test('CreateBookAction makes every new copy available', function () {
    $category = Category::create(['name' => 'Fantasy']);

    $book = (new CreateBookAction)->execute([
        'title' => 'The Hobbit',
        'author' => 'J.R.R. Tolkien',
        'isbn' => '7777777777',
        'category_id' => $category->id,
        'publisher' => '',
        'publication_year' => '',
        'quantity' => 6,
    ]);

    expect($book->quantity)->toBe(6)
        ->and($book->available_quantity)->toBe(6)
        ->and($book->publisher)->toBeNull()
        ->and($book->publication_year)->toBeNull();
});

// ---------- UpdateBookAction ----------

test('UpdateBookAction keeps borrowed copies out when the quantity grows', function () {
    $book = Book::factory()->create(['quantity' => 10, 'available_quantity' => 7]); // 3 out

    $updated = (new UpdateBookAction)->execute($book, [
        'title' => $book->title,
        'author' => $book->author,
        'isbn' => $book->isbn,
        'category_id' => $book->category_id,
        'publisher' => $book->publisher,
        'publication_year' => $book->publication_year,
        'quantity' => 15,
    ]);

    expect($updated->quantity)->toBe(15)
        ->and($updated->available_quantity)->toBe(12); // still 3 out
});

test('UpdateBookAction refuses a quantity below the borrowed copies', function () {
    $book = Book::factory()->create(['quantity' => 10, 'available_quantity' => 7]); // 3 out

    expect(fn () => (new UpdateBookAction)->execute($book, [
        'title' => $book->title,
        'author' => $book->author,
        'isbn' => $book->isbn,
        'category_id' => $book->category_id,
        'quantity' => 2,
    ]))->toThrow(ValidationException::class);

    expect($book->fresh()->quantity)->toBe(10);
});

// ---------- BorrowBookAction ----------

test('BorrowBookAction takes one copy out of stock', function () {
    $book = Book::factory()->create(['quantity' => 3, 'available_quantity' => 3]);

    $result = (new BorrowBookAction)->execute($book);

    expect($result->available_quantity)->toBe(2);
});

test('BorrowBookAction never lets stock go below zero', function () {
    $book = Book::factory()->unavailable()->create();

    expect(fn () => (new BorrowBookAction)->execute($book))
        ->toThrow(ValidationException::class);

    expect($book->fresh()->available_quantity)->toBe(0);
});

// ---------- ReturnBookAction ----------

test('ReturnBookAction puts one copy back in stock', function () {
    $book = Book::factory()->create(['quantity' => 3, 'available_quantity' => 1]);

    $result = (new ReturnBookAction)->execute($book);

    expect($result->available_quantity)->toBe(2);
});

test('ReturnBookAction never lets stock go above the total quantity', function () {
    $book = Book::factory()->create(['quantity' => 3, 'available_quantity' => 3]);

    expect(fn () => (new ReturnBookAction)->execute($book))
        ->toThrow(ValidationException::class);

    expect($book->fresh()->available_quantity)->toBe(3);
});

// ---------- DeleteBookAction ----------

test('DeleteBookAction deletes a book with no copies on loan', function () {
    $book = Book::factory()->create(['quantity' => 4, 'available_quantity' => 4]);

    (new DeleteBookAction)->execute($book);

    expect(Book::find($book->id))->toBeNull();
});

test('DeleteBookAction refuses while copies are on loan', function () {
    $book = Book::factory()->create(['quantity' => 4, 'available_quantity' => 3]);

    expect(fn () => (new DeleteBookAction)->execute($book))
        ->toThrow(ValidationException::class);

    expect(Book::find($book->id))->not->toBeNull();
});
