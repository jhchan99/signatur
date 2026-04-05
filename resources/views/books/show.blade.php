@php
    $excerpt = $book->description
        ? \Illuminate\Support\Str::limit(\Illuminate\Support\Str::squish(strip_tags($book->description)), 900)
        : null;
    $displayAuthor = $book->primaryAuthorName();

    /** @var list<string> $subjectTags */
    $subjectTags = is_array($book->subjects) ? $book->subjects : [];
@endphp

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        @include('partials.head', ['title' => $book->title])
    </head>
    <body class="min-h-screen bg-surface-page text-text-default">
        <div class="relative">
            @include('partials.guest-header')

            <main class="mx-auto flex w-full max-w-7xl flex-col gap-10 px-6 pb-20 lg:px-8 lg:pb-28">
                <p class="text-sm">
                    <a href="{{ route('books.index') }}" class="text-text-soft underline decoration-border-strong underline-offset-4 hover:text-text-default">
                        {{ __('Back to books') }}
                    </a>
                </p>
                <article class="grid gap-10 lg:grid-cols-[18rem_minmax(0,1fr)] lg:items-start">
                    <div class="mx-auto w-full max-w-xs lg:mx-0">
                        @if (filled($book->cover_url))
                            <div class="overflow-hidden rounded-2xl border border-border-subtle bg-surface-card shadow-lg">
                                <img
                                    src="{{ $book->cover_url }}"
                                    alt=""
                                    class="aspect-[2/3] w-full object-cover"
                                />
                            </div>
                        @else
                            <div class="flex aspect-[2/3] w-full items-center justify-center rounded-2xl border border-border-subtle bg-surface-card text-sm text-text-soft">
                                No cover
                            </div>
                        @endif
                    </div>

                    <div class="min-w-0 space-y-4">
                        <div class="flex flex-wrap items-baseline gap-x-4 gap-y-2">
                            <h1 class="font-serif text-4xl font-semibold tracking-tight text-text-strong sm:text-5xl">
                                {{ $book->title }}
                            </h1>
                            @if ($book->publish_year)
                                <span class="text-lg text-text-muted">{{ $book->publish_year }}</span>
                            @endif
                        </div>

                        @if (filled($displayAuthor))
                            <p class="text-base text-text-muted">{{ $displayAuthor }}</p>
                        @endif

                        @if ($excerpt !== null && $excerpt !== '')
                            <p class="text-base leading-7 text-text-muted">
                                {{ $excerpt }}
                            </p>
                        @endif
                    </div>
                </article>

                <section class="rounded-[1.5rem] border border-border-subtle bg-surface-card p-6 shadow-sm backdrop-blur lg:p-8">
                    <h2 class="text-sm font-semibold uppercase tracking-[0.25em] text-text-soft">Details</h2>
                    <dl class="mt-6 space-y-4 text-sm">
                        @if ($book->publish_year)
                            <div class="flex flex-col gap-1 sm:flex-row sm:gap-6">
                                <dt class="shrink-0 font-medium text-text-soft sm:w-32">Published</dt>
                                <dd class="text-text-default">{{ $book->publish_year }}</dd>
                            </div>
                        @endif

                        @if ($subjectTags !== [])
                            <div class="flex flex-col gap-3 sm:flex-row sm:gap-6">
                                <dt class="shrink-0 font-medium text-text-soft sm:w-32 sm:pt-1">Subjects</dt>
                                <dd class="flex flex-wrap gap-2">
                                    @foreach ($subjectTags as $tag)
                                        <span class="rounded-full border border-border-subtle bg-surface-page-muted px-3 py-1 text-xs font-medium text-text-default">
                                            {{ $tag }}
                                        </span>
                                    @endforeach
                                </dd>
                            </div>
                        @endif

                        @if (! $book->publish_year && $subjectTags === [])
                            <p class="text-text-soft">More catalog metadata will show here once this title is enriched.</p>
                        @endif
                    </dl>
                </section>

                <section class="rounded-[1.5rem] border border-border-subtle bg-surface-card p-6 shadow-sm backdrop-blur lg:p-8">
                    <div class="flex items-center justify-between gap-4">
                        <h2 class="text-sm font-semibold uppercase tracking-[0.25em] text-text-soft">Reviews</h2>
                    </div>

                    @if ($reviews->isEmpty())
                        <p class="mt-6 text-sm leading-7 text-text-soft">
                            No public reviews yet. When readers share their thoughts (and keep them public), they will show up here.
                        </p>
                    @else
                        <ul class="mt-6 divide-y divide-border-subtle">
                            @foreach ($reviews as $review)
                                <li class="py-6 first:pt-0 last:pb-0">
                                    <div class="flex flex-wrap items-center gap-3">
                                        <p class="text-sm font-medium text-text-strong">
                                            Review by {{ $review->user->display_name ?? $review->user->name }}
                                        </p>
                                        @if ($review->rating !== null)
                                            <span class="text-sm text-emerald-400">{{ number_format((float) $review->rating, 1) }} / 5</span>
                                        @endif
                                        @if ($review->is_spoiler)
                                            <span class="rounded-full bg-amber-500/15 px-2 py-0.5 text-xs font-semibold text-amber-300">
                                                Spoilers
                                            </span>
                                        @endif
                                    </div>
                                    <p class="mt-3 text-sm leading-7 text-text-muted">
                                        {{ $review->review_text }}
                                    </p>
                                </li>
                            @endforeach
                        </ul>
                    @endif
                </section>
            </main>
        </div>
    </body>
</html>
