<?php

use Database\Seeders\UserSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

uses(RefreshDatabase::class);

it('seeds users from database/data/users.csv', function () {
    $this->seed(UserSeeder::class);

    expect(DB::table('users')->count())->toBe(5)
        ->and(DB::table('users')->where('email', 'alice@example.com')->exists())->toBeTrue()
        ->and(DB::table('users')->where('email', 'david@example.com')->value('avatar_url'))->toBeNull();
});
