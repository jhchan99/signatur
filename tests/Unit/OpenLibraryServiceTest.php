<?php

use App\Services\OpenLibrary\OpenLibraryService;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;

beforeEach(function () {
    config()->set('services.openlibrary.base_url', 'https://openlibrary.org');
    config()->set('services.openlibrary.timeout', 5);
    config()->set('services.openlibrary.connect_timeout', 0);
});

test('search endpoints degrade to empty results when Open Library transport fails', function () {
    Http::fake(function () {
        throw new ConnectionException('Simulated transport failure');
    });

    $service = new OpenLibraryService;

    expect($service->search('anything'))->toHaveCount(0)
        ->and($service->searchDocuments('anything'))->toHaveCount(0)
        ->and($service->searchDocumentsByTitleAndAuthor('t', 'a'))->toHaveCount(0)
        ->and($service->searchAuthorDocuments('anyone'))->toHaveCount(0)
        ->and($service->searchDocumentsWithAuthorField('Anyone'))->toHaveCount(0);
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
