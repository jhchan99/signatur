@props([
    'sidebar' => false,
    'tone' => 'default',
])

@php
    $href = $attributes->get('href', route('home'));

    $baseClass = match ($tone) {
        'inverse' => 'shrink-0 lowercase no-underline text-lg font-medium tracking-tight !text-neutral-100 [&>span.font-sans]:font-sans [&>span.font-serif]:font-serif [&>span.font-serif]:!text-ui-gold',
        default => 'nav-logo shrink-0 lowercase no-underline'.($sidebar ? ' px-1' : ''),
    };
@endphp

<a
    href="{{ $href }}"
    {{ $attributes->except('href')->merge([
        'class' => $baseClass,
    ]) }}
    wire:navigate
>
    <span class="font-sans font-medium">signa</span><span class="font-serif text-ui-gold">t</span><span class="font-sans font-medium">ur</span>
</a>
