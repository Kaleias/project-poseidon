<?php

use App\Livewire\Books\BookManagement;
use App\Models\Book;
use App\Models\Category;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->category = Category::create(['name' => 'Science']);
    $this->book = Book::factory()->create([
        'title' => 'Cosmos',
        'category_id' => $this->category->id,
    ]);
});

// ---------- Authentication ----------

test('guests cannot open the book management component', function () {
    Livewire::test(BookManagement::class)->assertForbidden();
});

// ---------- Students: read-only ----------

test('students can browse the catalogue but see no management buttons', function () {
    $student = User::factory()->create(['role' => 'student']);

    Livewire::actingAs($student)
        ->test(BookManagement::class)
        ->assertOk()
        ->assertSee('Cosmos')
        ->assertDontSee('Add Book')
        ->assertDontSee('Edit')
        ->assertDontSee('Delete')
        ->assertDontSee('Borrow');
});

test('students cannot add, edit, delete or change stock even by calling the methods directly', function () {
    $student = User::factory()->create(['role' => 'student']);

    // A fresh component for every call: once a call returns 403 the component
    // has no valid state left, so it cannot be reused for the next call.
    $component = fn () => Livewire::actingAs($student)->test(BookManagement::class);

    $component()->call('openForm')->assertForbidden();
    $component()->call('editBook', $this->book->id)->assertForbidden();
    $component()->call('deleteBook', $this->book->id)->assertForbidden();
    $component()->call('borrowBook', $this->book->id)->assertForbidden();
    $component()->call('returnBook', $this->book->id)->assertForbidden();

    $component()
        ->set('title', 'Sneaky')
        ->set('author', 'Student')
        ->set('isbn', '5555555555')
        ->set('category_id', $this->category->id)
        ->set('quantity', 1)
        ->call('saveBook')
        ->assertForbidden();

    $this->assertDatabaseMissing('books', ['isbn' => '5555555555']);
    $this->assertDatabaseHas('books', ['id' => $this->book->id]);
});

// ---------- Librarian vs admin ----------

test('a librarian can add and edit books but cannot delete them', function () {
    $librarian = User::factory()->create(['role' => 'librarian']);

    Livewire::actingAs($librarian)
        ->test(BookManagement::class)
        ->assertSee('Add Book')
        ->assertSee('Edit')
        ->assertDontSee('Delete')
        ->call('deleteBook', $this->book->id)
        ->assertForbidden();

    $this->assertDatabaseHas('books', ['id' => $this->book->id]);
});

test('an admin can delete a book', function () {
    $admin = User::factory()->create(['role' => 'admin']);

    Livewire::actingAs($admin)
        ->test(BookManagement::class)
        ->assertSee('Delete')
        ->call('deleteBook', $this->book->id)
        ->assertHasNoErrors();

    $this->assertDatabaseMissing('books', ['id' => $this->book->id]);
});

// ---------- XSS ----------

test('book titles are escaped and never rendered as HTML', function () {
    Book::factory()->create([
        'title' => '<script>alert("xss-title")</script>',
        'author' => '<img src=x onerror=alert("xss-author")>',
        'category_id' => $this->category->id,
    ]);

    Livewire::actingAs(User::factory()->create(['role' => 'admin']))
        ->test(BookManagement::class)
        ->assertDontSeeHtml('<script>alert("xss-title")</script>')
        ->assertDontSeeHtml('<img src=x onerror=alert("xss-author")>')
        ->assertSee('&lt;script&gt;', false);
});

// ---------- Tampering ----------

test('the editing book id cannot be changed from the browser', function () {
    $admin = User::factory()->create(['role' => 'admin']);

    expect(fn () => Livewire::actingAs($admin)
        ->test(BookManagement::class)
        ->set('editingBookId', $this->book->id))
        ->toThrow(Exception::class);
});

test('input is validated on the server even if the browser skips it', function () {
    $admin = User::factory()->create(['role' => 'admin']);

    Livewire::actingAs($admin)
        ->test(BookManagement::class)
        ->set('title', str_repeat('a', 300))
        ->set('author', 'Someone')
        ->set('isbn', '6666666666')
        ->set('category_id', 99999) // category that does not exist
        ->set('quantity', -5)
        ->call('saveBook')
        ->assertHasErrors(['title' => 'max', 'category_id' => 'exists', 'quantity' => 'min']);
});
