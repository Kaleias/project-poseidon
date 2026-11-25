<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateLoanRequest extends FormRequest
{
    public function authorize(): bool
    {
        $loan = $this->route('loan');

        return $loan !== null && ($this->user()?->can('update', $loan) ?? false);
    }

    /**
     * Only the due date (extension) and notes may change after a loan is issued.
     * The book and member are fixed so stock counts can never drift.
     *
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
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
            'due_date.after_or_equal' => 'The due date cannot be before the borrow date.',
        ];
    }
}
