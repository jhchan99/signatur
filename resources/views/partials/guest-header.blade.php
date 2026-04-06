@php
    /** @var string|null $subline */
    $subline = $subline ?? null;

    $logHref = auth()->check() ? route('books.index') : route('login');
@endphp

<header class="guest-header-band">
    <div class="guest-header-shell">
        <div class="flex flex-wrap items-center gap-x-4 gap-y-3 py-3">
            <div class="contents min-[1100px]:flex min-[1100px]:min-w-0 min-[1100px]:flex-1 min-[1100px]:items-center min-[1100px]:gap-x-5">
                <a href="{{ route('home') }}" class="nav-logo shrink-0 lowercase no-underline" wire:navigate>
                    <span class="font-sans font-medium">signa</span><span class="font-serif text-ui-gold">t</span><span class="font-sans font-medium">ur</span>
                </a>

                <nav class="guest-primary-nav order-3 flex w-full flex-none flex-wrap items-center justify-center gap-x-5 gap-y-2 min-[1100px]:order-none min-[1100px]:w-auto min-[1100px]:flex-1 min-[1100px]:justify-center" aria-label="{{ __('Primary') }}">
                    <a
                        href="{{ route('home') }}"
                        @class([request()->routeIs('home') ? 'nav-link-active' : 'nav-link'])
                        wire:navigate
                    >
                        {{ __('Home') }}
                    </a>
                    <a
                        href="{{ route('books.index') }}"
                        @class([request()->routeIs('books.*') ? 'nav-link-active' : 'nav-link'])
                        wire:navigate
                    >
                        {{ __('Books') }}
                    </a>
                    <a
                        href="{{ route('authors.index') }}"
                        @class([request()->routeIs('authors.*') ? 'nav-link-active' : 'nav-link'])
                        wire:navigate
                    >
                        {{ __('Authors') }}
                    </a>
                    <a
                        href="{{ route('collections.index') }}"
                        @class([request()->routeIs('collections.*') ? 'nav-link-active' : 'nav-link'])
                        wire:navigate
                    >
                        {{ __('Collections') }}
                    </a>
                </nav>
            </div>

            <div
                class="min-w-0 w-full max-w-sm flex-1 basis-full min-[1100px]:order-none min-[1100px]:w-56 min-[1100px]:max-w-xs min-[1100px]:flex-none min-[1100px]:basis-auto"
                data-test="header-global-search"
            >
                @include('partials.global-search-form', [
                    'value' => $globalSearchQuery ?? request('q', ''),
                    'compact' => true,
                ])
            </div>

            <div class="ms-auto flex shrink-0 flex-wrap items-center justify-end gap-2">
                @auth
                    <a href="{{ route('profile.edit') }}" class="btn-secondary rounded-tag px-4 py-2 text-meta no-underline" wire:navigate>
                        {{ __('Account settings') }}
                    </a>
                @else
                    @if (Route::has('register'))
                        <a href="{{ route('register') }}" class="btn-ghost rounded-tag px-3 py-2 no-underline">
                            {{ __('Create account') }}
                        </a>
                    @endif
                @endauth

                <a href="{{ $logHref }}" class="btn-log whitespace-nowrap no-underline" wire:navigate>
                    {{ __('+ Log') }}
                </a>
            </div>
        </div>

        @if (filled($subline))
            <p class="text-center text-xs text-ui-faint lg:text-left">
                {{ $subline }}
            </p>
        @endif
    </div>
</header>
