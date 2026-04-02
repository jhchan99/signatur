<?php

namespace Database\Factories;

use App\Models\Author;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Author>
 */
class AuthorFactory extends Factory
{
    protected $model = Author::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $suffix = fake()->unique()->numerify('#######');

        return [
            'open_library_id' => '/authors/OL'.$suffix.'A',
            'name' => fake()->name(),
            'bio' => fake()->sentence(),
        ];
    }
}
