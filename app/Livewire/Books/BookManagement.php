<?php

namespace App\Livewire\Books;

use App\Actions\BorrowBookAction;
use App\Actions\CreateBookAction;
use App\Actions\DeleteBookAction;
use App\Actions\ReturnBookAction;
use App\Actions\UpdateBookAction;
use App\Http\Requests\StoreBookRequest;
use App\Http\Requests\UpdateBookRequest;
use App\Models\Book;
use App\Models\Category;
use Livewire\Attributes\Locked;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * Book Management.
 *
 * A slim orchestrator: authorize with BookPolicy, validate with the Form Request
 * rules, then hand the real work to an Action class.
 */
class BookManagement extends Component
{
    use WithPagination;

    public $search = '';

    public $showForm = false;

    /**
     * #[Locked]: the browser cannot change this id in the Livewire payload,
     * so a user cannot trick updateBook() into editing a different book.
     */
    #[Locked]
    public $editingBookId = null;

    public $title = '';

    public $author = '';

    public $isbn = '';

    public $category_id = '';

    public $publisher = '';

    public $publication_year = '';

    public $quantity = 0;

    public function mount(): void
    {
        $this->authorize('viewAny', Book::class);
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function openForm(): void
    {
        $this->authorize('create', Book::class);

        $this->resetForm();
        $this->showForm = true;
    }

    public function saveBook(CreateBookAction $action): void
    {
        $this->authorize('create', Book::class);

        $validated = $this->validate((new StoreBookRequest)->rules());

        $action->execute($validated);

        $this->closeForm();

        session()->flash('success', 'Book added successfully!');
    }

    public function editBook($id): void
    {
        $book = Book::findOrFail($id);

        $this->authorize('update', $book);

        $this->resetValidation();

        $this->editingBookId = $book->id;
        $this->title = $book->title;
        $this->author = $book->author;
        $this->isbn = $book->isbn;
        $this->category_id = $book->category_id;
        $this->publisher = $book->publisher;
        $this->publication_year = $book->publication_year;
        $this->quantity = $book->quantity;

        $this->showForm = true;
    }

    public function updateBook(UpdateBookAction $action): void
    {
        // Re-fetch and re-authorize: never trust state that came from the browser.
        $book = Book::findOrFail($this->editingBookId);

        $this->authorize('update', $book);

        $validated = $this->validate(UpdateBookRequest::rulesFor($book->id));

        $action->execute($book, $validated);

        $this->closeForm();

        session()->flash('success', 'Book updated successfully!');
    }

    public function deleteBook($id, DeleteBookAction $action): void
    {
        $book = Book::findOrFail($id);

        $this->authorize('delete', $book);

        $action->execute($book);

        session()->flash('success', 'Book deleted successfully!');
    }

    public function borrowBook($id, BorrowBookAction $action): void
    {
        $book = Book::findOrFail($id);

        $this->authorize('update', $book);

        $action->execute($book);

        session()->flash('success', 'Book borrowed successfully!');
    }

    public function returnBook($id, ReturnBookAction $action): void
    {
        $book = Book::findOrFail($id);

        $this->authorize('update', $book);

        $action->execute($book);

        session()->flash('success', 'Book returned successfully!');
    }

    public function closeForm(): void
    {
        $this->showForm = false;
        $this->resetForm();
    }

    public function resetForm(): void
    {
        $this->reset([
            'title',
            'author',
            'isbn',
            'category_id',
            'publisher',
            'publication_year',
            'quantity',
            'editingBookId',
        ]);

        $this->resetValidation();
    }

    public function render()
    {
        $term = '%'.$this->search.'%';

        $books = Book::query()
            ->with('category')
            ->when($this->search !== '', function ($query) use ($term) {
                // One grouped WHERE so the ORs stay inside the search condition.
                $query->where(fn ($q) => $q
                    ->where('title', 'like', $term)
                    ->orWhere('author', 'like', $term)
                    ->orWhere('isbn', 'like', $term));
            })
            ->latest()
            ->paginate(8);

        return view('livewire.books.book-management', [
            'books' => $books,
            'categories' => Category::orderBy('name')->get(),
        ]);
    }
}
