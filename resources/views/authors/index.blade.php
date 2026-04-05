@php
    /** @var \Illuminate\Contracts\Pagination\LengthAwarePaginator<int, \App\Models\Author> $authors */
    /** @var string|null $letter */
    $letterBase =
        'inline-flex min-w-[2rem] items-center justify-center rounded-full border px-2.5 py-1 text-xs font-semibold transition focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-action-primary';
    $letterInactive = 'border-ui bg-surface text-ui-muted hover:border-parchment-4 dark:hover:border-ink-4 hover:text-ui-primary';
    $letterActive = 'border-border-strong bg-surface-strong text-ui-primary shadow-sm';
@endphp
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        @include('partials.head', ['title' => $title])
    </head>
    <body class="min-h-screen bg-page text-ui-primary antialiased">
        <div class="relative">
            @include('partials.guest-header')

            <main class="guest-page-main mx-auto flex w-full max-w-7xl flex-col gap-8 px-6 pb-20 lg:px-8 lg:pb-28">
                <header class="space-y-2 border-b border-ui pb-6">
                    <h1 class="font-serif text-3xl font-semibold tracking-tight text-ui-primary sm:text-4xl">
                        {{ __('Authors') }}
                    </h1>
                    <p class="text-sm text-ui-muted">
                        {{ __('People in our catalog.') }}
                    </p>
                </header>

                <nav aria-label="{{ __('Browse authors by letter') }}" class="space-y-3">
                    <div class="flex flex-wrap items-center gap-2 gap-y-2">
                        <span class="me-1 text-xs font-semibold uppercase tracking-wider text-ui-faint">{{ __('Browse') }}</span>
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
                        <p class="text-sm text-ui-muted">
                            {{ __('Showing authors whose names do not start with A–Z.') }}
                        </p>
                    @elseif (is_string($letter) && strlen($letter) === 1 && $letter >= 'A' && $letter <= 'Z')
                        <p class="text-sm text-ui-muted">
                            {!! __('Showing authors starting with :letter.', ['letter' => '<strong class="font-semibold text-ui-primary">'.$letter.'</strong>']) !!}
                        </p>
                    @endif
                </nav>

                <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
                    @foreach ($authors as $author)
                        <a
                            href="{{ route('authors.show', $author) }}"
                            class="rounded-[1.25rem] border border-ui bg-surface px-4 py-3 text-sm font-medium text-ui-primary transition hover:border-parchment-4 dark:hover:border-ink-4 hover:text-ui-primary"
                        >
                            {{ $author->name }}
                        </a>
                    @endforeach
                </div>

                @if ($authors->isEmpty())
                    <p class="rounded-[1.5rem] border border-ui bg-surface p-8 text-center text-sm text-ui-muted">
                        {{ __('No authors in the catalog yet.') }}
                    </p>
                @else
                    <div class="text-ui-muted">
                        {{ $authors->links() }}
                    </div>
                @endif
            </main>
        </div>
    </body>
</html>
