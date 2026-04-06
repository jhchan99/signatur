<?php

use App\Jobs\EnrichWorkJob;
use App\Models\Author;
use App\Models\Work;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Str;

use function Pest\Laravel\artisan;

test('sync batch command enriches a work by id', function () {
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

    Http::fake([
        'https://openlibrary.org/search.json*' => Http::response([
            'docs' => [
                [
                    'key' => '/works/OL5738148W',
                    'title' => 'The Final Empire',
                    'author_key' => ['OL1394865A'],
                    'author_name' => ['Brandon Sanderson'],
                    'cover_i' => 14_658_160,
                    'first_publish_year' => 2006,
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

    $path = writeEnrichmentBatchFile([
        [
            'id' => $work->getKey(),
            'title' => $work->title,
        ],
    ]);

    artisan('works:enrich-batch', [
        'file' => $path,
        '--sync' => true,
        '--delay' => 0,
    ])
        ->expectsOutputToContain("Enriched work #{$work->getKey()} ({$work->title}).")
        ->assertSuccessful();

    $work->refresh();
    $author->refresh();

    expect($work->open_library_key)->toBe('/works/OL5738148W')
        ->and($work->cover_id)->toBe(14_658_160)
        ->and($work->first_publish_year)->toBe(2006)
        ->and($work->open_library_match_source)->toBe('search.json')
        ->and($work->open_library_search_doc)->toBeArray()
        ->and($work->open_library_search_doc['title'] ?? null)->toBe('The Final Empire')
        ->and($work->open_library_enriched_at)->not->toBeNull()
        ->and($author->open_library_id)->toBe('/authors/OL1394865A')
        ->and($author->open_library_author_search_doc)->toBeArray()
        ->and($author->open_library_author_search_doc['name'] ?? null)->toBe('Brandon Sanderson')
        ->and($author->open_library_author_enriched_at)->not->toBeNull();
});

test('sync batch command enriches a work by title when id is missing', function () {
    config()->set('services.openlibrary.base_url', 'https://openlibrary.org');

    $work = Work::factory()->create([
        'title' => 'Station Eleven',
        'open_library_key' => null,
        'cover_id' => null,
        'first_publish_year' => null,
        'open_library_search_doc' => null,
        'open_library_match_source' => null,
        'open_library_enriched_at' => null,
    ]);

    $author = Author::factory()->create([
        'name' => 'Emily St. John Mandel',
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
                    'key' => '/works/OL18007545W',
                    'title' => 'Station Eleven',
                    'author_key' => ['OL7349774A'],
                    'author_name' => ['Emily St. John Mandel'],
                    'cover_i' => 8_234_567,
                    'first_publish_year' => 2014,
                ],
            ],
        ], 200),
        'https://openlibrary.org/search/authors.json*' => Http::response([
            'docs' => [
                [
                    'key' => 'OL7349774A',
                    'name' => 'Emily St. John Mandel',
                    'work_count' => 24,
                ],
            ],
        ], 200),
    ]);

    $path = writeEnrichmentBatchFile([
        [
            'title' => 'STATION ELEVEN',
        ],
    ]);

    artisan('works:enrich-batch', [
        'file' => $path,
        '--sync' => true,
        '--delay' => 0,
    ])->assertSuccessful();

    $work->refresh();
    $author->refresh();

    expect($work->open_library_key)->toBe('/works/OL18007545W')
        ->and($work->cover_id)->toBe(8_234_567)
        ->and($work->first_publish_year)->toBe(2014)
        ->and($author->open_library_id)->toBe('/authors/OL7349774A');
});

test('batch command queues jobs with incremental delays', function () {
    Queue::fake();

    $firstWork = Work::factory()->create(['title' => 'Beloved']);
    $secondWork = Work::factory()->create(['title' => 'The Road']);

    $path = writeEnrichmentBatchFile([
        [
            'id' => $firstWork->getKey(),
            'title' => $firstWork->title,
        ],
        [
            'id' => $secondWork->getKey(),
            'title' => $secondWork->title,
        ],
    ]);

    artisan('works:enrich-batch', [
        'file' => $path,
        '--delay' => 30,
    ])->assertSuccessful();

    Queue::assertPushed(EnrichWorkJob::class, 2);

    expect(
        Queue::pushed(EnrichWorkJob::class)
            ->map(fn (EnrichWorkJob $job): array => [
                'work_id' => $job->workId,
                'delay' => $job->delay,
            ])
            ->values()
            ->all()
    )->toBe([
        [
            'work_id' => $firstWork->getKey(),
            'delay' => 0,
        ],
        [
            'work_id' => $secondWork->getKey(),
            'delay' => 30,
        ],
    ]);
});

test('batch command warns when an entry cannot be resolved', function () {
    Queue::fake();

    $path = writeEnrichmentBatchFile([
        [
            'title' => 'A Book That Does Not Exist Locally',
        ],
    ]);

    artisan('works:enrich-batch', [
        'file' => $path,
        '--delay' => 0,
    ])
        ->expectsOutputToContain('Skipped entry #1 [A Book That Does Not Exist Locally] (work could not be resolved).')
        ->assertSuccessful();

    Queue::assertNothingPushed();
});

/**
 * @param  list<array<string, mixed>>  $entries
 */
function writeEnrichmentBatchFile(array $entries): string
{
    $directory = storage_path('framework/testing');

    File::ensureDirectoryExists($directory);

    $path = $directory.'/enrich-work-batch-'.Str::uuid().'.json';

    file_put_contents($path, json_encode($entries, JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR));

    return $path;
}
