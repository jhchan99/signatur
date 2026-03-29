<?php

use App\Models\User;

test('guests visiting dashboard are redirected to the landing page', function () {
    $response = $this->get(route('dashboard'));

    $response->assertRedirect(route('home', absolute: false));
});

test('authenticated users visiting dashboard are redirected to the landing page', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $response = $this->get(route('dashboard'));

    $response->assertRedirect(route('home', absolute: false));
});
