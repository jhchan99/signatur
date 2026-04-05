{{-- Auth: session theme + Flux localStorage. Guest: prefers-color-scheme only (no session). --}}
@php
    $appearance = auth()->check()
        ? (session('theme', 'dark') === 'light' ? 'light' : 'dark')
        : 'system';
@endphp

<style>
    :root.dark {
        color-scheme: dark;
    }
</style>
<script>
    window.Flux = {
        applyAppearance(appearance) {
            let applyDark = () => document.documentElement.classList.add('dark');
            let applyLight = () => document.documentElement.classList.remove('dark');

            if (appearance === 'system') {
                let media = window.matchMedia('(prefers-color-scheme: dark)');

                window.localStorage.removeItem('flux.appearance');

                let sync = () => (media.matches ? applyDark() : applyLight());
                sync();
                media.addEventListener('change', sync);
            } else if (appearance === 'dark') {
                window.localStorage.setItem('flux.appearance', 'dark');

                applyDark();
            } else if (appearance === 'light') {
                window.localStorage.setItem('flux.appearance', 'light');

                applyLight();
            }
        },
    };

    window.Flux.applyAppearance(@js($appearance));
</script>
