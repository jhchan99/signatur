<?php

namespace Database\Factories;

use App\Models\Book;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Book>
 */
class BookFactory extends Factory
{
    protected $model = Book::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $suffix = fake()->unique()->numerify('#######');

        return [
            'open_library_id' => '/works/OL'.$suffix.'W',
            'title' => fake()->sentence(3),
            'author' => fake()->name(),
            'cover_url' => 'https://covers.openlibrary.org/b/id/'.fake()->numberBetween(1000000, 9999999).'-M.jpg',
            'publish_year' => fake()->year(),
            'description' => fake()->paragraph(),
            'subjects' => null,
        ];
    }
}
