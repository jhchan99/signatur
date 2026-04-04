<?php

use App\Models\Author;
use App\Models\Work;
use Illuminate\Support\Facades\Http;

use function Pest\Laravel\get;

test('books index does not call Open Library when local catalog matches', function () {
    Http::preventStrayRequests();

    Work::factory()->create([
        'title' => 'Local Only Solar',
    ]);

    get(route('books.index', [
        'q' => 'Solar',
        'mode' => 'books',
    ]))
        ->assertSuccessful()
        ->assertSee('Local Only Solar');
});

test('books index shows the empty state on miss without calling Open Library', function () {
    Http::preventStrayRequests();

    get(route('books.index', [
        'q' => 'zznomatchunique',
        'mode' => 'books',
    ]))
        ->assertSuccessful()
        ->assertSee('No books match those filters yet. Try a different search or filter.', escape: false);
});

test('author mode shows the empty state on miss without calling Open Library', function () {
    Http::preventStrayRequests();

    get(route('books.index', [
        'q' => 'Jane Q Author',
        'mode' => 'author',
    ]))
        ->assertSuccessful()
        ->assertSee('No books match those filters yet. Try a different search or filter.', escape: false);
});

test('author mode can match related authors already in the catalog', function () {
    Http::preventStrayRequests();

    $work = Work::factory()->create([
        'title' => 'Catalog Author Hit',
    ]);
    $work->authors()->attach(
        Author::factory()->create(['name' => 'Stored Relation Author']),
        ['position' => 1, 'role' => null],
    );

    get(route('books.index', [
        'q' => 'Relation Author',
        'mode' => 'author',
    ]))
        ->assertSuccessful()
        ->assertSee('Catalog Author Hit');
});

test('author mode does not match secondary authors', function () {
    Http::preventStrayRequests();

    $work = Work::factory()->create([
        'title' => 'Primary Filtered Book',
    ]);
    $work->authors()->attach(
        Author::factory()->create(['name' => 'Primary Catalog Author']),
        ['position' => 1, 'role' => null],
    );
    $work->authors()->attach(
        Author::factory()->create(['name' => 'Secondary Match Name']),
        ['position' => 2, 'role' => null],
    );

    get(route('books.index', [
        'q' => 'Secondary Match',
        'mode' => 'author',
    ]))
        ->assertSuccessful()
        ->assertSee('No books match those filters yet. Try a different search or filter.', escape: false)
        ->assertDontSee('Primary Filtered Book');
});
