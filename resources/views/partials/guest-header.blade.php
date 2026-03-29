@php
    /** @var string $subline */
    $subline = $subline ?? 'Books, reactions, and what to read next.';
@endphp

<header class="mx-auto flex w-full max-w-7xl flex-wrap items-center justify-between gap-4 px-6 py-6 lg:px-8">
    <a href="{{ route('home') }}" class="flex items-center gap-3">
        <span class="flex size-10 items-center justify-center rounded-2xl bg-white text-sm font-semibold text-zinc-950">
            S
        </span>
        <span>
            <span class="block text-sm font-semibold tracking-[0.2em] uppercase text-zinc-300">Signatr</span>
        </span>
    </a>

    @guest
        <div class="order-3 flex w-full justify-center lg:order-none lg:flex-1 lg:justify-center">
            <div
                class="inline-flex rounded-full border border-zinc-800 bg-zinc-900/80 p-1 text-xs font-semibold text-zinc-300"
                role="tablist"
                aria-label="Navigation"
            >
                <span
                    class="select-none rounded-full px-4 py-2 text-zinc-500"
                    role="tab"
                    aria-selected="false"
                    aria-disabled="true"
                    tabindex="-1"
                >
                    Home 
                </span>
                <span
                    class="select-none rounded-full px-4 py-2 text-zinc-500"
                    role="tab"
                    aria-selected="false"
                    aria-disabled="true"
                    tabindex="-1"
                >
                    Books
                </span>
                <span
                    class="select-none rounded-full px-4 py-2 text-zinc-500"
                    role="tab"
                    aria-selected="false"
                    aria-disabled="true"
                    tabindex="-1"
                >
                    Collections
                </span>
            </div>
        </div>
    @endguest

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
