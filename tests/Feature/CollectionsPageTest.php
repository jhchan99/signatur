<?php

use App\Models\User;

test('the collections placeholder page can be rendered', function () {
    $this->get(route('collections.index'))
        ->assertSuccessful()
        ->assertSee('Collections', escape: false)
        ->assertSee('guest-page-main', escape: false)
        ->assertSee('Under Construction')
        ->assertSee('Collections are taking shape.')
        ->assertSee(route('books.index'), escape: false);
});

test('the collections placeholder page loads for authenticated users', function () {
    $this->actingAs(User::factory()->create())
        ->get(route('collections.index'))
        ->assertSuccessful()
        ->assertSee('Collections are taking shape.')
        ->assertSee('Collections', escape: false)
        ->assertSee('Books', escape: false)
        ->assertSee('Authors', escape: false)
        ->assertSee('Account settings');
});
