@php
    $displayAuthor = static fn (\App\Models\Work $book): ?string => $book->displayAuthor();
    /** @var list<string>|null $alts */
    $alts = is_array($author->alternate_names) ? $author->alternate_names : null;
@endphp

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        @include('partials.head', ['title' => $title])
    </head>
    <body class="min-h-screen bg-surface-page text-text-default">
        <div class="relative">
            @include('partials.guest-header')

            <main class="mx-auto flex w-full max-w-7xl flex-col gap-10 px-6 pb-20 lg:px-8 lg:pb-28">
                <p class="text-sm">
                    <a href="{{ route('authors.index') }}" class="text-text-soft underline decoration-border-strong underline-offset-4 hover:text-text-default">
                        {{ __('All authors') }}
                    </a>
                </p>

                <header class="space-y-4 border-b border-border-subtle pb-6">
                    <h1 class="font-serif text-4xl font-semibold tracking-tight text-text-strong sm:text-5xl">
                        {{ $author->name }}
                    </h1>
                    @if ($alts !== null && $alts !== [])
                        <p class="text-sm text-text-muted">
                            <span class="font-medium text-text-soft">{{ __('Also known as') }}:</span>
                            {{ implode(', ', $alts) }}
                        </p>
                    @endif
                    @if (filled($author->bio))
                        <p class="max-w-3xl text-base leading-7 text-text-muted">
                            {{ \Illuminate\Support\Str::limit(\Illuminate\Support\Str::squish(strip_tags($author->bio)), 1200) }}
                        </p>
                    @endif
                    @if (filled($author->birth_date) || filled($author->death_date))
                        <p class="text-sm text-text-soft">
                            @if (filled($author->birth_date))
                                <span>{{ $author->birth_date }}</span>
                            @endif
                            @if (filled($author->birth_date) && filled($author->death_date))
                                <span> — </span>
                            @endif
                            @if (filled($author->death_date))
                                <span>{{ $author->death_date }}</span>
                            @endif
                        </p>
                    @endif
                </header>

                <section class="space-y-4">
                    <h2 class="text-xs font-semibold uppercase tracking-[0.2em] text-text-soft">
                        {{ __('Works') }}
                    </h2>
                    @if ($author->works->isEmpty())
                        <p class="rounded-[1.5rem] border border-border-subtle bg-surface-card p-8 text-center text-sm text-text-muted">
                            {{ __('No works linked to this author yet.') }}
                        </p>
                    @else
                        <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
                            @foreach ($author->works as $book)
                                <a
                                    href="{{ route('books.show', $book) }}"
                                    class="group flex gap-4 rounded-[1.25rem] border border-border-subtle bg-surface-card p-4 shadow-sm transition hover:border-border-strong"
                                >
                                    <div class="shrink-0">
                                        @if (filled($book->cover_url))
                                            <div class="overflow-hidden rounded-xl border border-border-subtle bg-surface-page-muted">
                                                <img
                                                    src="{{ $book->cover_url }}"
                                                    alt=""
                                                    class="h-28 w-20 object-cover sm:h-32 sm:w-[5.5rem]"
                                                />
                                            </div>
                                        @else
                                            <div class="flex h-28 w-20 items-center justify-center rounded-xl border border-border-subtle bg-surface-page-muted text-xs text-text-soft sm:h-32 sm:w-[5.5rem]">
                                                {{ __('No cover') }}
                                            </div>
                                        @endif
                                    </div>
                                    <div class="min-w-0 flex-1 space-y-2">
                                        <h3 class="text-sm font-semibold leading-snug text-text-strong group-hover:underline">
                                            {{ $book->title }}
                                        </h3>
                                        @if (filled($displayAuthor($book)))
                                            <p class="text-xs text-text-muted">{{ $displayAuthor($book) }}</p>
                                        @endif
                                        @if ($book->publish_year)
                                            <p class="text-xs text-text-soft">{{ $book->publish_year }}</p>
                                        @endif
                                    </div>
                                </a>
                            @endforeach
                        </div>
                    @endif
                </section>
            </main>
        </div>
    </body>
</html>
