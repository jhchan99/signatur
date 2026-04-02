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

test('openlibrary import accepts long author names', function () {
    $longName = str_repeat('Very Long Imported Author Name ', 12);
    $fixture = tempnam(sys_get_temp_dir(), 'ol-authors-');

    file_put_contents(
        $fixture,
        "/type/author\t/authors/OLLONGA\t1\t2019-01-01T00:00:00.000000\t".json_encode([
            'name' => $longName,
            'bio' => null,
        ]).PHP_EOL,
    );

    $this->artisan('openlibrary:import', [
        'type' => 'authors',
        'file' => $fixture,
    ])->assertSuccessful();

    expect(Author::query()->where('open_library_id', '/authors/OLLONGA')->value('name'))
        ->toBe(trim($longName));

    @unlink($fixture);
});

test('openlibrary import skips non-ascii primary author names', function () {
    $fixture = tempnam(sys_get_temp_dir(), 'ol-authors-ascii-');

    file_put_contents(
        $fixture,
        "/type/author\t/authors/OLNONASCII\t1\t2019-01-01T00:00:00.000000\t".json_encode([
            'name' => 'José Unicode Name',
            'bio' => null,
        ], JSON_THROW_ON_ERROR).PHP_EOL,
    );

    $this->artisan('openlibrary:import', [
        'type' => 'authors',
        'file' => $fixture,
    ])->assertSuccessful();

    expect(Author::query()->where('open_library_id', '/authors/OLNONASCII')->exists())->toBeFalse();

    @unlink($fixture);
});

test('openlibrary import normalizes messy author birth and death dates to a year', function () {
    $fixture = tempnam(sys_get_temp_dir(), 'ol-authors-dates-');

    file_put_contents(
        $fixture,
        "/type/author\t/authors/OLDATES1\t1\t2019-01-01T00:00:00.000000\t".json_encode([
            'name' => 'Date Cleanup Author',
            'bio' => null,
            'birth_date' => '(c. 1950 March)',
            'death_date' => 'circa 2001 or so',
        ], JSON_THROW_ON_ERROR).PHP_EOL,
    );

    $this->artisan('openlibrary:import', [
        'type' => 'authors',
        'file' => $fixture,
    ])->assertSuccessful();

    $author = Author::query()->where('open_library_id', '/authors/OLDATES1')->first();
    expect($author)->not->toBeNull()
        ->and($author->birth_date)->toBe('1950')
        ->and($author->death_date)->toBe('2001');

    @unlink($fixture);
});

test('openlibrary import stores null life years when no four digit year is present', function () {
    $fixture = tempnam(sys_get_temp_dir(), 'ol-authors-nodates-');

    file_put_contents(
        $fixture,
        "/type/author\t/authors/OLNODATES\t1\t2019-01-01T00:00:00.000000\t".json_encode([
            'name' => 'No Year Author',
            'bio' => null,
            'birth_date' => 'born in the spring',
            'death_date' => 'unknown',
        ], JSON_THROW_ON_ERROR).PHP_EOL,
    );

    $this->artisan('openlibrary:import', [
        'type' => 'authors',
        'file' => $fixture,
    ])->assertSuccessful();

    $author = Author::query()->where('open_library_id', '/authors/OLNODATES')->first();
    expect($author)->not->toBeNull()
        ->and($author->birth_date)->toBeNull()
        ->and($author->death_date)->toBeNull();

    @unlink($fixture);
});
