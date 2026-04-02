<?php

use App\Models\Author;
use App\Models\Book;
use App\Models\ReadingLog;
use App\Models\User;

test('the book detail page renders stored catalog fields', function () {
    $book = Book::factory()->create([
        'title' => 'Page Title Alpha',
        'publish_year' => 2019,
        'description' => str_repeat('Synopsis line. ', 40),
        'subjects' => ['Essays', 'Biography'],
    ]);
    $book->authors()->attach(
        Author::factory()->create(['name' => 'Author Example']),
        ['position' => 1],
    );

    $this->get(route('books.show', $book))
        ->assertSuccessful()
        ->assertSee('Page Title Alpha')
        ->assertSee('Author Example')
        ->assertSee('2019')
        ->assertSee('Essays')
        ->assertSee('Biography')
        ->assertSee('No public reviews yet')
        ->assertSee('Back to home')
        ->assertSee('Books', escape: false)
        ->assertSee('Collections', escape: false);
});

test('the book detail page hides guest tab navigation when logged in', function () {
    $book = Book::factory()->create([
        'title' => 'Auth Header Book',
    ]);

    $this->actingAs(User::factory()->create())
        ->get(route('books.show', $book))
        ->assertSuccessful()
        ->assertDontSee('Collections', escape: false)
        ->assertSee('Account settings');
});

test('the book page shows public reviews and hides private logs', function () {
    $book = Book::factory()->create([
        'title' => 'Reviewed Title',
    ]);

    $publicUser = User::factory()->create([
        'display_name' => 'Public Reviewer',
    ]);

    ReadingLog::factory()
        ->for($publicUser)
        ->for($book)
        ->create([
            'review_text' => 'This review is public.',
            'is_private' => false,
            'rating' => 4.5,
        ]);

    $privateUser = User::factory()->create();

    ReadingLog::factory()
        ->for($privateUser)
        ->for($book)
        ->create([
            'review_text' => 'This should stay hidden.',
            'is_private' => true,
        ]);

    $this->get(route('books.show', $book))
        ->assertSuccessful()
        ->assertSee('Public Reviewer')
        ->assertSee('This review is public.')
        ->assertSee('4.5 / 5')
        ->assertDontSee('This should stay hidden.');
});

test('the book page omits empty or null review text', function () {
    $book = Book::factory()->create();

    $user = User::factory()->create();

    ReadingLog::factory()
        ->for($user)
        ->for($book)
        ->withoutReviewText()
        ->create([
            'is_private' => false,
        ]);

    $this->get(route('books.show', $book))
        ->assertSuccessful()
        ->assertSee('No public reviews yet');
});
