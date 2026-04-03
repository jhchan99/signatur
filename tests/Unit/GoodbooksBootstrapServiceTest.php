<?php

use App\Services\Books\GoodbooksBootstrapService;

test('goodbooks author dedupe key is stable and is not a faux Open Library id', function () {
    $svc = new GoodbooksBootstrapService;

    expect($svc->goodbooksAuthorDedupeKey('  Jane Doe '))->toBe(hash('sha1', 'jane doe'));
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
