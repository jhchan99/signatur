<?php

use App\Models\Author;
use App\Models\Book;
use App\Services\Books\BookDiscoveryService;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\RateLimiter;

beforeEach(function () {
    config()->set('services.openlibrary.base_url', 'https://openlibrary.org');
    config()->set('services.openlibrary.timeout', 5);
});

test('books index does not call Open Library when local catalog matches', function () {
    Http::preventStrayRequests();

    Book::factory()->create([
        'title' => 'Local Only Solar',
    ]);

    $this->get(route('books.index', [
        'q' => 'Solar',
        'mode' => 'books',
    ]))
        ->assertSuccessful()
        ->assertSee('Local Only Solar');
});

test('books index falls back to Open Library and persists work on miss', function () {
    Http::fake([
        'https://openlibrary.org/search.json*' => Http::response([
            'docs' => [
                [
                    'key' => '/works/OLDISCOVERY1W',
                    'title' => 'Discovery Remote Title',
                    'author_name' => 'Remote Writer',
                    'first_publish_year' => 2019,
                    'cover_i' => 9_999_001,
                ],
            ],
        ], 200),
        'https://openlibrary.org/works/*.json' => Http::response([
            'title' => 'Discovery Remote Title',
            'first_publish_year' => 2019,
            'covers' => [9_999_001],
            'description' => ['type' => '/type/text', 'value' => 'Synopsis.'],
            'subjects' => ['Fiction'],
            'authors' => [
                ['author' => ['key' => '/authors/OLXA']],
            ],
        ], 200),
        'https://openlibrary.org/authors/*.json' => Http::response([
            'name' => 'Remote Writer',
        ], 200),
    ]);

    $this->get(route('books.index', [
        'q' => 'zznomatchunique',
        'mode' => 'books',
    ]))
        ->assertSuccessful()
        ->assertSee('Discovery Remote Title')
        ->assertSee('Remote Writer');

    $book = Book::query()->where('open_library_id', '/works/OLDISCOVERY1W')->first();
    expect($book)->not->toBeNull()
        ->and($book->title)->toBe('Discovery Remote Title');

    $author = Author::query()->where('open_library_id', '/authors/OLXA')->first();
    expect($author)->not->toBeNull()
        ->and($author->name)->toBe('Remote Writer')
        ->and($book->authors()->first()?->is($author))->toBeTrue();
});

test('author mode falls back through authors search then work search', function () {
    Http::fake(function (Request $request) {
        $url = $request->url();

        if (str_contains($url, 'search/authors.json')) {
            return Http::response([
                'docs' => [
                    [
                        'name' => 'Jane Q Author',
                        'key' => '/authors/OL123A',
                    ],
                ],
            ], 200);
        }

        if (str_contains($url, 'search.json')) {
            $query = [];
            parse_str((string) parse_url($url, PHP_URL_QUERY), $query);

            if (($query['author'] ?? null) === 'Jane Q Author') {
                return Http::response([
                    'docs' => [
                        [
                            'key' => '/works/OLAUTHOR1W',
                            'title' => 'Author Mode Book',
                            'author_name' => ['Jane Q Author'],
                            'first_publish_year' => 2021,
                        ],
                    ],
                ], 200);
            }

            return Http::response(['docs' => []], 200);
        }

        if (str_contains($url, '/works/') && str_contains($url, '.json')) {
            return Http::response([
                'title' => 'Author Mode Book',
                'first_publish_year' => 2021,
                'authors' => [
                    ['author' => ['key' => '/authors/OL123A']],
                ],
            ], 200);
        }

        if (str_contains($url, '/authors/') && str_contains($url, '.json')) {
            return Http::response(['name' => 'Jane Q Author'], 200);
        }

        return Http::response([], 404);
    });

    $this->get(route('books.index', [
        'q' => 'Jane Q Author',
        'mode' => 'author',
    ]))
        ->assertSuccessful()
        ->assertSee('Author Mode Book');
});

test('author mode can match related authors already in the catalog', function () {
    Http::preventStrayRequests();

    $book = Book::factory()->create([
        'title' => 'Catalog Author Hit',
    ]);
    $book->authors()->attach(
        Author::factory()->create(['name' => 'Stored Relation Author']),
        ['position' => 1],
    );

    $this->get(route('books.index', [
        'q' => 'Relation Author',
        'mode' => 'author',
    ]))
        ->assertSuccessful()
        ->assertSee('Catalog Author Hit');
});

test('open library fallback excludes works already in catalog by open_library_id', function () {
    Book::factory()->create([
        'open_library_id' => '/works/OLDEDUPE1W',
        'title' => 'Already Here',
    ]);

    Http::fake([
        'https://openlibrary.org/search.json*' => Http::response([
            'docs' => [
                [
                    'key' => '/works/OLDEDUPE1W',
                    'title' => 'Already Here OL',
                    'author_name' => 'X',
                ],
            ],
        ], 200),
    ]);

    $this->get(route('books.index', [
        'q' => 'zzuniquededupequery',
        'mode' => 'books',
    ]))
        ->assertSuccessful()
        ->assertDontSee('Already Here OL');
});

test('open library fallback is rate limited after max attempts', function () {
    Http::fake([
        'https://openlibrary.org/search.json*' => Http::response(['docs' => []], 200),
    ]);

    $rateKey = 'openlibrary-fallback:guest:'.sha1('127.0.0.1');
    RateLimiter::clear($rateKey);

    for ($i = 0; $i < BookDiscoveryService::FALLBACK_RATE_LIMIT_MAX; $i++) {
        $this->get(route('books.index', [
            'q' => "rate-test-{$i}",
            'mode' => 'books',
        ]))->assertSuccessful();
    }

    Http::preventStrayRequests();

    $this->get(route('books.index', [
        'q' => 'rate-test-final',
        'mode' => 'books',
    ]))
        ->assertSuccessful()
        ->assertSee(__('Too many catalog lookups from Open Library. Please wait a moment and try again.'), escape: false);
});
