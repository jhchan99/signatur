<?php

namespace Database\Factories;

use App\Models\Book;
use App\Models\ReadingLog;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ReadingLog>
 */
class ReadingLogFactory extends Factory
{
    protected $model = ReadingLog::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'book_id' => Book::factory(),
            'status' => 'finished',
            'rating' => fake()->randomElement([3.0, 3.5, 4.0, 4.5, 5.0]),
            'review_text' => fake()->paragraph(),
            'is_spoiler' => false,
            'is_private' => false,
            'date_started' => null,
            'date_finished' => null,
        ];
    }

    public function publicReview(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_private' => false,
            'review_text' => fake()->paragraph(),
        ]);
    }

    public function privateLog(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_private' => true,
            'review_text' => fake()->paragraph(),
        ]);
    }

    public function withoutReviewText(): static
    {
        return $this->state(fn (array $attributes) => [
            'review_text' => null,
        ]);
    }
}
