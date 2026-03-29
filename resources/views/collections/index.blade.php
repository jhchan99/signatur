<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        @include('partials.head', ['title' => $title])
    </head>
    <body class="min-h-screen bg-zinc-950 text-zinc-100">
        <div class="relative">
            @include('partials.guest-header')

            <main class="mx-auto flex min-h-[calc(100vh-10rem)] w-full max-w-7xl items-center justify-center px-6 pb-20 lg:px-8 lg:pb-28">
                <section class="w-full max-w-3xl rounded-[2rem] border border-zinc-800 bg-zinc-900/60 p-8 text-center shadow-2xl shadow-black/30 sm:p-12">
                    <div class="mx-auto flex max-w-xl flex-col items-center gap-6">
                        <div class="inline-flex items-center gap-3 rounded-full border border-amber-500/30 bg-amber-500/10 px-4 py-2 text-[10px] font-semibold uppercase tracking-[0.3em] text-amber-200">
                            <span class="relative flex size-2">
                                <span class="absolute inline-flex h-full w-full animate-ping rounded-full bg-amber-300/70"></span>
                                <span class="relative inline-flex size-2 rounded-full bg-amber-200"></span>
                            </span>
                            Under Construction
                        </div>

                        <div class="space-y-4">
                            <h1 class="text-4xl font-semibold tracking-tight text-white sm:text-5xl">
                                Collections are taking shape.
                            </h1>
                        </div>

                        <div class="grid w-full gap-3 text-left sm:grid-cols-3">
                            <div class="rounded-2xl border border-zinc-800 bg-zinc-950/70 p-4">
                                <p class="text-[10px] font-semibold uppercase tracking-[0.2em] text-zinc-500">Soon</p>
                                <p class="mt-2 text-sm text-zinc-300">Curated staff picks and seasonal lists.</p>
                            </div>
                            <div class="rounded-2xl border border-zinc-800 bg-zinc-950/70 p-4">
                                <p class="text-[10px] font-semibold uppercase tracking-[0.2em] text-zinc-500">In Progress</p>
                                <p class="mt-2 text-sm text-zinc-300">Editorial workflow and collection metadata.</p>
                            </div>
                            <div class="rounded-2xl border border-zinc-800 bg-zinc-950/70 p-4">
                                <p class="text-[10px] font-semibold uppercase tracking-[0.2em] text-zinc-500">For Now</p>
                                <p class="mt-2 text-sm text-zinc-300">Browse the catalog while this page comes to life.</p>
                            </div>
                        </div>

                        <div class="flex flex-wrap items-center justify-center gap-3 pt-2">
                            <a href="{{ route('books.index') }}" class="inline-flex rounded-full border border-white/15 bg-white px-5 py-2.5 text-sm font-medium text-zinc-950 transition hover:bg-zinc-200">
                                Browse books
                            </a>
                            <a href="{{ route('home') }}" class="inline-flex rounded-full border border-zinc-700 bg-zinc-950 px-5 py-2.5 text-sm font-medium text-white transition hover:border-zinc-600 hover:bg-zinc-900">
                                Return home
                            </a>
                        </div>
                    </div>
                </section>
            </main>
        </div>
    </body>
</html>
