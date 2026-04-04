<?php

use App\Services\OpenLibrary\OpenLibraryBookNormalizer;

test('normalizes key fields from a work search document', function () {
    $doc = [
        'key' => '/works/OL5738148W',
        'cover_i' => 14_658_160,
        'first_publish_year' => 2001,
        'author_key' => ['OL1394865A'],
        'author_name' => ['Brandon Sanderson'],
    ];

    expect(OpenLibraryBookNormalizer::workKeyFromSearchDoc($doc))->toBe('/works/OL5738148W')
        ->and(OpenLibraryBookNormalizer::firstCoverIdFromSearchDoc($doc))->toBe(14_658_160)
        ->and(OpenLibraryBookNormalizer::firstPublishYearFromSearchDoc($doc))->toBe(2001)
        ->and(OpenLibraryBookNormalizer::firstAuthorKeyFromSearchDoc($doc))->toBe('/authors/OL1394865A')
        ->and(OpenLibraryBookNormalizer::firstAuthorNameFromSearchDoc($doc))->toBe('Brandon Sanderson');
});

test('flags obvious non-book work search titles', function () {
    expect(OpenLibraryBookNormalizer::searchDocLooksLikeNonBook([
        'title' => 'Mistborn Saga Collection Box Set',
    ]))->toBeTrue()
        ->and(OpenLibraryBookNormalizer::searchDocLooksLikeNonBook([
            'title' => 'The Final Empire',
        ]))->toBeFalse();
});

test('normalizes author search documents', function () {
    $doc = [
        'key' => 'OL1394865A',
        'name' => 'Brandon Sanderson',
        'work_count' => 184,
    ];

    expect(OpenLibraryBookNormalizer::authorKeyFromAuthorSearchDoc($doc))->toBe('/authors/OL1394865A')
        ->and(OpenLibraryBookNormalizer::authorNameFromAuthorSearchDoc($doc))->toBe('Brandon Sanderson')
        ->and(OpenLibraryBookNormalizer::authorWorkCountFromAuthorSearchDoc($doc))->toBe(184);
});
