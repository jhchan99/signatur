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

test('openlibrary works import creates author_works using stub authors when authors dump was not imported first', function () {
    $base = base_path('tests/Fixtures/openlibrary');

    $this->artisan('openlibrary:import', [
        'type' => 'works',
        'file' => $base.'/works_sample.txt',
    ])->assertSuccessful();

    $author = Author::query()->where('open_library_id', '/authors/OLIMPA')->first();
    expect($author)->not->toBeNull()
        ->and($author->name)->toBe('Pending Author');

    $work = Work::query()->where('open_library_key', '/works/OLIMPW')->first();
    expect($work)->not->toBeNull();

    expect(DB::table('author_works')->where('work_id', $work->id)->where('author_id', $author->id)->exists())->toBeTrue();

    $this->artisan('openlibrary:import', [
        'type' => 'authors',
        'file' => $base.'/authors_sample.txt',
    ])->assertSuccessful();

    $author->refresh();
    expect($author->name)->toBe('Import Author Alpha');
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

test('openlibrary import accepts long english-looking single-word author names within limits', function () {
    $longName = 'A'.str_repeat('b', 62);
    $fixture = tempnam(sys_get_temp_dir(), 'ol-authors-');

    file_put_contents(
        $fixture,
        "/type/author\t/authors/OLLONGA\t1\t2019-01-01T00:00:00.000000\t".json_encode([
            'name' => $longName,
            'bio' => null,
        ], JSON_THROW_ON_ERROR).PHP_EOL,
    );

    $this->artisan('openlibrary:import', [
        'type' => 'authors',
        'file' => $fixture,
    ])->assertSuccessful();

    expect(Author::query()->where('open_library_id', '/authors/OLLONGA')->value('name'))
        ->toBe($longName);

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

test('openlibrary import skips punctuation wrapped english names', function () {
    $fixture = tempnam(sys_get_temp_dir(), 'ol-authors-wrap-');

    file_put_contents(
        $fixture,
        "/type/author\t/authors/OLWRAP1\t1\t2019-01-01T00:00:00.000000\t".json_encode([
            'name' => '-Bradford Miller-',
            'bio' => null,
        ], JSON_THROW_ON_ERROR).PHP_EOL,
    );

    $this->artisan('openlibrary:import', [
        'type' => 'authors',
        'file' => $fixture,
    ])->assertSuccessful();

    expect(Author::query()->where('open_library_id', '/authors/OLWRAP1')->exists())->toBeFalse();

    @unlink($fixture);
});

test('openlibrary import skips html numeric entity author names', function () {
    $fixture = tempnam(sys_get_temp_dir(), 'ol-authors-ent-');

    file_put_contents(
        $fixture,
        "/type/author\t/authors/OLENT1\t1\t2019-01-01T00:00:00.000000\t".json_encode([
            'name' => '&#1057;&#1077;&#1088;&#1075;&#1077;&#1081; Test',
            'bio' => null,
        ], JSON_THROW_ON_ERROR).PHP_EOL,
    );

    $this->artisan('openlibrary:import', [
        'type' => 'authors',
        'file' => $fixture,
    ])->assertSuccessful();

    expect(Author::query()->where('open_library_id', '/authors/OLENT1')->exists())->toBeFalse();

    @unlink($fixture);
});

test('openlibrary import skips label like parenthetical author names', function () {
    $fixture = tempnam(sys_get_temp_dir(), 'ol-authors-label-');

    file_put_contents(
        $fixture,
        "/type/author\t/authors/OLLABEL1\t1\t2019-01-01T00:00:00.000000\t".json_encode([
            'name' => '(MORMONISM)',
            'bio' => null,
        ], JSON_THROW_ON_ERROR).PHP_EOL,
    );

    $this->artisan('openlibrary:import', [
        'type' => 'authors',
        'file' => $fixture,
    ])->assertSuccessful();

    expect(Author::query()->where('open_library_id', '/authors/OLLABEL1')->exists())->toBeFalse();

    @unlink($fixture);
});

test('openlibrary import skips all caps token author names', function () {
    $fixture = tempnam(sys_get_temp_dir(), 'ol-authors-caps-');

    file_put_contents(
        $fixture,
        "/type/author\t/authors/OLCAPS1\t1\t2019-01-01T00:00:00.000000\t".json_encode([
            'name' => 'IBM Editorial',
            'bio' => null,
        ], JSON_THROW_ON_ERROR).PHP_EOL,
    );

    $this->artisan('openlibrary:import', [
        'type' => 'authors',
        'file' => $fixture,
    ])->assertSuccessful();

    expect(Author::query()->where('open_library_id', '/authors/OLCAPS1')->exists())->toBeFalse();

    @unlink($fixture);
});

test('openlibrary import skips title like author strings with commas', function () {
    $fixture = tempnam(sys_get_temp_dir(), 'ol-authors-title-');

    file_put_contents(
        $fixture,
        "/type/author\t/authors/OLTITLE1\t1\t2019-01-01T00:00:00.000000\t".json_encode([
            'name' => 'Idea absurd, Gianfranco Brebbia e il cinema',
            'bio' => null,
        ], JSON_THROW_ON_ERROR).PHP_EOL,
    );

    $this->artisan('openlibrary:import', [
        'type' => 'authors',
        'file' => $fixture,
    ])->assertSuccessful();

    expect(Author::query()->where('open_library_id', '/authors/OLTITLE1')->exists())->toBeFalse();

    @unlink($fixture);
});

test('openlibrary import accepts hyphenated and irish style surnames', function () {
    $fixture = tempnam(sys_get_temp_dir(), 'ol-authors-okshape-');

    file_put_contents(
        $fixture,
        "/type/author\t/authors/OLOK1\t1\t2019-01-01T00:00:00.000000\t".json_encode([
            'name' => 'Mary Jane Watson-Parker',
            'bio' => null,
        ], JSON_THROW_ON_ERROR).PHP_EOL.
        "/type/author\t/authors/OLOK2\t1\t2019-01-01T00:00:00.000000\t".json_encode([
            'name' => 'Sean O\'Brien',
            'bio' => null,
        ], JSON_THROW_ON_ERROR).PHP_EOL.
        "/type/author\t/authors/OLOK3\t1\t2019-01-01T00:00:00.000000\t".json_encode([
            'name' => 'Alistair MacIntyre',
            'bio' => null,
        ], JSON_THROW_ON_ERROR).PHP_EOL,
    );

    $this->artisan('openlibrary:import', [
        'type' => 'authors',
        'file' => $fixture,
    ])->assertSuccessful();

    expect([
        Author::query()->where('open_library_id', '/authors/OLOK1')->value('name'),
        Author::query()->where('open_library_id', '/authors/OLOK2')->value('name'),
        Author::query()->where('open_library_id', '/authors/OLOK3')->value('name'),
    ])->toBe([
        'Mary Jane Watson-Parker',
        'Sean O\'Brien',
        'Alistair MacIntyre',
    ]);

    @unlink($fixture);
});

test('openlibrary import skips html entity work titles', function () {
    $fixture = tempnam(sys_get_temp_dir(), 'ol-works-ent-');

    file_put_contents(
        $fixture,
        "/type/work\t/works/OLWENT\t1\t2019-01-01T00:00:00.000000\t".json_encode([
            'title' => '&#1057;&#1077; Entity Spam Title',
            'authors' => [],
        ], JSON_THROW_ON_ERROR).PHP_EOL,
    );

    $this->artisan('openlibrary:import', [
        'type' => 'works',
        'file' => $fixture,
    ])->assertSuccessful();

    expect(Work::query()->where('open_library_key', '/works/OLWENT')->exists())->toBeFalse();

    @unlink($fixture);
});

test('openlibrary import skips conference style work titles', function () {
    $fixture = tempnam(sys_get_temp_dir(), 'ol-works-conf-');

    file_put_contents(
        $fixture,
        "/type/work\t/works/OLWCONF\t1\t2019-01-01T00:00:00.000000\t".json_encode([
            'title' => 'Proceedings of the Great Workshop on Fiction',
            'authors' => [],
        ], JSON_THROW_ON_ERROR).PHP_EOL,
    );

    $this->artisan('openlibrary:import', [
        'type' => 'works',
        'file' => $fixture,
    ])->assertSuccessful();

    expect(Work::query()->where('open_library_key', '/works/OLWCONF')->exists())->toBeFalse();

    @unlink($fixture);
});

test('openlibrary import accepts english work titles and normalizes messy first publish year', function () {
    $fixture = tempnam(sys_get_temp_dir(), 'ol-works-year-');

    file_put_contents(
        $fixture,
        "/type/work\t/works/OLWYEAR\t1\t2019-01-01T00:00:00.000000\t".json_encode([
            'title' => 'English Title For Year Test',
            'first_publish_date' => '(c. 1985, revised 2001)',
            'authors' => [],
        ], JSON_THROW_ON_ERROR).PHP_EOL,
    );

    $this->artisan('openlibrary:import', [
        'type' => 'works',
        'file' => $fixture,
    ])->assertSuccessful();

    $work = Work::query()->where('open_library_key', '/works/OLWYEAR')->first();
    expect($work)->not->toBeNull()
        ->and($work->title)->toBe('English Title For Year Test')
        ->and($work->first_publish_year)->toBe(1985);

    @unlink($fixture);
});

test('openlibrary import stores null publish year when work date has no year', function () {
    $fixture = tempnam(sys_get_temp_dir(), 'ol-works-noyear-');

    file_put_contents(
        $fixture,
        "/type/work\t/works/OLWNOYEAR\t1\t2019-01-01T00:00:00.000000\t".json_encode([
            'title' => 'No Year Work Title Here',
            'first_publish_date' => 'sometime in spring',
            'authors' => [],
        ], JSON_THROW_ON_ERROR).PHP_EOL,
    );

    $this->artisan('openlibrary:import', [
        'type' => 'works',
        'file' => $fixture,
    ])->assertSuccessful();

    $work = Work::query()->where('open_library_key', '/works/OLWNOYEAR')->first();
    expect($work)->not->toBeNull()
        ->and($work->first_publish_year)->toBeNull();

    @unlink($fixture);
});
