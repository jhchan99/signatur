<?php

use App\Services\Books\GoodbooksBootstrapService;

test('goodbooks synthetic author id is stable until open library enrichment', function () {
    $svc = new GoodbooksBootstrapService;

    expect($svc->syntheticAuthorOpenLibraryId('  Jane Doe '))->toBe('/authors/goodbooks/'.hash('sha1', 'jane doe'));
});

test('parseAuthorNames splits comma-separated author lists', function () {
    $svc = new GoodbooksBootstrapService;

    expect($svc->parseAuthorNames('J.K. Rowling, Mary GrandPré'))->toBe(['J.K. Rowling', 'Mary GrandPré'])
        ->and($svc->parseAuthorNames('Solo Author'))->toBe(['Solo Author']);
});

test('dedupeAuthorNamesPreservingOrder drops repeat authors for author_works pk', function () {
    $svc = new GoodbooksBootstrapService;

    expect($svc->dedupeAuthorNamesPreservingOrder(['A', 'A', 'B']))->toBe(['A', 'B'])
        ->and($svc->dedupeAuthorNamesPreservingOrder(['A', 'B', 'A']))->toBe(['A', 'B'])
        ->and($svc->dedupeAuthorNamesPreservingOrder(['  A  ', 'A']))->toBe(['  A  ']);
});
