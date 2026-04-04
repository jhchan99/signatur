<?php

use App\Services\OpenLibrary\OpenLibraryService;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;

beforeEach(function () {
    config()->set('services.openlibrary.base_url', 'https://openlibrary.org');
    config()->set('services.openlibrary.timeout', 5);
    config()->set('services.openlibrary.connect_timeout', 0);
    config()->set('services.openlibrary.user_agent', 'Signatr (jhchan99@gmail.com)');
});

test('search endpoints degrade to empty results when Open Library transport fails', function () {
    Http::fake(function () {
        throw new ConnectionException('Simulated transport failure');
    });

    $service = new OpenLibraryService;

    expect($service->searchDocuments('t a'))->toHaveCount(0);
});

test('getWork and getAuthor rethrow connection failures for retryable queue work', function () {
    Http::fake(function () {
        throw new ConnectionException('Simulated transport failure');
    });

    $service = new OpenLibraryService;

    expect(fn () => $service->getWork('/works/OL12345W'))
        ->toThrow(ConnectionException::class);

    Http::fake(function () {
        throw new ConnectionException('Simulated transport failure');
    });

    expect(fn () => $service->getAuthor('/authors/OL1A'))
        ->toThrow(ConnectionException::class);
});

test('getWork returns empty array when HTTP response fails', function () {
    Http::fake([
        'https://openlibrary.org/*' => Http::response(['error' => 'gone'], 500),
    ]);

    $service = new OpenLibraryService;

    expect($service->getWork('/works/OL12345W'))->toBe([]);
});

test('requests include configured open library user agent', function () {
    Http::fake([
        'https://openlibrary.org/search.json*' => Http::response(['docs' => []], 200),
    ]);

    $service = new OpenLibraryService;
    $service->searchDocuments('Mistborn');

    Http::assertSent(function (Request $request): bool {
        return $request->hasHeader('User-Agent', 'Signatr (jhchan99@gmail.com)');
    });
});

test('work search uses q parameter only', function () {
    Http::fake([
        'https://openlibrary.org/search.json*' => Http::response(['docs' => []], 200),
    ]);

    $service = new OpenLibraryService;
    $service->searchDocuments('The Final Empire Brandon Sanderson');

    Http::assertSent(function (Request $request): bool {
        $query = $request->data();

        return array_key_exists('q', $query)
            && ! array_key_exists('title', $query)
            && ! array_key_exists('author', $query);
    });
});
