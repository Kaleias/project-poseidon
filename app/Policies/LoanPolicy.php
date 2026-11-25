<?php

namespace App\Policies;

use App\Models\Loan;
use App\Models\User;

class LoanPolicy
{
    /** Roles allowed to operate the circulation desk. */
    private function isStaff(User $user): bool
    {
        return in_array($user->role, ['admin', 'librarian'], true);
    }

    public function viewAny(User $user): bool
    {
        return $this->isStaff($user);
    }

    public function view(User $user, Loan $loan): bool
    {
        return $this->isStaff($user);
    }

    public function create(User $user): bool
    {
        return $this->isStaff($user);
    }

    /** A returned loan is history and can no longer be edited. */
    public function update(User $user, Loan $loan): bool
    {
        return $this->isStaff($user) && ! $loan->isReturned();
    }

    /** Checking a book back in. Only possible while the loan is still active. */
    public function markReturned(User $user, Loan $loan): bool
    {
        return $this->isStaff($user) && ! $loan->isReturned();
    }

    /** Deleting loan records is restricted to admins. */
    public function delete(User $user, Loan $loan): bool
    {
        return $user->role === 'admin';
    }
}
