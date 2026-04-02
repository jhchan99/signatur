<?php

use App\Models\Author;
use App\Models\Edition;
use App\Models\Work;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

uses(RefreshDatabase::class);

test('openlibrary import ingests authors works and editions from dump fixtures', function () {
    $base = base_path('tests/Fixtures/openlibrary');

    $this->artisan('openlibrary:import', [
        'type' => 'authors',
        'file' => $base.'/authors_sample.txt',
    ])->assertSuccessful();

    $this->artisan('openlibrary:import', [
        'type' => 'works',
        'file' => $base.'/works_sample.txt',
    ])->assertSuccessful();

    $this->artisan('openlibrary:import', [
        'type' => 'editions',
        'file' => $base.'/editions_sample.txt',
    ])->assertSuccessful();

    $author = Author::query()->where('open_library_id', '/authors/OLIMPA')->first();
    expect($author)->not->toBeNull()
        ->and($author->name)->toBe('Import Author Alpha')
        ->and($author->birth_date)->toBe('1950');

    $work = Work::query()->where('open_library_key', '/works/OLIMPW')->first();
    expect($work)->not->toBeNull()
        ->and($work->title)->toBe('Import Work Beta')
        ->and($work->first_publish_year)->toBe(1999)
        ->and($work->cover_id)->toBe(12345);

    expect(DB::table('author_works')->where('role', 'author')->exists())->toBeTrue();

    $edition = Edition::query()->where('open_library_key', '/books/OLIMPM')->first();
    expect($edition)->not->toBeNull()
        ->and($edition->work_id)->toBe($work->id);

    expect(DB::table('edition_isbns')->where('isbn', '9780306406157')->exists())->toBeTrue();
});

test('openlibrary import respects --limit', function () {
    $path = base_path('tests/Fixtures/openlibrary/authors_two.txt');

    $this->artisan('openlibrary:import', [
        'type' => 'authors',
        'file' => $path,
        '--limit' => '1',
    ])->assertSuccessful();

    expect(Author::count())->toBe(1)
        ->and(Author::query()->value('open_library_id'))->toBe('/authors/OLIMPA');
});
