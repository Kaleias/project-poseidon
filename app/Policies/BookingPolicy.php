<?php

namespace App\Policies;

use App\Models\Booking;
use App\Models\User;

class BookingPolicy
{
    // Admin can see everything; users only their own
    public function viewAny(User $user): bool
    {
        return true; // filtered in the query
    }

    public function view(User $user, Booking $booking): bool
    {
        return $user->is_admin || $user->id === $booking->user_id;
    }

    public function create(User $user): bool
    {
        return true; // any authenticated user
    }

    public function update(User $user, Booking $booking): bool
    {
        return $user->is_admin || $user->id === $booking->user_id;
    }

    public function delete(User $user, Booking $booking): bool
    {
        return $user->is_admin || $user->id === $booking->user_id;
    }
}