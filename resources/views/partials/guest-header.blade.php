@php
    /** @var string|null $subline */
    $subline = $subline ?? null;

    $tabBase =
        'inline-flex select-none rounded-full px-4 py-2 transition focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-white';
    $tabActive = 'bg-white text-zinc-950 shadow-sm';
    $tabInactive = 'text-zinc-300 hover:bg-zinc-800 hover:text-white';
@endphp

<header class="mx-auto flex w-full max-w-7xl flex-col gap-4 px-6 py-6 lg:px-8">
    <div class="flex flex-wrap items-center gap-x-3 gap-y-3">
        <a href="{{ route('home') }}" class="flex shrink-0 items-center gap-3">
            <span class="flex size-10 items-center justify-center rounded-2xl bg-white text-sm font-semibold text-zinc-950">
                S
            </span>
            <span>
                <span class="block text-sm font-semibold tracking-[0.2em] uppercase text-zinc-300">Signatr</span>
            </span>
        </a>

        @guest
            <div class="flex min-w-0 flex-1 basis-full justify-center min-[1024px]:basis-0 min-[1024px]:justify-center">
                <div
                    class="inline-flex rounded-full border border-zinc-800 bg-zinc-900/80 p-1 text-xs font-semibold text-zinc-300"
                    role="tablist"
                    aria-label="Navigation"
                >
                    <a
                        href="{{ route('home') }}"
                        @class([$tabBase, request()->routeIs('home') ? $tabActive : $tabInactive])
                        role="tab"
                        aria-selected="{{ request()->routeIs('home') ? 'true' : 'false' }}"
                    >
                        Home
                    </a>
                    <a
                        href="{{ route('books.index') }}"
                        @class([$tabBase, request()->routeIs('books.*') ? $tabActive : $tabInactive])
                        role="tab"
                        aria-selected="{{ request()->routeIs('books.*') ? 'true' : 'false' }}"
                    >
                        Books
                    </a>
                    <a
                        href="{{ route('authors.index') }}"
                        @class([$tabBase, request()->routeIs('authors.*') ? $tabActive : $tabInactive])
                        role="tab"
                        aria-selected="{{ request()->routeIs('authors.*') ? 'true' : 'false' }}"
                    >
                        Authors
                    </a>
                    <a
                        href="{{ route('collections.index') }}"
                        @class([$tabBase, request()->routeIs('collections.*') ? $tabActive : $tabInactive])
                        role="tab"
                        aria-selected="{{ request()->routeIs('collections.*') ? 'true' : 'false' }}"
                    >
                        Collections
                    </a>
                </div>
            </div>
        @endguest

        <div class="min-w-0 w-full max-w-sm flex-1 basis-full min-[1024px]:w-56 min-[1024px]:max-w-xs min-[1024px]:flex-none min-[1024px]:basis-auto" data-test="header-global-search">
            @include('partials.global-search-form', [
                'value' => $globalSearchQuery ?? request('q', ''),
                'compact' => true,
            ])
        </div>

        <nav class="ms-auto flex shrink-0 items-center gap-3 text-sm">
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
    </div>

    @if (filled($subline))
        <p class="text-center text-xs text-zinc-500 lg:text-left">
            {{ $subline }}
        </p>
    @endif
</header>
