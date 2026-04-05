@php
    $searchValue = $value ?? request('q', '');
    $compact = $compact ?? false;
    $emphasized = $emphasized ?? false;
    $theme = $theme ?? 'light';
    $isLightTheme = $theme !== 'dark';

    if ($compact) {
        $formClass = 'flex w-full min-w-0 items-center gap-2';
        $inputClass = $isLightTheme
            ? 'min-w-0 flex-1 rounded-md border border-border-subtle bg-surface-card px-2 py-1.5 text-xs text-text-default placeholder:text-text-soft outline-none focus:border-border-strong'
            : 'min-w-0 flex-1 rounded-md border border-zinc-700 bg-zinc-900 px-2 py-1.5 text-xs text-white placeholder:text-zinc-500 outline-none focus:border-zinc-500 dark:border-zinc-600 dark:bg-zinc-950';
        $buttonClass = $isLightTheme
            ? 'shrink-0 rounded-md border border-border-subtle bg-surface-card-strong px-2 py-1.5 text-[10px] font-semibold uppercase tracking-wider text-text-strong hover:bg-white-smoke-200'
            : 'shrink-0 rounded-md border border-zinc-600 bg-zinc-800 px-2 py-1.5 text-[10px] font-semibold uppercase tracking-wider text-zinc-100 hover:bg-zinc-700 dark:border-zinc-600';
    } elseif ($emphasized) {
        $formClass = 'mx-auto flex w-full max-w-3xl items-center gap-3 lg:mx-0';
        $inputClass = $isLightTheme
            ? 'min-w-0 flex-1 rounded-lg border border-border-subtle bg-surface-card px-4 py-3 text-base text-text-default placeholder:text-text-soft outline-none focus:border-border-strong'
            : 'min-w-0 flex-1 rounded-lg border border-zinc-700 bg-zinc-900 px-4 py-3 text-base text-white placeholder:text-zinc-500 outline-none focus:border-zinc-500 dark:border-zinc-600 dark:bg-zinc-950';
        $buttonClass = $isLightTheme
            ? 'shrink-0 rounded-lg border border-action-primary bg-action-primary px-5 py-3 text-xs font-semibold uppercase tracking-wider text-text-inverse hover:border-action-primary-hover hover:bg-action-primary-hover'
            : 'shrink-0 rounded-lg border border-zinc-600 bg-zinc-800 px-5 py-3 text-xs font-semibold uppercase tracking-wider text-zinc-100 hover:bg-zinc-700 dark:border-zinc-600';
    } else {
        $formClass = 'mx-auto flex w-full max-w-xl items-center gap-2 lg:mx-0';
        $inputClass = $isLightTheme
            ? 'min-w-0 flex-1 rounded-md border border-border-subtle bg-surface-card px-3 py-2 text-sm text-text-default placeholder:text-text-soft outline-none focus:border-border-strong'
            : 'min-w-0 flex-1 rounded-md border border-zinc-800 bg-zinc-950 px-3 py-2 text-sm text-white placeholder:text-zinc-600 outline-none focus:border-zinc-500';
        $buttonClass = $isLightTheme
            ? 'shrink-0 rounded-md border border-border-subtle bg-surface-card-strong px-3 py-2 text-xs font-semibold uppercase tracking-wider text-text-strong hover:border-border-strong hover:bg-white-smoke-200'
            : 'shrink-0 rounded-md border border-zinc-700 bg-zinc-900 px-3 py-2 text-xs font-semibold uppercase tracking-wider text-zinc-200 hover:border-zinc-600 hover:bg-zinc-800';
    }
@endphp

<form
    method="get"
    action="{{ route('search.index') }}"
    class="{{ $formClass }}"
    role="search"
    data-test="global-search-form"
>
    <label class="sr-only" for="{{ $compact ? 'global-search-q-compact' : 'global-search-q' }}">{{ __('Search') }}</label>
    <input
        id="{{ $compact ? 'global-search-q-compact' : 'global-search-q' }}"
        type="search"
        name="q"
        value="{{ $searchValue }}"
        placeholder="{{ $compact ? __('Search books and authors') : __('Titles, authors, subjects…') }}"
        class="{{ $inputClass }}"
        autocomplete="off"
    />
    <button
        type="submit"
        class="{{ $buttonClass }}"
    >
        {{ __('Search') }}
    </button>
</form>
