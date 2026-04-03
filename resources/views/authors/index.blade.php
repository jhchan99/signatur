@php
    /** @var \Illuminate\Contracts\Pagination\LengthAwarePaginator<int, \App\Models\Author> $authors */
    /** @var string|null $letter */
    $letterBase =
        'inline-flex min-w-[2rem] items-center justify-center rounded-full border px-2.5 py-1 text-xs font-semibold transition focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-white';
    $letterInactive = 'border-zinc-800 bg-zinc-900/70 text-zinc-300 hover:border-zinc-600 hover:text-white';
    $letterActive = 'border-white/60 bg-white text-zinc-950 shadow-sm';
@endphp
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

                <nav aria-label="{{ __('Browse authors by letter') }}" class="space-y-3">
                    <div class="flex flex-wrap items-center gap-2 gap-y-2">
                        <span class="me-1 text-xs font-semibold uppercase tracking-wider text-zinc-500">{{ __('Browse') }}</span>
                        <a
                            href="{{ route('authors.index') }}"
                            @class([$letterBase, $letter === null ? $letterActive : $letterInactive])
                        >
                            {{ __('All') }}
                        </a>
                        @foreach (range('A', 'Z') as $browseLetter)
                            <a
                                href="{{ route('authors.index', ['letter' => $browseLetter]) }}"
                                @class([$letterBase, $letter === $browseLetter ? $letterActive : $letterInactive])
                            >
                                {{ $browseLetter }}
                            </a>
                        @endforeach
                        <a
                            href="{{ route('authors.index', ['letter' => '#']) }}"
                            @class([$letterBase, $letter === '#' ? $letterActive : $letterInactive])
                            title="{{ __('Names not starting with A–Z') }}"
                        >
                            #
                        </a>
                    </div>
                    @if ($letter === '#')
                        <p class="text-sm text-zinc-400">
                            {{ __('Showing authors whose names do not start with A–Z.') }}
                        </p>
                    @elseif (is_string($letter) && strlen($letter) === 1 && $letter >= 'A' && $letter <= 'Z')
                        <p class="text-sm text-zinc-400">
                            {!! __('Showing authors starting with :letter.', ['letter' => '<strong class="font-semibold text-zinc-200">'.$letter.'</strong>']) !!}
                        </p>
                    @endif
                </nav>

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
