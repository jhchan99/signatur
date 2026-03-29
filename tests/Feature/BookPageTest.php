<?php

use App\Models\Book;
use App\Models\ReadingLog;
use App\Models\User;

test('the book detail page renders stored catalog fields', function () {
    $book = Book::factory()->create([
        'title' => 'Page Title Alpha',
        'author' => 'Author Example',
        'publish_year' => 2019,
        'description' => str_repeat('Synopsis line. ', 40),
        'subjects' => ['Essays', 'Biography'],
    ]);

    $this->get(route('books.show', $book))
        ->assertSuccessful()
        ->assertSee('Page Title Alpha')
        ->assertSee('Author Example')
        ->assertSee('2019')
        ->assertSee('Essays')
        ->assertSee('Biography')
        ->assertSee('No public reviews yet');
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
