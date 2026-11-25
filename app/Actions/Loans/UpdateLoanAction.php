<?php

namespace App\Actions\Loans;

use App\Models\Loan;
use Illuminate\Validation\ValidationException;

class UpdateLoanAction
{
    /**
     * Extend the due date or change the notes of an active loan.
     * Book, member and status are deliberately NOT updatable here.
     *
     * @param  array{due_date: string, notes?: string|null}  $data
     *
     * @throws ValidationException when the loan was already returned
     */
    public function execute(Loan $loan, array $data): Loan
    {
        if ($loan->isReturned()) {
            throw ValidationException::withMessages([
                'loan' => 'A returned loan can no longer be edited.',
            ]);
        }

        $loan->update([
            'due_date' => $data['due_date'],
            'notes' => $data['notes'] ?? null,
        ]);

        return $loan;
    }
}
