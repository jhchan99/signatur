@php
    /** @var \App\Data\GlobalSearchResult $result */
    $displayAuthor = static fn (\App\Models\Work $book): ?string => $book->primaryAuthorName();
@endphp

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        @include('partials.head', ['title' => $title])
    </head>
    <body class="min-h-screen bg-page text-ui-primary antialiased">
        <div class="relative">
            @include('partials.guest-header', ['globalSearchQuery' => $query ?? ''])

            <main class="guest-page-main mx-auto flex w-full max-w-7xl flex-col gap-8 px-6 pb-20 lg:px-8 lg:pb-28">
                <div class="border-b border-ui pb-6">
                    <h1 class="text-[10px] font-semibold uppercase tracking-[0.2em] text-ui-faint">
                        {{ __('Search') }}
                    </h1>
                    <p class="mt-2 text-sm text-ui-muted">
                        {{ __('Results from our catalog only.') }}
                    </p>
                </div>

                <div class="flex flex-col gap-4">
                    @include('partials.global-search-form', [
                        'value' => $query ?? '',
                        'emphasized' => true,
                    ])
                </div>

                @if (($query ?? null) === null || $query === '')
                    <p class="rounded-[1.5rem] border border-ui bg-surface p-8 text-center text-sm leading-7 text-ui-muted">
                        {{ __('Search by title, subtitle, subject tags, or author—including alternate names.') }}
                    </p>
                @elseif (! $result->hasAnyResults())
                    <p class="rounded-[1.5rem] border border-ui bg-surface p-8 text-center text-sm leading-7 text-ui-muted">
                        {{ __('No books or authors match that search yet. Try a different term.') }}
                    </p>
                @else
                    @if ($result->books->isNotEmpty())
                        <section class="space-y-4">
                            <h2 class="text-xs font-semibold uppercase tracking-[0.2em] text-ui-faint">
                                {{ __('Books') }}
                            </h2>
                            <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
                                @foreach ($result->books as $book)
                                    <a
                                        href="{{ route('books.show', $book) }}"
                                        class="group flex gap-4 rounded-[1.25rem] border border-ui bg-surface p-4 shadow-sm transition hover:border-parchment-4 dark:hover:border-ink-4"
                                    >
                                        <div class="shrink-0">
                                            @if (filled($book->cover_url))
                                                <div class="overflow-hidden rounded-xl border border-ui bg-page">
                                                    <img
                                                        src="{{ $book->cover_url }}"
                                                        alt=""
                                                        class="h-28 w-20 object-cover sm:h-32 sm:w-[5.5rem]"
                                                    />
                                                </div>
                                            @else
                                                <div class="flex h-28 w-20 items-center justify-center rounded-xl border border-ui bg-page text-xs text-ui-faint sm:h-32 sm:w-[5.5rem]">
                                                    {{ __('No cover') }}
                                                </div>
                                            @endif
                                        </div>
                                        <div class="min-w-0 flex-1 space-y-2">
                                            <h3 class="text-sm font-semibold leading-snug text-ui-primary group-hover:underline">
                                                {{ $book->title }}
                                            </h3>
                                            @if (filled($displayAuthor($book)))
                                                <p class="text-xs text-ui-muted">{{ $displayAuthor($book) }}</p>
                                            @endif
                                            @if ($book->publish_year)
                                                <p class="text-xs text-ui-faint">{{ $book->publish_year }}</p>
                                            @endif
                                        </div>
                                    </a>
                                @endforeach
                            </div>
                        </section>
                    @endif

                    @if ($result->authors->isNotEmpty())
                        <section class="space-y-4">
                            <h2 class="text-xs font-semibold uppercase tracking-[0.2em] text-ui-faint">
                                {{ __('Authors') }}
                            </h2>
                            <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                                @foreach ($result->authors as $author)
                                    <a
                                        href="{{ route('authors.show', $author) }}"
                                        class="group rounded-[1.25rem] border border-ui bg-surface p-4 shadow-sm transition hover:border-parchment-4 dark:hover:border-ink-4"
                                    >
                                        <h3 class="text-sm font-semibold text-ui-primary group-hover:underline">
                                            {{ $author->name }}
                                        </h3>
                                        @php
                                            /** @var list<string>|null $alts */
                                            $alts = is_array($author->alternate_names) ? $author->alternate_names : null;
                                        @endphp
                                        @if ($alts !== null && $alts !== [])
                                            <p class="mt-2 text-xs text-ui-faint">
                                                {{ __('Also known as') }}: {{ implode(', ', $alts) }}
                                            </p>
                                        @endif
                                    </a>
                                @endforeach
                            </div>
                        </section>
                    @endif
                @endif
            </main>
        </div>
    </body>
</html>
