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
            'copy' => 'The landing page should immediately show the kind of books and product experience someone can expect inside Signatr.',
        ],
    ];
@endphp

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        @include('partials.head')
    </head>
    <body class="min-h-screen bg-surface-page text-text-default">
        <div class="relative overflow-hidden">
            <div class="absolute inset-0 -z-20 bg-surface-page"></div>
            <div class="absolute inset-x-0 top-0 -z-10 h-[40rem] bg-linear-to-b from-surface-page-muted via-surface-page to-surface-page"></div>

            @include('partials.guest-header', ['theme' => 'homepage-light'])

            <main class="mx-auto flex w-full max-w-7xl flex-col gap-18 px-6 pb-20 pt-4 lg:px-8 lg:pb-28">
                <section class="relative overflow-hidden rounded-[2rem] border border-border-subtle bg-surface-card shadow-sm">
                    <div class="relative h-[17rem] w-full overflow-hidden sm:h-[22rem] lg:h-[26rem]">
                        <img
                            src="{{ asset('images/home-hero3.png') }}"
                            alt="A person reading in a cozy chair"
                            class="absolute inset-0 h-full w-full scale-105 object-cover object-center blur-sm"
                        />
                    </div>
                    <div class="pointer-events-none absolute inset-0 bg-linear-to-t from-ink-black-950/80 via-ink-black-950/35 to-ink-black-950/5"></div>

                    <div class="absolute inset-x-0 bottom-0 p-6 sm:p-8 lg:p-10">
                        <p class="text-xs font-semibold uppercase tracking-[0.28em] text-white-smoke-100">Signatur</p>
                        <h1 class="mt-3 max-w-2xl text-3xl font-semibold leading-tight text-white sm:text-4xl lg:text-5xl">
                            Where readers chat.
                        </h1>
                        <p class="mt-3 max-w-xl text-sm leading-6 text-white-smoke-50 sm:text-base">
                            Build your reading profile with books you love, books you want next, and reactions that capture your taste.
                        </p>
                    </div>
                </section>

                <section id="featured-books" class="space-y-6">
                    <p class="text-sm font-semibold uppercase tracking-[0.25em] text-text-soft">Featured books</p>

                    <div class="-mx-6 overflow-x-auto px-6 pb-3 lg:mx-0 lg:px-0">
                        <div class="flex gap-4 lg:grid lg:grid-cols-6">
                            @foreach ($featuredCovers as $cover)
                                <a
                                    href="{{ $cover['href'] }}"
                                    @if ($cover['external'] ?? true)
                                        target="_blank"
                                        rel="noreferrer"
                                    @endif
                                    class="group block min-w-[10rem] overflow-hidden rounded-xl border-3 border-border-subtle bg-surface-card shadow-lg transition-colors duration-200 hover:border-border-strong"
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
                        <p class="text-sm font-semibold uppercase tracking-[0.25em] text-text-soft">What Signatr does</p>
                        <h2 class="text-3xl font-semibold tracking-tight text-text-strong sm:text-4xl">A better homepage explains the product after it catches the eye.</h2>
                        <p class="text-base leading-7 text-text-muted">
                            Once the books establish the mood, the next section should make the product obvious: what you can track, save, share, and discover inside Signatur.
                        </p>
                    </div>

                    <div class="grid gap-5 md:grid-cols-2 xl:grid-cols-3">
                        @foreach ($features as $feature)
                            <article class="rounded-[1.5rem] border border-border-subtle bg-surface-card p-6 shadow-sm backdrop-blur">
                                <div class="mb-5 flex size-12 items-center justify-center rounded-2xl bg-surface-card-strong text-sm font-semibold text-text-strong">
                                    {{ $loop->iteration }}
                                </div>
                                <h3 class="text-xl font-semibold text-text-strong">{{ $feature['title'] }}</h3>
                                <p class="mt-3 text-sm leading-7 text-text-muted">{{ $feature['copy'] }}</p>
                            </article>
                        @endforeach
                    </div>
                </section>

                <section class="rounded-[2rem] border border-border-subtle bg-surface-card p-8 shadow-sm lg:p-10">
                    <div class="flex flex-col gap-6 lg:flex-row lg:items-end lg:justify-between">
                        <div class="max-w-2xl space-y-3">
                            <p class="text-sm font-semibold uppercase tracking-[0.25em] text-text-soft">Ready to get started?</p>
                            <h2 class="text-3xl font-semibold tracking-tight text-text-strong sm:text-4xl">Make the first interaction feel like the beginning of a real reading profile.</h2>
                            <p class="text-base leading-7 text-text-muted">
                                Sign up, start logging books, and let the homepage lead people straight into the kind of experience they were hoping to find.
                            </p>
                        </div>

                        <div class="flex flex-wrap gap-3">
                            @auth
                                <a href="{{ route('profile.edit') }}" class="inline-flex rounded-full border border-border-strong bg-surface-page px-5 py-3 text-sm font-medium text-text-strong transition hover:bg-surface-page-muted">
                                    Open settings
                                </a>
                            @else
                                <a href="{{ route('login') }}" class="inline-flex rounded-full px-5 py-3 text-sm font-medium text-text-muted transition hover:bg-surface-card hover:text-text-strong">
                                    Log in
                                </a>

                                @if (Route::has('register'))
                                    <a href="{{ route('register') }}" class="inline-flex rounded-full border border-action-primary bg-action-primary px-5 py-3 text-sm font-medium text-surface-page transition hover:border-action-primary-hover hover:bg-action-primary-hover">
                                        Create account
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
