<?php

use App\Models\Author;
use Database\Seeders\AuthorSeeder;

test('author seeder loads authors from csv with normalized open library ids', function () {
    $this->seed(AuthorSeeder::class);

    expect(Author::query()->count())->toBe(8);

    $leGuin = Author::query()->where('name', 'Ursula K. Le Guin')->first();
    expect($leGuin)->not->toBeNull()
        ->and($leGuin->open_library_id)->toBe('/authors/OL23919A')
        ->and($leGuin->bio)->toContain('speculative fiction');

    $orwell = Author::query()->where('name', 'George Orwell')->first();
    expect($orwell)->not->toBeNull()
        ->and($orwell->open_library_id)->toBe('/authors/OL34184A');
});
