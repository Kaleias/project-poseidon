<?php

namespace Database\Factories;

use App\Models\Book;
use App\Models\Category;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Book>
 */
class BookFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $quantity = fake()->numberBetween(2, 8);

        return [
            'title' => rtrim(fake()->unique()->sentence(3), '.'),
            'author' => fake()->name(),
            'isbn' => fake()->unique()->numerify('978##########'),
            'publisher' => fake()->company(),
            'publication_year' => fake()->numberBetween(1990, 2025),
            'quantity' => $quantity,
            'available_quantity' => $quantity,
            'category_id' => Category::query()->inRandomOrder()->value('id'),
        ];
    }

    /** A book with every copy currently borrowed. */
    public function unavailable(): static
    {
        return $this->state(fn (array $attributes) => [
            'available_quantity' => 0,
        ]);
    }
}
