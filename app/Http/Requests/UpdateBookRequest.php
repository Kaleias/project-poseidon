<?php

namespace App\Http\Requests;

use App\Models\Book;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateBookRequest extends FormRequest
{
    public function authorize(): bool
    {
        $book = $this->route('book');

        return $book instanceof Book && ($this->user()?->can('update', $book) ?? false);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $book = $this->route('book');

        return self::rulesFor($book instanceof Book ? $book->id : null);
    }

    /**
     * Same rules as creating a book, except the ISBN may stay the same
     * for the book being edited. Livewire has no route model, so the
     * component calls this directly with the book's id.
     *
     * @return array<string, mixed>
     */
    public static function rulesFor(?int $bookId): array
    {
        return [
            'title' => 'required|string|max:255',
            'author' => 'required|string|max:255',
            'isbn' => ['required', 'string', 'max:255', Rule::unique('books', 'isbn')->ignore($bookId)],
            'category_id' => 'required|exists:categories,id',
            'publisher' => 'nullable|string|max:255',
            'publication_year' => 'nullable|integer|min:1000|max:'.date('Y'),
            'quantity' => 'required|integer|min:0',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'category_id.required' => 'Please select a category.',
            'isbn.unique' => 'A book with this ISBN already exists.',
        ];
    }
}
