<?php

use App\Models\Author;
use App\Models\User;
use App\Models\Work;

use function Pest\Laravel\get;

test('global search without query shows the prompt state', function () {
    get(route('search.index'))
        ->assertSuccessful()
        ->assertSee(__('Enter a title, author name, or keyword to search the catalog.'), escape: false);
});

test('global search shows empty state when nothing matches', function () {
    Work::factory()->create(['title' => 'Only Moon']);

    get(route('search.index', ['q' => 'zzunique-no-match-xyz']))
        ->assertSuccessful()
        ->assertSee(__('No books or authors match that search yet. Try a different term.'), escape: false);
});

test('global search returns matching books and authors in grouped sections', function () {
    $author = Author::factory()->create(['name' => 'Pat AuthorSearch']);
    $work = Work::factory()->create(['title' => 'AuthorSearch Book Title']);
    $work->authors()->attach($author, ['position' => 1, 'role' => null]);

    get(route('search.index', ['q' => 'AuthorSearch']))
        ->assertSuccessful()
        ->assertSee(__('Books'), escape: false)
        ->assertSee(__('Authors'), escape: false)
        ->assertSee('AuthorSearch Book Title')
        ->assertSee('Pat AuthorSearch')
        ->assertSee(route('authors.show', $author), escape: false)
        ->assertSee(route('books.show', $work), escape: false);
});

test('global search matches authors by alternate names', function () {
    $author = Author::factory()->create([
        'name' => 'Primary Legal Name',
        'alternate_names' => ['Pen Name UniqueAlt'],
    ]);

    get(route('search.index', ['q' => 'UniqueAlt']))
        ->assertSuccessful()
        ->assertSee('Primary Legal Name')
        ->assertSee(__('Also known as'), escape: false)
        ->assertSee('Pen Name UniqueAlt')
        ->assertSee(route('authors.show', $author), escape: false);
});

test('author show page lists linked works', function () {
    $author = Author::factory()->create(['name' => 'Show Page Writer', 'bio' => 'A short bio for testing.']);
    $work = Work::factory()->create(['title' => 'Linked Work Alpha']);
    $author->works()->attach($work, ['position' => 1, 'role' => null]);

    get(route('authors.show', $author))
        ->assertSuccessful()
        ->assertSee('Show Page Writer')
        ->assertSee('A short bio for testing.')
        ->assertSee('Linked Work Alpha')
        ->assertSee(route('books.show', $work), escape: false);
});

test('authors index lists authors', function () {
    Author::factory()->create(['name' => 'ZZZ List Author']);

    get(route('authors.index'))
        ->assertSuccessful()
        ->assertSee('ZZZ List Author');
});

test('the books index header includes the global catalog search form', function () {
    get(route('books.index'))
        ->assertSuccessful()
        ->assertSee(__('Search books and authors'), escape: false)
        ->assertSee(route('search.index'), escape: false);
});

test('the authenticated app shell includes the global catalog search form', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('profile.edit'))
        ->assertSuccessful()
        ->assertSee(__('Search books and authors'), escape: false)
        ->assertSee(route('search.index'), escape: false);
});
