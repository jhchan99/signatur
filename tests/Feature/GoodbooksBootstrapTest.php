<?php

use App\Models\Work;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('goodbooks bootstrap imports filtered english works with tags and multi-author order', function () {
    $fixture = base_path('tests/Fixtures/goodbooks');

    $this->artisan('goodbooks:bootstrap', [
        '--force' => true,
        '--path' => $fixture,
    ])->assertSuccessful();

    expect(Work::query()->count())->toBe(3);

    $hunger = Work::query()->where('goodbooks_book_id', 1)->first();
    expect($hunger)->not->toBeNull()
        ->and($hunger->open_library_key)->toBeNull()
        ->and($hunger->title)->toBe('The Hunger Games')
        ->and($hunger->first_publish_year)->toBe(2008)
        ->and($hunger->subjects)->toBe(['fiction', 'fantasy']);

    $pair = Work::query()->where('goodbooks_book_id', 4)->first();
    expect($pair)->not->toBeNull()
        ->and($pair->authors)->toHaveCount(2)
        ->and($pair->authors[0]->name)->toBe('Alice A')
        ->and($pair->authors[1]->name)->toBe('Bob B');

    $dup = Work::query()->where('goodbooks_book_id', 5)->first();
    expect($dup)->not->toBeNull()
        ->and($dup->authors)->toHaveCount(2)
        ->and($dup->authors[0]->name)->toBe('Alice A')
        ->and($dup->authors[1]->name)->toBe('Bob B');
});

test('goodbooks bootstrap refuses when works exist without --force', function () {
    Work::factory()->create();

    $fixture = base_path('tests/Fixtures/goodbooks');

    $this->artisan('goodbooks:bootstrap', [
        '--path' => $fixture,
    ])->assertFailed();
});

test('goodbooks bootstrap succeeds on empty catalog without --force', function () {
    $fixture = base_path('tests/Fixtures/goodbooks');

    $this->artisan('goodbooks:bootstrap', [
        '--path' => $fixture,
    ])->assertSuccessful();

    expect(Work::query()->count())->toBe(3);
});

test('goodbooks bootstrap --force truncates prior catalog rows', function () {
    Work::factory()->create(['title' => 'Old Seed']);
    $fixture = base_path('tests/Fixtures/goodbooks');

    $this->artisan('goodbooks:bootstrap', [
        '--force' => true,
        '--path' => $fixture,
    ])->assertSuccessful();

    expect(Work::query()->where('title', 'Old Seed')->exists())->toBeFalse()
        ->and(Work::query()->count())->toBe(3);
});
