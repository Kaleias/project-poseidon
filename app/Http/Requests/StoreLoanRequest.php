<?php

namespace App\Http\Requests;

use App\Models\Loan;
use Illuminate\Foundation\Http\FormRequest;

class StoreLoanRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', Loan::class) ?? false;
    }

    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'book_id' => ['required', 'integer', 'exists:books,id'],
            'member_id' => ['required', 'integer', 'exists:members,id'],
            'borrowed_at' => ['required', 'date', 'before_or_equal:today'],
            'due_date' => ['required', 'date', 'after_or_equal:borrowed_at'],
            'notes' => ['nullable', 'string', 'max:500'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'book_id.required' => 'Please select a book.',
            'member_id.required' => 'Please select a member.',
            'borrowed_at.before_or_equal' => 'The borrow date cannot be in the future.',
            'due_date.after_or_equal' => 'The due date cannot be before the borrow date.',
        ];
    }

    /** Trim whitespace and turn an empty note into null before validating. */
    protected function prepareForValidation(): void
    {
        $this->merge([
            'notes' => filled($this->notes) ? trim((string) $this->notes) : null,
        ]);
    }
}
