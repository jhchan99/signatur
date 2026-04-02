<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

uses(RefreshDatabase::class);

it('seeds all tables from database/data CSVs', function () {
    $this->seed();

    expect(DB::table('users')->count())->toBe(5)
        ->and(DB::table('books')->count())->toBe(6)
        ->and(DB::table('reading_logs')->count())->toBe(9)
        ->and(DB::table('follows')->count())->toBe(8)
        ->and(DB::table('activities')->count())->toBe(8)
        ->and(DB::table('book_featured_entries')->count())->toBe(5);
});
