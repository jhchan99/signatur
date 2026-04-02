<?php

use App\Models\Author;
use App\Models\ReadingLog;
use App\Models\User;
use App\Models\Work;

test('the book detail page renders stored catalog fields', function () {
    $work = Work::factory()->create([
        'title' => 'Page Title Alpha',
        'first_publish_year' => 2019,
        'description' => str_repeat('Synopsis line. ', 40),
        'subjects' => ['Essays', 'Biography'],
    ]);
    $work->authors()->attach(
        Author::factory()->create(['name' => 'Author Example']),
        ['position' => 1, 'role' => null],
    );

    $this->get(route('books.show', $work))
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
    $work = Work::factory()->create([
        'title' => 'Auth Header Book',
    ]);

    $this->actingAs(User::factory()->create())
        ->get(route('books.show', $work))
        ->assertSuccessful()
        ->assertDontSee('Collections', escape: false)
        ->assertSee('Account settings');
});

test('the book page shows public reviews and hides private logs', function () {
    $work = Work::factory()->create([
        'title' => 'Reviewed Title',
    ]);

    $publicUser = User::factory()->create([
        'display_name' => 'Public Reviewer',
    ]);

    ReadingLog::factory()
        ->for($publicUser)
        ->for($work, 'work')
        ->create([
            'review_text' => 'This review is public.',
            'is_private' => false,
            'rating' => 4.5,
        ]);

    $privateUser = User::factory()->create();

    ReadingLog::factory()
        ->for($privateUser)
        ->for($work, 'work')
        ->create([
            'review_text' => 'This should stay hidden.',
            'is_private' => true,
        ]);

    $this->get(route('books.show', $work))
        ->assertSuccessful()
        ->assertSee('Public Reviewer')
        ->assertSee('This review is public.')
        ->assertSee('4.5 / 5')
        ->assertDontSee('This should stay hidden.');
});

test('the book page omits empty or null review text', function () {
    $work = Work::factory()->create();

    $user = User::factory()->create();

    ReadingLog::factory()
        ->for($user)
        ->for($work, 'work')
        ->withoutReviewText()
        ->create([
            'is_private' => false,
        ]);

    $this->get(route('books.show', $work))
        ->assertSuccessful()
        ->assertSee('No public reviews yet');
});
