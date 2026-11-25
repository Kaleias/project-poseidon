<?php

namespace App\Livewire\Books;

use App\Actions\CreateBookAction;
use App\Http\Requests\StoreBookRequest;
use App\Models\Book;
use App\Models\Category;
use Livewire\Component;
use Illuminate\Support\Facades\Validator;
use App\Actions\UpdateBookAction;

class BookManagement extends Component
{
    public $search = '';
    public $showForm = false;
    public $isEditMode = false;
    public $editingBookId = null;

    public $title = '';
    public $author = '';
    public $isbn = '';
    public $category_id = '';
    public $publisher = '';
    public $publication_year = '';
    public $quantity = 0;

    public function openForm()
    {
        $this->resetValidation();
        $this->isEditMode = false;
        $this->editingBookId = null;
        $this->showForm = true;
    }

    public function editBook($id)
    {
        $this->resetValidation();

        $book = Book::findOrFail($id);

        $this->editingBookId = $book->id;
        $this->title = $book->title;
        $this->author = $book->author;
        $this->isbn = $book->isbn;
        $this->category_id = $book->category_id;
        $this->publisher = $book->publisher;
        $this->publication_year = $book->publication_year;
        $this->quantity = $book->quantity;

        $this->isEditMode = true;
        $this->showForm = true;
    }
    public function updateBook(UpdateBookAction $action)
    {
        $book = Book::findOrFail($this->editingBookId);

        $data = [
            'title' => $this->title,
            'author' => $this->author,
            'isbn' => $this->isbn,
            'category_id' => $this->category_id,
            'publisher' => $this->publisher,
            'publication_year' => $this->publication_year,
            'quantity' => $this->quantity,
        ];

        $rules = (new StoreBookRequest())->rules();

        $rules['isbn'] = 'required|string|max:255|unique:books,isbn,' . $book->id;

        $validated = Validator::make($data, $rules)->validate();

        $borrowedQuantity = $book->quantity - $book->available_quantity;

        if ($validated['quantity'] < $borrowedQuantity) {
            $this->addError(
                'quantity',
                'Quantity cannot be less than the number of borrowed books.'
            );

            return;
        }

        $validated['available_quantity'] = $validated['quantity'] - $borrowedQuantity;

        $action->execute($book, $validated);

        $this->closeForm();

        $this->isEditMode = false;
        $this->editingBookId = null;

        session()->flash('success', 'Book updated successfully!');
    }

    public function deleteBook($id)
    {
        $book = Book::findOrFail($id);

        $book->delete();

        session()->flash('success', 'Book deleted successfully!');
    }

    public function borrowBook($id)
    {
        $book = Book::findOrFail($id);

        if ($book->available_quantity > 0) {
            $book->decrement('available_quantity');

            session()->flash('success', 'Book borrowed successfully!');
        }
    }

    public function returnBook($id)
    {
        $book = Book::findOrFail($id);

        if ($book->available_quantity < $book->quantity) {
            $book->increment('available_quantity');

            session()->flash('success', 'Book returned successfully!');
        }
    }

    public function closeForm()
    {
        $this->showForm = false;
        $this->reset([
            'title',
            'author',
            'isbn',
            'category_id',
            'publisher',
            'publication_year',
            'quantity',
        ]);
        $this->resetValidation();
    }

    public function saveBook(CreateBookAction $action)
    {
        $this->resetValidation();

        $data = [
            'title' => $this->title,
            'author' => $this->author,
            'isbn' => $this->isbn,
            'category_id' => $this->category_id,
            'publisher' => $this->publisher,
            'publication_year' => $this->publication_year,
            'quantity' => $this->quantity,
        ];

        $validated = Validator::make(
            $data,
            (new StoreBookRequest())->rules()
        )->validate();

        $validated['available_quantity'] = $validated['quantity'];

        $action->execute($validated);

        $this->closeForm();

        session()->flash('success', 'Book added successfully!');
    }

    public function render()
    {
        $books = Book::where('title', 'like', '%' . $this->search . '%')
            ->orWhere('author', 'like', '%' . $this->search . '%')
            ->orWhere('isbn', 'like', '%' . $this->search . '%')
            ->latest()
            ->get();

        return view('livewire.books.book-management', [
            'books' => $books,
            'categories' => Category::orderBy('name')->get(),
        ]);
    }
}
