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
    <body class="min-h-screen bg-zinc-950 text-zinc-100">
        <div class="relative overflow-hidden">
            <div class="absolute inset-0 -z-20 bg-zinc-950"></div>
            <div class="absolute inset-x-0 top-0 -z-10 h-[40rem] bg-linear-to-b from-zinc-900 via-zinc-950 to-zinc-950"></div>

            <header class="mx-auto flex w-full max-w-7xl items-center justify-between px-6 py-6 lg:px-8">
                <a href="{{ route('home') }}" class="flex items-center gap-3">
                    <span class="flex size-10 items-center justify-center rounded-2xl bg-white text-sm font-semibold text-zinc-950">
                        S
                    </span>
                    <span>
                        <span class="block text-sm font-semibold tracking-[0.2em] uppercase text-zinc-300">Signatr</span>
                        <span class="block text-sm text-zinc-400">Books, reactions, and what to read next.</span>
                    </span>
                </a>

                <nav class="flex items-center gap-3 text-sm">
                    @auth
                        <a href="{{ route('profile.edit') }}" class="inline-flex rounded-full border border-zinc-700 bg-zinc-900 px-4 py-2 font-medium text-white transition hover:border-zinc-600 hover:bg-zinc-800">
                            Account settings
                        </a>
                    @else
                        @if (Route::has('login'))
                            <a href="{{ route('login') }}" class="inline-flex rounded-full px-4 py-2 text-zinc-300 transition hover:bg-zinc-900 hover:text-white">
                                Log in
                            </a>
                        @endif

                        @if (Route::has('register'))
                            <a href="{{ route('register') }}" class="inline-flex rounded-full border border-white bg-white px-4 py-2 font-medium text-zinc-950 shadow-sm transition hover:bg-zinc-200">
                                Create account
                            </a>
                        @endif
                    @endauth
                </nav>
            </header>

            <main class="mx-auto flex w-full max-w-7xl flex-col gap-18 px-6 pb-20 pt-4 lg:px-8 lg:pb-28">
                <section id="featured-books" class="space-y-6">
                    <p class="text-sm font-semibold uppercase tracking-[0.25em] text-zinc-400">Featured books</p>

                    <div class="-mx-6 overflow-x-auto px-6 pb-3 lg:mx-0 lg:px-0">
                        <div class="flex gap-4 lg:grid lg:grid-cols-6">
                            @foreach ($featuredCovers as $cover)
                                <a
                                    href="{{ $cover['href'] }}"
                                    @if ($cover['external'] ?? true)
                                        target="_blank"
                                        rel="noreferrer"
                                    @endif
                                    class="group block min-w-[10rem] overflow-hidden rounded-xl border-3 border-zinc-800 bg-zinc-900 shadow-lg transition-colors duration-200 hover:border-white/60"
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
                        <p class="text-sm font-semibold uppercase tracking-[0.25em] text-zinc-400">What Signatr does</p>
                        <h2 class="text-3xl font-semibold tracking-tight text-white sm:text-4xl">A better homepage explains the product after it catches the eye.</h2>
                        <p class="text-base leading-7 text-zinc-400">
                            Once the books establish the mood, the next section should make the product obvious: what you can track, save, share, and discover inside Signatur.
                        </p>
                    </div>

                    <div class="grid gap-5 md:grid-cols-2 xl:grid-cols-3">
                        @foreach ($features as $feature)
                            <article class="rounded-[1.5rem] border border-zinc-800 bg-zinc-900/70 p-6 shadow-sm backdrop-blur">
                                <div class="mb-5 flex size-12 items-center justify-center rounded-2xl bg-zinc-800 text-sm font-semibold text-zinc-300">
                                    {{ $loop->iteration }}
                                </div>
                                <h3 class="text-xl font-semibold text-white">{{ $feature['title'] }}</h3>
                                <p class="mt-3 text-sm leading-7 text-zinc-400">{{ $feature['copy'] }}</p>
                            </article>
                        @endforeach
                    </div>
                </section>

                <section class="rounded-[2rem] border border-zinc-800 bg-zinc-900/80 p-8 shadow-sm lg:p-10">
                    <div class="flex flex-col gap-6 lg:flex-row lg:items-end lg:justify-between">
                        <div class="max-w-2xl space-y-3">
                            <p class="text-sm font-semibold uppercase tracking-[0.25em] text-zinc-400">Ready to get started?</p>
                            <h2 class="text-3xl font-semibold tracking-tight text-white sm:text-4xl">Make the first interaction feel like the beginning of a real reading profile.</h2>
                            <p class="text-base leading-7 text-zinc-400">
                                Sign up, start logging books, and let the homepage lead people straight into the kind of experience they were hoping to find.
                            </p>
                        </div>

                        <div class="flex flex-wrap gap-3">
                            @auth
                                <a href="{{ route('profile.edit') }}" class="inline-flex rounded-full border border-zinc-700 bg-zinc-900 px-5 py-3 text-sm font-medium text-white transition hover:border-zinc-600 hover:bg-zinc-800">
                                    Open settings
                                </a>
                            @else
                                <a href="{{ route('login') }}" class="inline-flex rounded-full px-5 py-3 text-sm font-medium text-zinc-300 transition hover:bg-zinc-900 hover:text-white">
                                    Log in
                                </a>

                                @if (Route::has('register'))
                                    <a href="{{ route('register') }}" class="inline-flex rounded-full border border-white bg-white px-5 py-3 text-sm font-medium text-zinc-950 transition hover:bg-zinc-200">
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
