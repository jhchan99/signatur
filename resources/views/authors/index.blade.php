<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        @include('partials.head', ['title' => $title])
    </head>
    <body class="min-h-screen bg-zinc-950 text-zinc-100">
        <div class="relative">
            @include('partials.guest-header')

            <main class="mx-auto flex w-full max-w-7xl flex-col gap-8 px-6 pb-20 lg:px-8 lg:pb-28">
                <header class="space-y-2 border-b border-zinc-800 pb-6">
                    <h1 class="font-serif text-3xl font-semibold tracking-tight text-white sm:text-4xl">
                        {{ __('Authors') }}
                    </h1>
                    <p class="text-sm text-zinc-400">
                        {{ __('People in our catalog.') }}
                    </p>
                </header>

                <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
                    @foreach ($authors as $author)
                        <a
                            href="{{ route('authors.show', $author) }}"
                            class="rounded-[1.25rem] border border-zinc-800 bg-zinc-900/70 px-4 py-3 text-sm font-medium text-zinc-200 transition hover:border-white/40 hover:text-white"
                        >
                            {{ $author->name }}
                        </a>
                    @endforeach
                </div>

                @if ($authors->isEmpty())
                    <p class="rounded-[1.5rem] border border-zinc-800 bg-zinc-900/70 p-8 text-center text-sm text-zinc-400">
                        {{ __('No authors in the catalog yet.') }}
                    </p>
                @else
                    <div class="text-zinc-400">
                        {{ $authors->links() }}
                    </div>
                @endif
            </main>
        </div>
    </body>
</html>
