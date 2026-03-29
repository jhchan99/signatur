<?php

use App\Models\Book;
use App\Models\BookFeaturedEntry;
use Illuminate\Support\Facades\Cache;

test('the landing page can be rendered', function () {
    $response = $this->get(route('home'));

    $response
        ->assertSuccessful()
        ->assertSee('Track what you read')
        ->assertSee('Featured books')
        ->assertSee('What Signatr does')
        ->assertSee('Project Hail Mary');
});

test('the landing page shows imported featured books when present', function () {
    Cache::forget('home.featured_books');

    $book = Book::factory()->create([
        'open_library_id' => '/works/OLUNIT123W',
        'title' => 'Visible Featured Title',
        'author' => 'Casey Catalog',
        'cover_url' => 'https://covers.openlibrary.org/b/id/9999999-M.jpg',
    ]);

    BookFeaturedEntry::query()->create([
        'import_batch' => '3f47ac10-58cc-4372-a567-0e02b2c3d479',
        'book_id' => $book->id,
        'position' => 1,
        'source' => 'test',
        'list_name' => 'homepage_test',
        'payload' => null,
        'imported_at' => now(),
    ]);

    $this->get(route('home'))
        ->assertSuccessful()
        ->assertSee('Visible Featured Title')
        ->assertSee('Casey Catalog');
});
