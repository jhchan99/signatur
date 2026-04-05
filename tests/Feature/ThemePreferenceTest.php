<?php

use App\Models\User;
use Livewire\Livewire;
use Livewire\Mechanisms\HandleRequests\EndpointResolver;

test('guest catalog omits session driven dark class on html element', function () {
    $response = $this->withSession(['theme' => 'dark'])
        ->get(route('books.index'));

    $response->assertSuccessful();
    expect($response->getContent())->not->toMatch('/<html[^>]+\sclass="[^"]*\bdark\b/');
});

test('guest catalog flux bootstrap uses system appearance', function () {
    $response = $this->get(route('books.index'));

    $response->assertSuccessful();
    expect($response->getContent())->toContain('applyAppearance');
    expect($response->getContent())->toMatch('/applyAppearance\(\s*[\'"]system[\'"]\s*\)/');
});

test('livewire update endpoint rejects get requests', function () {
    $this->get(EndpointResolver::updatePath())->assertNotFound();
});

test('livewire update endpoint rejects head requests', function () {
    $this->call('HEAD', EndpointResolver::updatePath())->assertNotFound();
});

test('theme preference settings redirects away from livewire update url', function () {
    $updatePath = EndpointResolver::updatePath();

    $testable = Livewire::actingAs(User::factory()->create())
        ->test('theme-preference')
        ->set('theme', 'light');

    $testable->assertRedirect();

    expect($testable->effects['redirect'] ?? '')
        ->not->toContain($updatePath);
});

test('appearance settings persists theme to session', function () {
    Livewire::actingAs(User::factory()->create())
        ->test('theme-preference')
        ->set('theme', 'light');

    expect(session('theme'))->toBe('light');
});
