<?php

namespace App\Policies;

use App\Models\Book;
use App\Models\User;

class BookPolicy
{
    /** Roles that run the library desk and may change the catalogue. */
    private function isStaff(User $user): bool
    {
        return in_array($user->role, ['admin', 'librarian'], true);
    }

    /** Everyone who is logged in can browse the catalogue (read-only for students). */
    public function viewAny(User $user): bool
    {
        return in_array($user->role, ['admin', 'librarian', 'student'], true);
    }

    public function view(User $user, Book $book): bool
    {
        return $this->viewAny($user);
    }

    public function create(User $user): bool
    {
        return $this->isStaff($user);
    }

    /** Also covers the borrow / return stock buttons. */
    public function update(User $user, Book $book): bool
    {
        return $this->isStaff($user);
    }

    /** Removing a book from the catalogue is admin-only. */
    public function delete(User $user, Book $book): bool
    {
        return $user->role === 'admin';
    }
}
