<?php

namespace Database\Factories;

use App\Models\Book;
use App\Models\Loan;
use App\Models\Member;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Loan>
 */
class LoanFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'book_id' => Book::factory(),
            'member_id' => Member::factory(),
            'issued_by' => null,
            'borrowed_at' => today()->subDays(3)->toDateString(),
            'due_date' => today()->addDays(11)->toDateString(),
            'returned_at' => null,
            'status' => Loan::STATUS_BORROWED,
            'notes' => null,
        ];
    }

    /** A loan that is past its due date and still not returned. */
    public function overdue(): static
    {
        return $this->state(fn (array $attributes) => [
            'borrowed_at' => today()->subDays(30)->toDateString(),
            'due_date' => today()->subDays(16)->toDateString(),
        ]);
    }

    /** A loan that has already been returned. */
    public function returned(): static
    {
        return $this->state(fn (array $attributes) => [
            'borrowed_at' => today()->subDays(20)->toDateString(),
            'due_date' => today()->subDays(6)->toDateString(),
            'returned_at' => today()->subDays(8)->toDateString(),
            'status' => Loan::STATUS_RETURNED,
        ]);
    }
}
