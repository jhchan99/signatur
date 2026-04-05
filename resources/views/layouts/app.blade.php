<x-layouts::app.sidebar :title="$title ?? null">
    <flux:main class="bg-page text-ui-primary">
        {{ $slot }}
    </flux:main>
</x-layouts::app.sidebar>
