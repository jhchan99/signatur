<?php

use App\Models\Author;
use App\Models\User;
use App\Models\Work;

test('the books index page can be rendered', function () {
    $work = Work::factory()->create([
        'title' => 'Index Visible Book',
    ]);
    $work->authors()->attach(
        Author::factory()->create(['name' => 'Taylor Reader']),
        ['position' => 1, 'role' => null],
    );

    $response = $this->get(route('books.index'));

    $response
        ->assertSuccessful()
        ->assertSee('Books', escape: false)
        ->assertSee('Authors', escape: false)
        ->assertSee('Collections', escape: false)
        ->assertSee('Browse by', escape: false)
        ->assertSee('Find a book', escape: false)
        ->assertSee('Index Visible Book')
        ->assertSee('Taylor Reader')
        ->assertSee(route('books.index'), escape: false);
});

test('the books index orders books by title', function () {
    Work::factory()->create(['title' => 'Zebra Title']);
    Work::factory()->create(['title' => 'Alpha Title']);

    $response = $this->get(route('books.index'));

    $response->assertSuccessful();

    $content = $response->getContent();

    expect(strpos($content, 'Alpha Title'))->toBeLessThan(strpos($content, 'Zebra Title'));
});

test('the books index can search by title', function () {
    Work::factory()->create(['title' => 'Unique Solar Title']);
    Work::factory()->create(['title' => 'Other Moon']);

    $this->get(route('books.index', ['q' => 'Solar']))
        ->assertSuccessful()
        ->assertSee('Unique Solar Title')
        ->assertDontSee('Other Moon');
});

test('the books index can search by author', function () {
    $matchingWork = Work::factory()->create(['title' => 'First Book']);
    $matchingAuthor = Author::factory()->create(['name' => 'Quinn AuthorMatch']);
    $matchingWork->authors()->attach($matchingAuthor, ['position' => 1, 'role' => null]);

    Work::factory()->create(['title' => 'Second Book']);

    $this->get(route('books.index', ['q' => 'AuthorMatch', 'mode' => 'author']))
        ->assertSuccessful()
        ->assertSee('First Book')
        ->assertDontSee('Second Book');
});

test('the books index can filter by publish year', function () {
    Work::factory()->create([
        'title' => 'From Nineteen Ninety Nine',
        'first_publish_year' => 1999,
    ]);

    Work::factory()->create([
        'title' => 'From Twenty Twenty',
        'first_publish_year' => 2020,
    ]);

    $this->get(route('books.index', ['year' => 1999]))
        ->assertSuccessful()
        ->assertSee('From Nineteen Ninety Nine')
        ->assertDontSee('From Twenty Twenty');
});

test('the books index can filter by subject', function () {
    Work::factory()->create([
        'title' => 'Essays Pick',
        'subjects' => ['Essays', 'Biography'],
    ]);

    Work::factory()->create([
        'title' => 'Fiction Only',
        'subjects' => ['Fiction'],
    ]);

    $this->get(route('books.index', ['subject' => 'Essays']))
        ->assertSuccessful()
        ->assertSee('Essays Pick')
        ->assertDontSee('Fiction Only');
});

test('the books index paginates results', function () {
    foreach (range(1, 16) as $i) {
        Work::factory()->create([
            'title' => sprintf('Pagination Book %02d', $i),
        ]);
    }

    $this->get(route('books.index'))
        ->assertSuccessful()
        ->assertSee('page=2', escape: false);
});

test('the books index hides guest tab navigation when logged in', function () {
    $this->actingAs(User::factory()->create())
        ->get(route('books.index'))
        ->assertSuccessful()
        ->assertDontSee('Collections', escape: false)
        ->assertSee('Account settings');
});
