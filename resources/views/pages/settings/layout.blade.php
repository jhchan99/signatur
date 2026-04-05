<div class="flex items-start gap-6 max-md:flex-col">
    <div class="w-full pb-4 md:w-[220px]">
        <div class="rounded-[1.5rem] border border-border-subtle bg-surface-card p-3 shadow-sm">
        <flux:navlist aria-label="{{ __('Settings') }}">
            <flux:navlist.item :href="route('profile.edit')" wire:navigate>{{ __('Profile') }}</flux:navlist.item>
            <flux:navlist.item :href="route('security.edit')" wire:navigate>{{ __('Security') }}</flux:navlist.item>
            <flux:navlist.item :href="route('appearance.edit')" wire:navigate>{{ __('Appearance') }}</flux:navlist.item>
        </flux:navlist>
        </div>
    </div>

    <flux:separator class="md:hidden" />

    <div class="flex-1 self-stretch rounded-[1.5rem] border border-border-subtle bg-surface-card p-6 shadow-sm max-md:pt-6 sm:p-8">
        <flux:heading class="text-text-strong">{{ $heading ?? '' }}</flux:heading>
        <flux:subheading class="text-text-muted">{{ $subheading ?? '' }}</flux:subheading>

        <div class="mt-5 w-full max-w-lg">
            {{ $slot }}
        </div>
    </div>
</div>
