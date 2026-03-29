<?php

use App\Models\Book;
use App\Models\BookFeaturedEntry;
use App\Services\Books\FeaturedBooksImporter;
use App\Services\OpenLibrary\OpenLibraryService;
use Illuminate\Support\Facades\Http;

beforeEach(function () {
    config()->set('books.featured.seeds', [
        ['work_key' => '/works/OL27448W'],
    ]);
    config()->set('services.openlibrary.base_url', 'https://openlibrary.org');
});

test('featured books import persists catalog rows and featured entries', function () {
    Http::fake([
        'https://openlibrary.org/works/*.json' => Http::response([
            'title' => 'Imported Work',
            'first_publish_year' => 2021,
            'covers' => [1_234_567],
            'description' => ['type' => '/type/text', 'value' => 'A test synopsis.'],
            'authors' => [
                ['author' => ['key' => '/authors/OL1A']],
            ],
        ], 200),
        'https://openlibrary.org/authors/*.json' => Http::response([
            'name' => 'Taylor Reader',
        ], 200),
    ]);

    (new FeaturedBooksImporter(app(OpenLibraryService::class)))->import();

    $book = Book::query()->where('open_library_id', '/works/OL27448W')->first();
    expect($book)->not->toBeNull()
        ->and($book->title)->toBe('Imported Work')
        ->and($book->author)->toBe('Taylor Reader')
        ->and($book->cover_url)->toBe('https://covers.openlibrary.org/b/id/1234567-M.jpg');

    expect(BookFeaturedEntry::query()->count())->toBe(1)
        ->and(BookFeaturedEntry::query()->first()->book_id)->toBe($book->id);
});
