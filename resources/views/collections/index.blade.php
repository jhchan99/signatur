<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        @include('partials.head', ['title' => $title])
    </head>
    <body class="min-h-screen bg-page text-ui-primary antialiased">
        <div class="relative">
            @include('partials.guest-header')

            <main class="guest-page-main mx-auto flex min-h-[calc(100vh-10rem)] w-full max-w-7xl items-center justify-center px-6 pb-20 lg:px-8 lg:pb-28">
                <section class="w-full max-w-3xl rounded-[2rem] border border-ui bg-surface p-8 text-center shadow-xl shadow-faded-copper-300/20 sm:p-12">
                    <div class="mx-auto flex max-w-xl flex-col items-center gap-6">
                        <div class="inline-flex items-center gap-3 rounded-full border border-amber-600/20 bg-amber-500/10 px-4 py-2 text-[10px] font-semibold uppercase tracking-[0.3em] text-amber-800">
                            <span class="relative flex size-2">
                                <span class="absolute inline-flex h-full w-full animate-ping rounded-full bg-amber-500/60"></span>
                                <span class="relative inline-flex size-2 rounded-full bg-amber-700"></span>
                            </span>
                            Under Construction
                        </div>

                        <div class="space-y-4">
                            <h1 class="text-4xl font-semibold tracking-tight text-ui-primary sm:text-5xl">
                                Collections are taking shape.
                            </h1>
                        </div>

                        <div class="grid w-full gap-3 text-left sm:grid-cols-3">
                            <div class="rounded-2xl border border-ui bg-page p-4">
                                <p class="text-[10px] font-semibold uppercase tracking-[0.2em] text-ui-faint">Soon</p>
                                <p class="mt-2 text-sm text-ui-muted">Curated staff picks and seasonal lists.</p>
                            </div>
                            <div class="rounded-2xl border border-ui bg-page p-4">
                                <p class="text-[10px] font-semibold uppercase tracking-[0.2em] text-ui-faint">In Progress</p>
                                <p class="mt-2 text-sm text-ui-muted">Editorial workflow and collection metadata.</p>
                            </div>
                            <div class="rounded-2xl border border-ui bg-page p-4">
                                <p class="text-[10px] font-semibold uppercase tracking-[0.2em] text-ui-faint">For Now</p>
                                <p class="mt-2 text-sm text-ui-muted">Browse the catalog while this page comes to life.</p>
                            </div>
                        </div>

                        <div class="flex flex-wrap items-center justify-center gap-3 pt-2">
                            <a href="{{ route('books.index') }}" class="inline-flex rounded-full border border-action-primary bg-action-primary px-5 py-2.5 text-sm font-medium text-text-inverse transition hover:border-action-primary-hover hover:bg-action-primary-hover">
                                Browse books
                            </a>
                            <a href="{{ route('home') }}" class="inline-flex rounded-full border border-border-strong bg-surface-page px-5 py-2.5 text-sm font-medium text-ui-primary transition hover:bg-page">
                                Return home
                            </a>
                        </div>
                    </div>
                </section>
            </main>
        </div>
    </body>
</html>
