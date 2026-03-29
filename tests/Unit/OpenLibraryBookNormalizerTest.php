<?php

use App\Services\OpenLibrary\OpenLibraryBookNormalizer;

it('builds a cover url from a work covers id', function () {
    expect(OpenLibraryBookNormalizer::coverUrlFromWork([
        'covers' => [9_255_560],
    ]))->toBe('https://covers.openlibrary.org/b/id/9255560-M.jpg');
});

it('builds a cover url from cover edition olid', function () {
    expect(OpenLibraryBookNormalizer::coverUrlFromWork([
        'cover_edition_key' => '/books/OL7440863M',
    ]))->toBe('https://covers.openlibrary.org/b/olid/OL7440863M-M.jpg');
});

it('normalizes description strings and value objects', function () {
    expect(OpenLibraryBookNormalizer::description(' Hello '))->toBe('Hello');
    expect(OpenLibraryBookNormalizer::description(['type' => 'text', 'value' => ' Hi ']))->toBe('Hi');
    expect(OpenLibraryBookNormalizer::description(['value' => '   ']))->toBeNull();
});

it('extracts author keys from a work payload', function () {
    $keys = OpenLibraryBookNormalizer::authorKeysFromWork([
        'authors' => [
            ['author' => ['key' => '/authors/OL34184A']],
            ['author' => ['key' => '/authors/OL999A']],
        ],
    ]);

    expect($keys->all())->toBe([
        '/authors/OL34184A',
        '/authors/OL999A',
    ]);
});
