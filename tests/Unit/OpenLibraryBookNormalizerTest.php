<?php

use App\Services\OpenLibrary\OpenLibraryBookNormalizer;

it('builds a medium cover url from a work covers id', function () {
    expect(OpenLibraryBookNormalizer::coverUrlFromWork([
        'covers' => [9_255_560],
    ]))->toBe('https://covers.openlibrary.org/b/id/9255560-M.jpg');
});

it('builds a large cover url when requested', function () {
    expect(OpenLibraryBookNormalizer::coverUrlFromWork([
        'covers' => [9_255_560],
    ], 'L'))->toBe('https://covers.openlibrary.org/b/id/9255560-L.jpg');
});

it('builds a medium cover url from cover edition olid', function () {
    expect(OpenLibraryBookNormalizer::coverUrlFromWork([
        'cover_edition_key' => '/books/OL7440863M',
    ]))->toBe('https://covers.openlibrary.org/b/olid/OL7440863M-M.jpg');
});

it('upgrades a stored catalog cover url to large for hero use', function () {
    expect(OpenLibraryBookNormalizer::heroCoverUrlFromStoredCover(
        'https://covers.openlibrary.org/b/id/9255560-M.jpg',
    ))->toBe('https://covers.openlibrary.org/b/id/9255560-L.jpg');

    expect(OpenLibraryBookNormalizer::heroCoverUrlFromStoredCover(
        'https://covers.openlibrary.org/b/olid/OL7440863M-S.jpg',
    ))->toBe('https://covers.openlibrary.org/b/olid/OL7440863M-L.jpg');
});

it('does not fabricate a hero url from non open library images', function () {
    expect(OpenLibraryBookNormalizer::heroCoverUrlFromStoredCover(
        'https://images.unsplash.com/photo-123?w=1600',
    ))->toBeNull();
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

it('normalizes subjects from string and object entries', function () {
    expect(OpenLibraryBookNormalizer::subjectsFromWork([
        'subjects' => [' Literary ', 'Fiction', 'Fiction'],
    ]))->toBe(['Literary', 'Fiction']);

    expect(OpenLibraryBookNormalizer::subjectsFromWork([
        'subjects' => [
            ['name' => 'Fantasy'],
            ['subject' => 'Adventure'],
        ],
    ]))->toBe(['Fantasy', 'Adventure']);
});

it('builds cover url from a stored cover id', function () {
    expect(OpenLibraryBookNormalizer::coverUrlFromCoverId(9_255_560))
        ->toBe('https://covers.openlibrary.org/b/id/9255560-M.jpg');
    expect(OpenLibraryBookNormalizer::coverUrlFromCoverId(9_255_560, 'L'))
        ->toBe('https://covers.openlibrary.org/b/id/9255560-L.jpg');
    expect(OpenLibraryBookNormalizer::heroCoverUrlFromCoverId(9_255_560))
        ->toBe('https://covers.openlibrary.org/b/id/9255560-L.jpg');
});

it('extracts first publish year from work payload', function () {
    expect(OpenLibraryBookNormalizer::firstPublishYearFromWork([
        'first_publish_year' => 2010,
    ]))->toBe(2010);

    expect(OpenLibraryBookNormalizer::firstPublishYearFromWork([
        'first_publish_date' => 'June 1999',
    ]))->toBe(1999);
});

it('extracts language codes from edition payload', function () {
    expect(OpenLibraryBookNormalizer::languageCodesFromEdition([
        'languages' => [
            ['key' => '/languages/eng'],
            ['key' => '/languages/fre'],
        ],
    ]))->toBe(['eng', 'fre']);
});

it('caps the number of stored subjects', function () {
    $subjects = OpenLibraryBookNormalizer::subjectsFromWork([
        'subjects' => array_map(fn (int $n) => 'Topic '.$n, range(1, 20)),
    ], limit: 5);

    expect($subjects)->toHaveCount(5)
        ->and($subjects[0])->toBe('Topic 1');
});
