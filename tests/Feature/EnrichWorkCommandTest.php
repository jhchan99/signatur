<?php

use App\Models\Author;
use App\Models\Work;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;

use function Pest\Laravel\artisan;

test('sync command enriches a single work and keeps existing relationships', function () {
    config()->set('services.openlibrary.base_url', 'https://openlibrary.org');

    $work = Work::factory()->create([
        'title' => 'The Final Empire',
        'open_library_key' => null,
        'cover_id' => null,
        'first_publish_year' => null,
        'open_library_search_doc' => null,
        'open_library_match_source' => null,
        'open_library_enriched_at' => null,
    ]);

    $author = Author::factory()->create([
        'name' => 'Brandon Sanderson',
        'open_library_id' => null,
        'open_library_author_search_doc' => null,
        'open_library_author_enriched_at' => null,
    ]);

    $work->authors()->sync([
        $author->getKey() => ['position' => 1, 'role' => null],
    ]);

    $beforePivots = DB::table('author_works')
        ->where('work_id', $work->getKey())
        ->orderBy('position')
        ->get()
        ->map(fn (object $row): array => [
            'author_id' => (int) $row->author_id,
            'position' => (int) $row->position,
            'role' => $row->role,
        ])
        ->all();

    Http::fake([
        'https://openlibrary.org/search.json*' => Http::response([
            'docs' => [
                [
                    'key' => '/works/OL5738148W',
                    'title' => 'The Final Empire',
                    'author_key' => ['OL1394865A'],
                    'author_name' => ['Brandon Sanderson'],
                    'cover_i' => 14_658_160,
                    'first_publish_year' => 2001,
                    'edition_count' => 44,
                ],
                [
                    'key' => '/works/OL999999W',
                    'title' => 'Mistborn Saga Collection Box Set',
                    'author_key' => ['OL1394865A'],
                    'author_name' => ['Brandon Sanderson'],
                    'cover_i' => 111,
                    'first_publish_year' => 2015,
                    'edition_count' => 1,
                ],
            ],
        ], 200),
        'https://openlibrary.org/search/authors.json*' => Http::response([
            'docs' => [
                [
                    'key' => 'OL1394865A',
                    'name' => 'Brandon Sanderson',
                    'work_count' => 184,
                ],
            ],
        ], 200),
    ]);

    artisan('works:enrich-work', [
        'work' => $work->getKey(),
        '--sync' => true,
    ])->assertSuccessful();

    $work->refresh();
    $author->refresh();

    expect($work->open_library_key)->toBe('/works/OL5738148W')
        ->and($work->cover_id)->toBe(14_658_160)
        ->and($work->first_publish_year)->toBe(2001)
        ->and($work->open_library_match_source)->toBe('search.json')
        ->and($work->open_library_search_doc)->toBeArray()
        ->and($work->open_library_search_doc['title'] ?? null)->toBe('The Final Empire')
        ->and($work->open_library_enriched_at)->not->toBeNull()
        ->and($author->open_library_id)->toBe('/authors/OL1394865A')
        ->and($author->open_library_author_search_doc)->toBeArray()
        ->and($author->open_library_author_search_doc['name'] ?? null)->toBe('Brandon Sanderson')
        ->and($author->open_library_author_enriched_at)->not->toBeNull();

    $afterPivots = DB::table('author_works')
        ->where('work_id', $work->getKey())
        ->orderBy('position')
        ->get()
        ->map(fn (object $row): array => [
            'author_id' => (int) $row->author_id,
            'position' => (int) $row->position,
            'role' => $row->role,
        ])
        ->all();

    expect($afterPivots)->toBe($beforePivots);
});

test('sync command skips when no confident work match is found', function () {
    config()->set('services.openlibrary.base_url', 'https://openlibrary.org');

    $work = Work::factory()->create([
        'title' => 'The Final Empire',
        'open_library_key' => null,
        'cover_id' => null,
    ]);

    $author = Author::factory()->create([
        'name' => 'Brandon Sanderson',
        'open_library_id' => null,
        'open_library_author_search_doc' => null,
        'open_library_author_enriched_at' => null,
    ]);

    $work->authors()->sync([
        $author->getKey() => ['position' => 1, 'role' => null],
    ]);

    Http::fake([
        'https://openlibrary.org/search.json*' => Http::response([
            'docs' => [
                [
                    'title' => 'Mistborn Saga Collection',
                    'author_key' => ['OL1394865A'],
                    'author_name' => ['Brandon Sanderson'],
                    'cover_i' => 111,
                    'first_publish_year' => 2015,
                ],
                [
                    'key' => '/works/OL999999W',
                    'title' => 'Mistborn Saga Collection',
                    'author_key' => ['OL1394865A'],
                    'author_name' => ['Brandon Sanderson'],
                    'cover_i' => 111,
                    'first_publish_year' => 2015,
                ],
            ],
        ], 200),
    ]);

    artisan('works:enrich-work', [
        'work' => $work->getKey(),
        '--sync' => true,
    ])->assertSuccessful();

    $work->refresh();
    $author->refresh();

    expect($work->open_library_key)->toBeNull()
        ->and($work->cover_id)->toBeNull()
        ->and($work->open_library_search_doc)->toBeNull()
        ->and($author->open_library_id)->toBeNull()
        ->and($author->open_library_author_search_doc)->toBeNull()
        ->and($author->open_library_author_enriched_at)->toBeNull();
});

test('sync command uses a q query for work search', function () {
    config()->set('services.openlibrary.base_url', 'https://openlibrary.org');

    $work = Work::factory()->create([
        'title' => 'Harry Potter and the Prisoner of Azkaban (Harry Potter, #3)',
        'open_library_key' => null,
        'cover_id' => null,
    ]);

    $author = Author::factory()->create([
        'name' => 'J.K. Rowling',
        'open_library_id' => null,
    ]);

    $work->authors()->sync([
        $author->getKey() => ['position' => 1, 'role' => null],
    ]);

    Http::fake(function (Request $request) {
        $url = $request->url();

        if (str_contains($url, 'search/authors.json')) {
            return Http::response([
                'docs' => [
                    [
                        'key' => 'OL23919A',
                        'name' => 'J. K. Rowling',
                        'work_count' => 400,
                    ],
                ],
            ], 200);
        }

        if (str_contains($url, 'search.json') && str_contains($url, 'q=')) {
            return Http::response([
                'docs' => [
                    [
                        'key' => '/works/OL82563W',
                        'title' => 'Harry Potter and the Prisoner of Azkaban',
                        'author_key' => ['OL23919A'],
                        'author_name' => ['J. K. Rowling'],
                        'cover_i' => 8_233_969,
                        'first_publish_year' => 1999,
                    ],
                ],
            ], 200);
        }

        return Http::response(['docs' => []], 200);
    });

    artisan('works:enrich-work', [
        'work' => $work->getKey(),
        '--sync' => true,
    ])->assertSuccessful();

    $work->refresh();
    $author->refresh();

    expect($work->open_library_key)->toBe('/works/OL82563W')
        ->and($work->cover_id)->toBe(8_233_969)
        ->and($author->open_library_id)->toBe('/authors/OL23919A');

    Http::assertSent(function (Request $request): bool {
        if (! str_contains($request->url(), 'search.json')) {
            return false;
        }

        $query = $request->data();

        return isset($query['q'])
            && is_string($query['q'])
            && str_contains($query['q'], 'Harry Potter and the Prisoner of Azkaban (Harry Potter, #3)')
            && str_contains($query['q'], 'J.K. Rowling')
            && ! isset($query['title'])
            && ! isset($query['author']);
    });
});
