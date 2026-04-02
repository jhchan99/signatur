<?php

namespace Database\Factories;

use App\Models\ReadingLog;
use App\Models\User;
use App\Models\Work;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ReadingLog>
 */
class ReadingLogFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'work_id' => Work::factory(),
            'status' => fake()->randomElement(['want_to_read', 'currently_reading', 'finished']),
            'rating' => fake()->optional()->randomFloat(1, 1, 5),
            'review_text' => fake()->optional()->paragraph(),
            'is_spoiler' => fake()->boolean(20),
            'is_private' => fake()->boolean(15),
            'date_started' => fake()->optional()->date(),
            'date_finished' => fake()->optional()->date(),
        ];
    }

    public function withoutReviewText(): static
    {
        return $this->state(fn (): array => [
            'review_text' => null,
        ]);
    }
}
