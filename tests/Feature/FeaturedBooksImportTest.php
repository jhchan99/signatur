<?php

use App\Models\Author;
use App\Models\BookFeaturedEntry;
use App\Models\Work;
use App\Services\Books\FeaturedBooksImporter;
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
            'subjects' => ['Fiction', 'Science fiction'],
            'authors' => [
                ['author' => ['key' => '/authors/OL1A']],
            ],
        ], 200),
        'https://openlibrary.org/authors/*.json' => Http::response([
            'name' => 'Taylor Reader',
        ], 200),
    ]);

    app(FeaturedBooksImporter::class)->import();

    $work = Work::query()->where('open_library_key', '/works/OL27448W')->first();
    expect($work)->not->toBeNull()
        ->and($work->title)->toBe('Imported Work')
        ->and($work->cover_url)->toBe('https://covers.openlibrary.org/b/id/1234567-M.jpg')
        ->and($work->subjects)->toBe(['Fiction', 'Science fiction']);

    $author = Author::query()->where('open_library_id', '/authors/OL1A')->first();
    expect($author)->not->toBeNull()
        ->and($author->name)->toBe('Taylor Reader')
        ->and($work->authors()->first()?->is($author))->toBeTrue();

    expect(BookFeaturedEntry::query()->count())->toBe(1)
        ->and(BookFeaturedEntry::query()->first()->work_id)->toBe($work->id);
});
