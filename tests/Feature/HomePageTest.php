<?php

use App\Models\BookFeaturedEntry;
use App\Models\User;
use App\Models\Work;
use Illuminate\Support\Facades\Cache;

test('the landing page can be rendered', function () {
    $response = $this->get(route('home'));

    $response
        ->assertSuccessful()
        ->assertSee('Track what you read')
        ->assertSee('Featured books')
        ->assertSee('What Signatr does')
        ->assertSee('Project Hail Mary')
        ->assertSee('Books', escape: false)
        ->assertSee('Collections', escape: false)
        ->assertDontSee('Open on Open Library');
});

test('the landing page hides guest tab navigation when logged in', function () {
    $this->actingAs(User::factory()->create())
        ->get(route('home'))
        ->assertSuccessful()
        ->assertDontSee('Collections', escape: false)
        ->assertSee('Account settings');
});

test('the landing page shows imported featured books when present', function () {
    Cache::forget('home.featured_books');

    $work = Work::factory()->create([
        'open_library_key' => '/works/OLUNIT123W',
        'title' => 'Visible Featured Title',
        'cover_id' => 9_999_999,
    ]);

    BookFeaturedEntry::query()->create([
        'import_batch' => '3f47ac10-58cc-4372-a567-0e92b2c3d479',
        'work_id' => $work->id,
        'position' => 1,
        'source' => 'test',
        'list_name' => 'homepage_test',
        'payload' => null,
        'imported_at' => now(),
    ]);

    $this->get(route('home'))
        ->assertSuccessful()
        ->assertSee('Visible Featured Title')
        ->assertDontSee('Casey Catalog')
        ->assertSee('/books/'.$work->id, escape: false)
        ->assertSee('https://covers.openlibrary.org/b/id/9999999-L.jpg', escape: false)
        ->assertSee('https://covers.openlibrary.org/b/id/9999999-M.jpg', escape: false)
        ->assertDontSee('Open on Open Library');
});

test('the landing page falls back to a high resolution hero when the featured book has no open library cover', function () {
    Cache::forget('home.featured_books');

    $work = Work::factory()->create([
        'open_library_key' => '/works/OLNOCOVER1W',
        'title' => 'No Cover Title',
        'cover_id' => null,
    ]);

    BookFeaturedEntry::query()->create([
        'import_batch' => '8c8f7f0c-7b0a-4c1b-9e2d-1a2b3c4d5e6f',
        'work_id' => $work->id,
        'position' => 1,
        'source' => 'test',
        'list_name' => 'homepage_test',
        'payload' => null,
        'imported_at' => now(),
    ]);

    $response = $this->get(route('home'));

    $response
        ->assertSuccessful()
        ->assertSee('No Cover Title')
        ->assertSee('/books/'.$work->id, escape: false)
        ->assertDontSee('Open on Open Library');

    expect($response->getContent())->toContain('w=2400');
});
