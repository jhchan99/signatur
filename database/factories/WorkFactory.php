<?php

namespace Database\Factories;

use App\Models\Author;
use App\Models\Work;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Work>
 */
class WorkFactory extends Factory
{
    protected $model = Work::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $suffix = fake()->unique()->numerify('#######');

        return [
            'open_library_key' => '/works/OL'.$suffix.'W',
            'title' => fake()->sentence(3),
            'subtitle' => null,
            'cover_id' => fake()->numberBetween(1000000, 9999999),
            'first_publish_year' => fake()->year(),
            'description' => fake()->paragraph(),
            'subjects' => null,
        ];
    }

    public function withAuthors(int $count = 1): static
    {
        return $this->afterCreating(function (Work $work) use ($count): void {
            $authors = Author::factory()->count($count)->create();

            $work->authors()->sync(
                $authors
                    ->values()
                    ->mapWithKeys(fn (Author $author, int $index): array => [
                        $author->getKey() => ['position' => $index + 1, 'role' => null],
                    ])
                    ->all(),
            );
        });
    }
}
