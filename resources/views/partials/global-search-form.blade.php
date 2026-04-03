@php
    $searchValue = $value ?? request('q', '');
    $compact = $compact ?? false;
@endphp

<form
    method="get"
    action="{{ route('search.index') }}"
    class="{{ $compact
        ? 'flex w-full min-w-0 items-center gap-2'
        : 'mx-auto flex w-full max-w-xl items-center gap-2 lg:mx-0' }}"
    role="search"
    data-test="global-search-form"
>
    <label class="sr-only" for="{{ $compact ? 'global-search-q-compact' : 'global-search-q' }}">{{ __('Search') }}</label>
    <input
        id="{{ $compact ? 'global-search-q-compact' : 'global-search-q' }}"
        type="search"
        name="q"
        value="{{ $searchValue }}"
        placeholder="{{ __('Search books and authors') }}"
        class="{{ $compact
            ? 'min-w-0 flex-1 rounded-md border border-zinc-700 bg-zinc-900 px-2 py-1.5 text-xs text-white placeholder:text-zinc-500 outline-none focus:border-zinc-500 dark:border-zinc-600 dark:bg-zinc-950'
            : 'min-w-0 flex-1 rounded-md border border-zinc-800 bg-zinc-950 px-3 py-2 text-sm text-white placeholder:text-zinc-600 outline-none focus:border-zinc-500' }}"
        autocomplete="off"
    />
    <button
        type="submit"
        class="{{ $compact
            ? 'shrink-0 rounded-md border border-zinc-600 bg-zinc-800 px-2 py-1.5 text-[10px] font-semibold uppercase tracking-wider text-zinc-100 hover:bg-zinc-700 dark:border-zinc-600'
            : 'shrink-0 rounded-md border border-zinc-700 bg-zinc-900 px-3 py-2 text-xs font-semibold uppercase tracking-wider text-zinc-200 hover:border-zinc-600 hover:bg-zinc-800' }}"
    >
        {{ __('Search') }}
    </button>
</form>
