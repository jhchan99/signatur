<?php

use App\Models\Book;
use App\Models\User;

test('the books index page can be rendered', function () {
    Book::factory()->create([
        'title' => 'Index Visible Book',
        'author' => 'Taylor Reader',
    ]);

    $response = $this->get(route('books.index'));

    $response
        ->assertSuccessful()
        ->assertSee('Books', escape: false)
        ->assertSee('Collections', escape: false)
        ->assertSee('Browse by', escape: false)
        ->assertSee('Find a book', escape: false)
        ->assertSee('Index Visible Book')
        ->assertSee('Taylor Reader')
        ->assertSee(route('books.index'), escape: false);
});

test('the books index orders books by title', function () {
    Book::factory()->create(['title' => 'Zebra Title']);
    Book::factory()->create(['title' => 'Alpha Title']);

    $response = $this->get(route('books.index'));

    $response->assertSuccessful();

    $content = $response->getContent();

    expect(strpos($content, 'Alpha Title'))->toBeLessThan(strpos($content, 'Zebra Title'));
});

test('the books index can search by title', function () {
    Book::factory()->create(['title' => 'Unique Solar Title', 'author' => 'Some Author']);
    Book::factory()->create(['title' => 'Other Moon', 'author' => 'Other Author']);

    $this->get(route('books.index', ['q' => 'Solar']))
        ->assertSuccessful()
        ->assertSee('Unique Solar Title')
        ->assertDontSee('Other Moon');
});

test('the books index can search by author', function () {
    Book::factory()->create(['title' => 'First Book', 'author' => 'Quinn AuthorMatch']);
    Book::factory()->create(['title' => 'Second Book', 'author' => 'Different Person']);

    $this->get(route('books.index', ['q' => 'AuthorMatch']))
        ->assertSuccessful()
        ->assertSee('First Book')
        ->assertDontSee('Second Book');
});

test('the books index can filter by publish year', function () {
    Book::factory()->create([
        'title' => 'From Nineteen Ninety Nine',
        'publish_year' => 1999,
    ]);

    Book::factory()->create([
        'title' => 'From Twenty Twenty',
        'publish_year' => 2020,
    ]);

    $this->get(route('books.index', ['year' => 1999]))
        ->assertSuccessful()
        ->assertSee('From Nineteen Ninety Nine')
        ->assertDontSee('From Twenty Twenty');
});

test('the books index can filter by subject', function () {
    Book::factory()->create([
        'title' => 'Essays Pick',
        'subjects' => ['Essays', 'Biography'],
    ]);

    Book::factory()->create([
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
        Book::factory()->create([
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
