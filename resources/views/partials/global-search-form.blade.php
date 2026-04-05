@php
    $searchValue = $value ?? request('q', '');
    $compact = $compact ?? false;
    $emphasized = $emphasized ?? false;

    if ($compact) {
        $formClass = 'flex w-full min-w-0 items-center gap-2';
        $inputClass =
            'input min-w-0 flex-1 rounded-md px-2 py-1.5 text-xs placeholder:text-ui-faint';
        $buttonClass =
            'btn-secondary shrink-0 rounded-md px-2 py-1.5 text-[10px] font-semibold uppercase tracking-wider';
    } elseif ($emphasized) {
        $formClass = 'mx-auto flex w-full max-w-3xl items-center gap-3 lg:mx-0';
        $inputClass = 'input min-w-0 flex-1 rounded-lg px-4 py-3 text-base placeholder:text-ui-faint';
        $buttonClass = 'btn-primary shrink-0 rounded-lg px-5 py-3 text-xs font-semibold uppercase tracking-wider';
    } else {
        $formClass = 'mx-auto flex w-full max-w-xl items-center gap-2 lg:mx-0';
        $inputClass = 'input min-w-0 flex-1 rounded-md px-3 py-2 text-sm placeholder:text-ui-faint';
        $buttonClass =
            'btn-secondary shrink-0 rounded-md px-3 py-2 text-xs font-semibold uppercase tracking-wider';
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
