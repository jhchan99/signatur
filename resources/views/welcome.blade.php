@php
    $features = [
        [
            'title' => 'Track the books you finish',
            'copy' => 'Keep a running history of what you have read so your taste builds up over time instead of disappearing into notes apps and screenshots.',
        ],
        [
            'title' => 'Save the books you want next',
            'copy' => 'Build a clean reading queue the moment a title catches your eye, then come back to it when you are ready for your next pick.',
        ],
        [
            'title' => 'Leave quick reactions',
            'copy' => 'Capture what hit, what missed, and whether you would recommend it without forcing every thought into a full review.',
        ],
        [
            'title' => 'Find your next read through people',
            'copy' => 'Discovery feels better when it comes from other readers with taste, not a giant wall of disconnected metadata.',
        ],
        [
            'title' => 'Turn taste into a profile',
            'copy' => 'Your books, reactions, and lists become a clearer picture of your reading life and the stories you keep chasing.',
        ],
        [
            'title' => 'Keep the homepage useful',
            'copy' => 'The landing page should immediately show the kind of books and product experience someone can expect inside Signatur.',
        ],
    ];
    $heroCovers = collect($featuredCovers)->take(5)->values();
@endphp

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        @include('partials.head')
    </head>
    <body class="min-h-screen bg-page text-ui-primary antialiased">
        <div class="relative overflow-hidden">
            @include('partials.guest-header')

            <main class="guest-page-main mx-auto flex w-full max-w-7xl flex-col gap-18 px-6 pb-20 lg:px-8 lg:pb-28">
                <section class="relative py-8 sm:py-10 lg:py-14">
                    <!-- <div class="pointer-events-none absolute inset-x-0 top-0 hidden border-t border-ui lg:block"></div> -->
                    <!-- <div class="pointer-events-none absolute left-0 top-0 hidden h-20 border-s border-ui lg:block"></div> -->

                    <div class="grid gap-8 lg:grid-cols-[minmax(0,1.1fr)_minmax(0,0.9fr)] lg:items-center lg:gap-12">
                        <div class="max-w-2xl">
                            <h1 class="mt-3 font-serif text-3xl font-medium leading-tight text-ui-primary sm:text-4xl lg:text-5xl">
                                The cozy social network.
                            </h1>
                            <p class="mt-4 max-w-xl text-sm leading-6 text-ui-muted sm:text-base">
                                Find your next adventure. Tell your friends about it.
                            </p>

                            <div class="mt-6 flex flex-wrap gap-3">
                                @guest
                                    <a href="{{ route('register') }}" class="btn-primary rounded-tag px-5 py-3 text-sm no-underline">
                                        {{ __('Create account') }}
                                    </a>
                                    <a href="{{ route('login') }}" class="btn-ghost rounded-tag px-5 py-3 text-sm no-underline">
                                        {{ __('Log in') }}
                                    </a>
                                @else
                                    <a href="{{ route('books.index') }}" class="btn-primary rounded-tag px-5 py-3 text-sm no-underline" wire:navigate>
                                        {{ __('Browse books') }}
                                    </a>
                                @endguest
                            </div>
                        </div>

                        <div class="grid grid-cols-3 gap-3 sm:gap-4 lg:grid-cols-4">
                            @foreach ($heroCovers as $cover)
                                <a
                                    href="{{ $cover['href'] }}"
                                    @if ($cover['external'] ?? true)
                                        target="_blank"
                                        rel="noreferrer"
                                    @endif
                                    @class([
                                        'group block overflow-hidden rounded-cover shadow-cover transition-transform duration-150',
                                        'translate-y-6' => $loop->index === 1,
                                        '-translate-y-3' => $loop->index === 2,
                                        'translate-y-8 lg:translate-y-10' => $loop->last,
                                        'hidden lg:block' => $loop->index === 4,
                                    ])
                                >
                                    <img
                                        src="{{ $cover['card_image'] ?? $cover['image'] }}"
                                        @if (($cover['image'] ?? '') !== ($cover['card_image'] ?? ''))
                                            srcset="{{ $cover['image'] }} 2x"
                                        @endif
                                        alt="{{ $cover['title'] }}"
                                        class="aspect-[2/3] h-full w-full object-cover"
                                    />
                                </a>
                            @endforeach
                        </div>
                    </div>
                </section>

                <section id="featured-books" class="space-y-6">
                    <div class="max-w-2xl space-y-3">
                        <p class="section-label">Featured books</p>
                    </div>

                    <div class="-mx-6 overflow-x-auto px-6 pb-3 lg:mx-0 lg:px-0">
                        <div class="flex gap-4 lg:grid lg:grid-cols-6">
                            @foreach ($featuredCovers as $cover)
                                <a
                                    href="{{ $cover['href'] }}"
                                    @if ($cover['external'] ?? true)
                                        target="_blank"
                                        rel="noreferrer"
                                    @endif
                                    class="group block min-w-[10rem] overflow-hidden rounded-card border-[3px] border-ui transition-colors duration-150 hover:border-gold-dark focus-visible:border-gold-dark dark:hover:border-gold dark:focus-visible:border-gold"
                                >
                                    <img
                                        src="{{ $cover['card_image'] ?? $cover['image'] }}"
                                        @if (($cover['image'] ?? '') !== ($cover['card_image'] ?? ''))
                                            srcset="{{ $cover['image'] }} 2x"
                                        @endif
                                        alt="{{ $cover['title'] }}"
                                        class="aspect-[2/3] h-full w-full object-cover"
                                    />
                                </a>
                            @endforeach
                        </div>
                    </div>
                </section>

                <section class="space-y-8">
                    <div class="max-w-2xl space-y-3">
                        <p class="section-label">What Signatur does</p>
                        <h2 class="display-heading text-3xl sm:text-4xl">A better homepage explains the product after it catches the eye.</h2>
                        <p class="book-author !mt-0 max-w-prose text-base leading-7 !text-ui-muted">
                            Once the books establish the mood, the next section should make the product obvious: what you can track, save, share, and discover inside Signatur.
                        </p>
                    </div>

                    <div class="grid gap-5 md:grid-cols-2 xl:grid-cols-3">
                        @foreach ($features as $feature)
                            <article class="card rounded-[1.25rem]">
                                <div class="mb-5 flex size-12 items-center justify-center rounded-2xl border border-ui bg-surface text-sm font-semibold text-ui-primary">
                                    {{ $loop->iteration }}
                                </div>
                                <h3 class="book-title text-xl">{{ $feature['title'] }}</h3>
                                <p class="book-author mt-3 leading-7 !text-ui-muted">{{ $feature['copy'] }}</p>
                            </article>
                        @endforeach
                    </div>
                </section>

                <section class="card rounded-[1.25rem] p-8 lg:p-10">
                    <div class="flex flex-col gap-6 lg:flex-row lg:items-end lg:justify-between">
                        <div class="max-w-2xl space-y-3">
                            <p class="section-label">Ready to get started?</p>
                            <h2 class="display-heading text-3xl sm:text-4xl">Make the first interaction feel like the beginning of a real reading profile.</h2>
                            <p class="book-author !mt-0 text-base leading-7 !text-ui-muted">
                                Sign up, start logging books, and let the homepage lead people straight into the kind of experience they were hoping to find.
                            </p>
                        </div>

                        <div class="flex flex-wrap gap-3">
                            @auth
                                <a href="{{ route('profile.edit') }}" class="btn-secondary rounded-tag px-5 py-3 text-sm no-underline" wire:navigate>
                                    {{ __('Open settings') }}
                                </a>
                            @else
                                <a href="{{ route('login') }}" class="btn-ghost rounded-tag px-5 py-3 text-sm no-underline">
                                    {{ __('Log in') }}
                                </a>

                                @if (Route::has('register'))
                                    <a href="{{ route('register') }}" class="btn-primary rounded-tag px-5 py-3 text-sm no-underline">
                                        {{ __('Create account') }}
                                    </a>
                                @endif
                            @endauth
                        </div>
                    </div>
                </section>
            </main>
        </div>
    </body>
</html>
