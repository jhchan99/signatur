<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        @include('partials.head')
        <script>
            window.Flux?.applyAppearance?.('light');
        </script>
    </head>
    <body class="min-h-screen bg-surface-page antialiased text-text-default">
        <div class="flex min-h-svh flex-col items-center justify-center gap-6 bg-surface-page p-6 md:p-10">
            <div class="flex w-full max-w-sm flex-col gap-2 rounded-[1.75rem] border border-border-subtle bg-surface-card p-6 shadow-sm md:p-8">
                <a href="{{ route('home') }}" class="flex flex-col items-center gap-2 font-medium" wire:navigate>
                    <span class="flex h-9 w-9 mb-1 items-center justify-center rounded-md">
                        <x-app-logo-icon class="size-9 fill-current text-text-strong" />
                    </span>
                    <span class="sr-only">{{ config('app.name', 'Laravel') }}</span>
                </a>
                <div class="flex flex-col gap-6">
                    {{ $slot }}
                </div>
            </div>
        </div>
        @fluxScripts
    </body>
</html>
