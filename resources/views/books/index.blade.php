<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        @include('partials.head', ['title' => $title])
    </head>
    <body class="min-h-screen bg-zinc-950 text-zinc-100">
        <div class="relative">
            @include('partials.guest-header')

            <main class="mx-auto flex w-full max-w-7xl flex-col gap-8 px-6 pb-20 lg:px-8 lg:pb-28">
                <form method="get" action="{{ route('books.index') }}" class="border-b border-zinc-800 pb-4">
                    @php
                        $filterBubble = 'inline-flex min-w-0 items-stretch overflow-hidden rounded-md border border-zinc-800 bg-zinc-950 transition focus-within:border-zinc-500 hover:border-zinc-600';
                        $filterBubbleLabel = 'flex shrink-0 items-center border-r border-zinc-800 bg-zinc-900/60 px-2.5 py-2 text-[10px] font-semibold uppercase tracking-[0.2em] text-zinc-500';
                        $filterSelect = 'min-w-0 flex-1 cursor-pointer appearance-none border-0 bg-transparent py-2 pl-2 pr-8 text-[11px] font-semibold uppercase tracking-wider text-zinc-200 outline-none sm:min-w-[6.5rem]';
                        $filterSelectDisabled = 'min-w-0 flex-1 cursor-not-allowed appearance-none border-0 bg-transparent py-2 pl-2 pr-8 text-[11px] font-semibold uppercase tracking-wider text-zinc-400 outline-none sm:min-w-[6.5rem]';
                        $filterChevron = 'pointer-events-none absolute right-2 top-1/2 -translate-y-1/2 text-[10px] text-zinc-500';
                        $displayAuthor = static fn (\App\Models\Book $book): ?string => $book->displayAuthor();
                    @endphp
                    <div class="flex flex-col gap-4">
                        <div class="flex flex-wrap items-baseline justify-between gap-x-8 gap-y-2">
                            <p class="text-[10px] font-semibold uppercase tracking-[0.2em] text-zinc-500">
                                Browse by
                            </p>
                            <p class="text-[10px] font-semibold uppercase tracking-[0.2em] text-zinc-500 lg:text-right">
                                Find a book
                            </p>
                        </div>

                        <div class="flex flex-col gap-4 lg:flex-row lg:flex-wrap lg:items-center lg:justify-between">
                            <div class="flex min-w-0 flex-1 flex-wrap items-center gap-2 sm:gap-3">
                                <div class="{{ $filterBubble }}">
                                    <label for="filter-year" class="{{ $filterBubbleLabel }}">
                                        Year
                                    </label>
                                    <div class="relative min-w-0 flex-1">
                                        <select
                                            id="filter-year"
                                            name="year"
                                            onchange="this.form.requestSubmit()"
                                            class="{{ $filterSelect }}"
                                        >
                                            <option value="">Any</option>
                                            @foreach ($yearOptions as $y)
                                                <option value="{{ $y }}" @selected($filters['year'] === (string) $y)>{{ $y }}</option>
                                            @endforeach
                                        </select>
                                        <span class="{{ $filterChevron }}" aria-hidden="true">▾</span>
                                    </div>
                                </div>

                                <div class="{{ $filterBubble }} max-sm:w-full sm:max-w-sm">
                                    <label for="filter-subject" class="{{ $filterBubbleLabel }}">
                                        Subject
                                    </label>
                                    <div class="relative min-w-0 flex-1">
                                        <select
                                            id="filter-subject"
                                            name="subject"
                                            onchange="this.form.requestSubmit()"
                                            class="{{ $filterSelect }} sm:min-w-[8rem] sm:max-w-[12rem]"
                                        >
                                            <option value="">Any</option>
                                            @foreach ($subjectOptions as $tag)
                                                <option value="{{ $tag }}" @selected($filters['subject'] === $tag)>{{ $tag }}</option>
                                            @endforeach
                                        </select>
                                        <span class="{{ $filterChevron }}" aria-hidden="true">▾</span>
                                    </div>
                                </div>

                                <div class="{{ $filterBubble }} opacity-45" title="Coming soon">
                                    <span class="{{ $filterBubbleLabel }}">
                                        Rating
                                    </span>
                                    <div class="relative min-w-0 flex-1">
                                        <select
                                            disabled
                                            class="{{ $filterSelectDisabled }}"
                                            aria-disabled="true"
                                        >
                                            <option>—</option>
                                        </select>
                                        <span class="pointer-events-none absolute right-2 top-1/2 -translate-y-1/2 text-[10px] text-zinc-600" aria-hidden="true">▾</span>
                                    </div>
                                </div>

                                <!-- <div class="{{ $filterBubble }} opacity-45" title="Coming soon">
                                    <span class="{{ $filterBubbleLabel }}">
                                        Popular
                                    </span>
                                    <div class="relative min-w-0 flex-1">
                                        <select
                                            disabled
                                            class="{{ $filterSelectDisabled }}"
                                            aria-disabled="true"
                                        >
                                            <option>—</option>
                                        </select>
                                        <span class="pointer-events-none absolute right-2 top-1/2 -translate-y-1/2 text-[10px] text-zinc-600" aria-hidden="true">▾</span>
                                    </div>
                                </div> -->

                                @if (filled($filters['q']) || filled($filters['subject']) || filled($filters['year']) || ($filters['mode'] ?? 'books') !== 'books')
                                    <p class="w-full pb-0.5 sm:w-auto sm:self-center">
                                        <a href="{{ route('books.index') }}" class="text-[10px] font-semibold uppercase tracking-wider text-zinc-500 underline decoration-zinc-700 underline-offset-4 hover:text-zinc-300 hover:decoration-zinc-500">
                                            Clear filters
                                        </a>
                                    </p>
                                @endif
                            </div>

                            <div class="flex w-full flex-col gap-3 lg:w-auto lg:max-w-md">
                                <fieldset class="flex flex-wrap gap-2">
                                    <legend class="sr-only">{{ __('Search scope') }}</legend>
                                    <label class="inline-flex cursor-pointer items-center gap-2 rounded-md border border-zinc-800 bg-zinc-900/70 px-3 py-2 text-[10px] font-semibold uppercase tracking-wider text-zinc-300 has-[:checked]:border-zinc-500 has-[:checked]:bg-zinc-800">
                                        <input
                                            type="radio"
                                            name="mode"
                                            value="books"
                                            class="sr-only"
                                            @checked(($filters['mode'] ?? 'books') === 'books')
                                        />
                                        {{ __('Books') }}
                                    </label>
                                    <label class="inline-flex cursor-pointer items-center gap-2 rounded-md border border-zinc-800 bg-zinc-900/70 px-3 py-2 text-[10px] font-semibold uppercase tracking-wider text-zinc-300 has-[:checked]:border-zinc-500 has-[:checked]:bg-zinc-800">
                                        <input
                                            type="radio"
                                            name="mode"
                                            value="author"
                                            class="sr-only"
                                            @checked(($filters['mode'] ?? 'books') === 'author')
                                        />
                                        {{ __('Author') }}
                                    </label>
                                </fieldset>
                                <div class="flex w-full shrink-0 items-center gap-2">
                                    <input
                                        id="find-book-q"
                                        type="search"
                                        name="q"
                                        value="{{ $filters['q'] }}"
                                        placeholder="{{ ($filters['mode'] ?? 'books') === 'author' ? __('Author name') : __('Title or author') }}"
                                        aria-label="{{ ($filters['mode'] ?? 'books') === 'author' ? __('Search by author') : __('Search title or author') }}"
                                        class="min-w-0 flex-1 rounded-md border border-zinc-800 bg-zinc-950 px-3 py-2 text-sm text-white placeholder:text-zinc-600 outline-none transition focus:border-zinc-500 lg:w-52"
                                    />
                                    <button type="submit" class="shrink-0 rounded-md border border-zinc-700 bg-zinc-900 px-3 py-2 text-xs font-semibold uppercase tracking-wider text-zinc-200 transition hover:border-zinc-600 hover:bg-zinc-800">
                                        {{ __('Go') }}
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </form>

                @if ($discovery->rateLimitedFallback && filled($filters['q']))
                    <p class="rounded-[1.5rem] border border-amber-900/60 bg-amber-950/40 p-8 text-center text-sm leading-7 text-amber-200/90" role="status">
                        {{ __('Too many catalog lookups from Open Library. Please wait a moment and try again.') }}
                    </p>
                @elseif ($books->isNotEmpty())
                    <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
                        @foreach ($books as $book)
                            <a
                                href="{{ route('books.show', $book) }}"
                                class="group flex gap-4 rounded-[1.25rem] border border-zinc-800 bg-zinc-900/70 p-4 shadow-sm transition hover:border-white/40"
                            >
                                <div class="shrink-0">
                                    @if (filled($book->cover_url))
                                        <div class="overflow-hidden rounded-xl border border-zinc-800 bg-zinc-950">
                                            <img
                                                src="{{ $book->cover_url }}"
                                                alt=""
                                                class="h-28 w-20 object-cover sm:h-32 sm:w-[5.5rem]"
                                            />
                                        </div>
                                    @else
                                        <div class="flex h-28 w-20 items-center justify-center rounded-xl border border-zinc-800 bg-zinc-950 text-xs text-zinc-500 sm:h-32 sm:w-[5.5rem]">
                                            {{ __('No cover') }}
                                        </div>
                                    @endif
                                </div>
                                <div class="min-w-0 flex-1 space-y-2">
                                    <h2 class="text-sm font-semibold leading-snug text-white group-hover:underline">
                                        {{ $book->title }}
                                    </h2>
                                    @if (filled($displayAuthor($book)))
                                        <p class="text-xs text-zinc-400">{{ $displayAuthor($book) }}</p>
                                    @endif
                                    @if ($book->publish_year)
                                        <p class="text-xs text-zinc-500">{{ $book->publish_year }}</p>
                                    @endif
                                </div>
                            </a>
                        @endforeach
                    </div>

                    <div class="mt-4 text-zinc-400">
                        {{ $books->links() }}
                    </div>
                @elseif ($discovery->openLibraryItems !== [])
                    @if ($discovery->usedOpenLibraryFallback)
                        <p class="text-center text-xs text-zinc-500">
                            {{ __('No matches in our catalog yet. Showing results from Open Library (adding them to our catalog in the background).') }}
                        </p>
                    @endif
                    <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
                        @foreach ($discovery->openLibraryItems as $item)
                            <a
                                href="{{ $item->detailUrl }}"
                                @if ($item->source === 'open_library') target="_blank" rel="noopener noreferrer" @endif
                                class="group flex gap-4 rounded-[1.25rem] border border-zinc-800 bg-zinc-900/70 p-4 shadow-sm transition hover:border-white/40"
                            >
                                <div class="shrink-0">
                                    @if (filled($item->coverUrl))
                                        <div class="overflow-hidden rounded-xl border border-zinc-800 bg-zinc-950">
                                            <img
                                                src="{{ $item->coverUrl }}"
                                                alt=""
                                                class="h-28 w-20 object-cover sm:h-32 sm:w-[5.5rem]"
                                            />
                                        </div>
                                    @else
                                        <div class="flex h-28 w-20 items-center justify-center rounded-xl border border-zinc-800 bg-zinc-950 text-xs text-zinc-500 sm:h-32 sm:w-[5.5rem]">
                                            {{ __('No cover') }}
                                        </div>
                                    @endif
                                </div>
                                <div class="min-w-0 flex-1 space-y-2">
                                    <h2 class="text-sm font-semibold leading-snug text-white group-hover:underline">
                                        {{ $item->title }}
                                    </h2>
                                    @if (filled($item->author))
                                        <p class="text-xs text-zinc-400">{{ $item->author }}</p>
                                    @endif
                                    @if ($item->publishYear)
                                        <p class="text-xs text-zinc-500">{{ $item->publishYear }}</p>
                                    @endif
                                    @if ($item->source === 'open_library')
                                        <p class="text-[10px] font-semibold uppercase tracking-wider text-zinc-600">
                                            {{ __('Open Library') }}
                                        </p>
                                    @endif
                                </div>
                            </a>
                        @endforeach
                    </div>
                @else
                    <p class="rounded-[1.5rem] border border-zinc-800 bg-zinc-900/70 p-8 text-center text-sm leading-7 text-zinc-400">
                        {{ __('No books match those filters yet. Try a different search or filter.') }}
                    </p>
                @endif
            </main>
        </div>
    </body>
</html>
