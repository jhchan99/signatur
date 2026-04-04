<?php

use App\Models\Author;
use App\Models\User;
use App\Models\Work;

use function Pest\Laravel\get;

test('global search without query shows the prompt state', function () {
    get(route('search.index'))
        ->assertSuccessful()
        ->assertSee(__('Search by title, subtitle, subject tags, or author—including alternate names.'), escape: false);
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

test('global search matches titles case-insensitively', function () {
    $work = Work::factory()->create(['title' => 'Project Hail Mary Casetest']);

    get(route('search.index', ['q' => 'project']))
        ->assertSuccessful()
        ->assertSee('Project Hail Mary Casetest')
        ->assertSee(route('books.show', $work), escape: false);
});

test('global search matches works by subject tags when the title does not contain the term', function () {
    $work = Work::factory()->create([
        'title' => 'Silent Planet Subjtest',
        'subjects' => ['Mars colonization', 'fiction'],
    ]);

    get(route('search.index', ['q' => 'mars']))
        ->assertSuccessful()
        ->assertSee('Silent Planet Subjtest')
        ->assertSee(route('books.show', $work), escape: false);
});

test('global search matches works by subtitle', function () {
    $work = Work::factory()->create([
        'title' => 'Volume One Subtitletest',
        'subtitle' => 'The Crystal Project Chronicle',
    ]);

    get(route('search.index', ['q' => 'crystal']))
        ->assertSuccessful()
        ->assertSee('Volume One Subtitletest')
        ->assertSee(route('books.show', $work), escape: false);
});

test('global search does not match works by secondary author names', function () {
    $work = Work::factory()->create(['title' => 'Primary Search Work']);
    $work->authors()->attach(
        Author::factory()->create(['name' => 'Primary Search Author']),
        ['position' => 1, 'role' => null],
    );
    $work->authors()->attach(
        Author::factory()->create(['name' => 'Secondary Search Alias']),
        ['position' => 2, 'role' => null],
    );

    get(route('search.index', ['q' => 'Secondary Search Alias']))
        ->assertSuccessful()
        ->assertDontSee('Primary Search Work');
});

test('global search orders book results with stronger title matches before author-only matches', function () {
    $author = Author::factory()->create(['name' => 'Someone Findtoken Here']);

    $authorOnly = Work::factory()->create(['title' => 'ZZZ Author Only Findtoken']);
    $authorOnly->authors()->attach($author, ['position' => 1, 'role' => null]);

    Work::factory()->create(['title' => 'Middle findtoken Middle']);
    Work::factory()->create(['title' => 'findtoken Starts Here']);

    $response = get(route('search.index', ['q' => 'findtoken']));
    $response->assertSuccessful();

    $content = $response->getContent();
    expect($content)->toBeString();
    $posPrefix = mb_strpos($content, 'findtoken Starts Here');
    $posMiddle = mb_strpos($content, 'Middle findtoken Middle');
    $posAuthor = mb_strpos($content, 'ZZZ Author Only Findtoken');
    expect($posPrefix)->not->toBeFalse()
        ->and($posMiddle)->not->toBeFalse()
        ->and($posAuthor)->not->toBeFalse()
        ->and($posPrefix)->toBeLessThan($posMiddle)
        ->and($posMiddle)->toBeLessThan($posAuthor);
});

test('global search matches author names case-insensitively', function () {
    $author = Author::factory()->create(['name' => 'Erica Authorcasetest']);
    $work = Work::factory()->create(['title' => 'Some Novel']);
    $work->authors()->attach($author, ['position' => 1, 'role' => null]);

    get(route('search.index', ['q' => 'erica']))
        ->assertSuccessful()
        ->assertSee('Erica Authorcasetest')
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

test('authors index lists authors in name order by default', function () {
    Author::factory()->create(['name' => 'Zebra OrderTest']);
    Author::factory()->create(['name' => 'Alpha OrderTest']);
    Author::factory()->create(['name' => 'Mike OrderTest']);

    get(route('authors.index'))
        ->assertSuccessful()
        ->assertSeeInOrder(['Alpha OrderTest', 'Mike OrderTest', 'Zebra OrderTest']);
});

test('authors index can be filtered by letter case-insensitively', function () {
    Author::factory()->create(['name' => 'brian LetterFilter']);
    Author::factory()->create(['name' => 'Clara LetterFilter']);

    get(route('authors.index', ['letter' => 'B']))
        ->assertSuccessful()
        ->assertSee('brian LetterFilter')
        ->assertDontSee('Clara LetterFilter');
});

test('authors index hash letter shows names not starting with a latin letter', function () {
    Author::factory()->create(['name' => '123 Numeric Author']);
    Author::factory()->create(['name' => 'Alpha Author']);

    get(route('authors.index', ['letter' => '#']))
        ->assertSuccessful()
        ->assertSee('123 Numeric Author')
        ->assertDontSee('Alpha Author');
});

test('authors index paginator retains letter in query string', function () {
    foreach (range(1, 25) as $i) {
        Author::factory()->create(['name' => sprintf('B Paginate Author %02d', $i)]);
    }

    get(route('authors.index', ['letter' => 'B']))
        ->assertSuccessful()
        ->assertSee('letter=B', escape: false);
});

test('the books index header includes the global catalog search form', function () {
    get(route('books.index'))
        ->assertSuccessful()
        ->assertSee(__('Search books and authors'), escape: false)
        ->assertSee(route('search.index'), escape: false)
        ->assertSee('data-test="header-global-search"', escape: false)
        ->assertSee('data-test="global-search-form"', escape: false);
});

test('the authenticated app shell includes the global catalog search form', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('profile.edit'))
        ->assertSuccessful()
        ->assertSee(__('Search books and authors'), escape: false)
        ->assertSee(route('search.index'), escape: false);
});
