<?php

use Livewire\Attributes\Validate;
use Livewire\Component;
use Livewire\Livewire;

new class extends Component
{
    #[Validate('required', message: 'Choose a theme.')]
    #[Validate('in:dark,light', message: 'Theme must be light or dark.')]
    public string $theme = 'dark';

    public function mount(): void
    {
        $this->theme = session('theme', 'dark') === 'light' ? 'light' : 'dark';
    }

    public function updatedTheme(string $value): void
    {
        $this->validateOnly('theme');
        session(['theme' => $value]);
        $this->redirect(Livewire::originalUrl(), navigate: false);
    }
}; ?>

<div class="w-full">
    <flux:radio.group variant="segmented" wire:model.live="theme">
        <flux:radio value="dark" icon="moon">{{ __('Dark') }}</flux:radio>
        <flux:radio value="light" icon="sun">{{ __('Light') }}</flux:radio>
    </flux:radio.group>
</div>
