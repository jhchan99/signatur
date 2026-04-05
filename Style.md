# Signatur — Design system reference

Use this document as the single source of truth for **target** UI styling when building or reviewing Signatur (social book-tracking, Letterboxd-for-books). Paste or `@mention` this file in Cursor when generating components.

---

## Product context

Signatur is a **social book-tracking app**. The stack is Laravel, Livewire, Flux UI, and **Tailwind CSS v4**.

**Core idea (borrowed from Letterboxd):** book cover art does the emotional work. The interface **organizes** information—it should not compete with covers or feel decorative.

**Themes:** **Dark** (default, evening) and **light** (morning / reading mode). Both are warm and literary—never clinical. **Light** uses warm parchment surfaces (`#f5f0e8` page), not pure white. **Dark** uses warm near-black (`#141210`), not pure black or blue-black.

---

## Principles

1. **Content leads** — Covers carry visual weight; the UI steps back.
2. **Dark, not gloomy** — Surfaces are warm brown-blacks, not blue-grays; the mood is literary and aged, not cold or “tech dashboard.”
3. **Light, not sterile** — Light mode stays parchment-toned; avoid stark white-gray SaaS chrome.
4. **Dense but clear** — Pack information tightly; every element should earn its space.
5. **No decoration** — If it does not communicate something, remove it.

---

## Theming mechanics

- **Dark mode** is applied with the **`dark` class on `<html>`** (Tailwind `darkMode: 'class'`), not with `prefers-color-scheme` alone.
- **Default** in product copy and session: treat **dark as default**; light is an explicit opt-in (e.g. session `theme === 'light'` leaves `<html>` without `dark`).
- **Templates:** Prefer **semantic utilities** (`bg-page`, `text-ui-primary`, …) so one class set works in both themes. Avoid raw hex in Blade/Livewire markup.

### Theme toggle (Livewire, session)

Store preference in the session and drive the root class:

```blade
{{-- Example: layouts/app.blade.php — root element --}}
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}"
      class="{{ session('theme', 'dark') === 'light' ? '' : 'dark' }}">
```

```php
// Example: app/Livewire/ThemeToggle.php
namespace App\Livewire;

use Livewire\Component;

class ThemeToggle extends Component
{
    public string $theme;

    public function mount(): void
    {
        $this->theme = session('theme', 'dark');
    }

    public function toggle(): void
    {
        $this->theme = $this->theme === 'dark' ? 'light' : 'dark';
        session(['theme' => $this->theme]);
    }

    public function render()
    {
        return view('livewire.theme-toggle');
    }
}
```

```blade
{{-- Example: resources/views/livewire/theme-toggle.blade.php --}}
<button type="button" wire:click="toggle"
        class="font-sans text-nav uppercase tracking-widest
               text-ui-faint hover:text-ui-primary
               transition-colors duration-150">
    {{ $theme === 'dark' ? 'Light' : 'Dark' }}
</button>
```

---

## Color palette

### Dark theme — surfaces (`ink`)

| Role | Token | Hex | Usage |
|------|--------|-----|--------|
| Background | `ink` | `#141210` | Page background |
| Surface | `ink-2` | `#1e1c1a` | Secondary surfaces (e.g. top bar) |
| Surface raised | `ink-3` | `#2a2825` | Cards |
| Border | `ink-4` | `#3e3b37` | Hairline borders |

### Light theme — surfaces (`parchment` scale)

These names mean **background layers in light mode**, not “parchment text.”

| Role | Token | Hex | Usage |
|------|--------|-----|--------|
| Background | `parchment` | `#f5f0e8` | Page background |
| Surface | `parchment-2` | `#ede7db` | Nav, secondary surfaces |
| Surface raised | `parchment-3` | `#ddd5c8` | Cards |
| Border | `parchment-4` | `#c8bfb0` | Hairline borders |

### Gold (accent — shifts by theme)

| Token | Hex | Typical use |
|-------|-----|-------------|
| `gold-dark` | `#8b5e2a` | Gold on **light** backgrounds; filled controls in light mode |
| `gold` | `#c9a96e` | Gold on **dark** backgrounds; stars, accent text in dark mode |
| `gold-light` | `#e8c68a` | Hover state on gold controls (dark theme especially) |

**CTA / filled button pattern:** `bg-gold-dark text-parchment dark:bg-gold dark:text-ink` (light: deep gold on cream text color; dark: medium gold on ink).

### Semantic text (use in components — theme-aware)

| Class | Light (`html` without `dark`) | Dark (`html.dark`) |
|-------|------------------------------|---------------------|
| `text-ui-primary` | `#1a1714` | `#c8c4bc` |
| `text-ui-muted` | `#6b6560` | `#7a7874` |
| `text-ui-faint` | `#b0a898` | `#4a4845` |
| `text-ui-gold` | `gold-dark` | `gold` |

### Legacy dark-text token names (migration)

Older snippets used **`parchment` / `parchment-muted` / `parchment-faint`** only for **copy on dark backgrounds**. When both themes ship, **prefer `text-ui-*`** in new markup so light mode stays correct. Map mentally: `text-parchment` (old) ≈ `text-ui-primary` in dark-only code paths.

### Other

| Role | Token | Hex | Usage |
|------|--------|-----|--------|
| Info | `slate.book` | `#7ba7bc` | Informational accents only (not primary actions) |

**Accent rule:** **Gold is the only interactive accent color.** Use it for active nav, primary buttons, star ratings, and key highlights. Do **not** use blue or green as the primary interactive color. (The `slate.book` token is for informational UI, not default links or buttons.)

---

## Typography

- **Display / book detail titles:** **Playfair Display**, ~28px, medium — bookish, printed quality.
- **Section headings:** **DM Sans**, ~18px, medium (e.g. “Recently read”).
- **Book titles in lists:** **DM Sans**, ~15px, medium.
- **Body / reviews:** **Playfair Display** or a readable serif at ~14px, regular — long-form and review text feel like reading.
- **Meta (author, dates, counts):** **DM Sans**, ~12px, regular.
- **Labels / nav:** **DM Sans**, ~10px, medium, uppercase, letter-spacing ~`0.1em`.

**Optional monospace:** **JetBrains Mono** for codes or technical snippets only.

**Split rule:** **Serif** for display headings and review/long-form body; **sans** for UI chrome (nav, labels, buttons, metadata).

---

## Layout and navigation

- **Top bar:** Semantic **`bg-surface`** with **`border-b border-ui`** (maps to `ink-2` / `parchment-2` and matching borders per theme). Prefer a shared **`nav-bar`** class in CSS when you centralize layout.
- **Page body:** **`bg-page`**, **`min-h-screen`**, main content **`max-w-6xl mx-auto px-6`** (adjust `py-*` per page).
- **Logo:** Serif wordmark “signatur” with the **t** as a **gold** accent (e.g. `signa<span class="accent">t</span>ur` with **`text-ui-gold`** on `.accent` when using semantic text).
- **Nav links:** Uppercase, tracked, small sans — default **`text-ui-faint`**; **active** = **`text-ui-primary`** (reserve gold for accents/CTAs unless a screen explicitly uses gold for “current”).
- **Primary CTA:** “+ Log” — **`btn-log`**: `bg-gold-dark text-parchment dark:bg-gold dark:text-ink`, hover opacity or single-step gold lighten (no blue focus ring).
- **Theme:** Include **`<livewire:theme-toggle />`** (or equivalent) when session-based theme is enabled.

---

## Components (patterns)

### Book card — list view

- Horizontal card, **`rounded-card` (~10px)**.
- **Left:** cover image, small shadow, **`rounded-cover`**.
- **Right:** title (sans `title`), author · year (meta muted), **5-star rating** (gold filled / `ink-4` empty), optional review snippet (**serif**, `line-clamp-2` in lists), genre **tags** (uppercase pills, thin border).

### Book grid — shelf view

- Dense grid of **covers only** (no always-visible metadata).
- **Hover:** dark overlay; **title + rating** in light text at bottom. Keeps the shelf feeling like a physical browse, not a table.

### Stars

- Filled: **`text-gold-dark dark:text-gold`** (or semantic row wrapper). Empty: **`text-parchment-4 dark:text-ink-4`**. Prefer SVG icons, not emoji; half-stars via SVG if needed.

### Tags / genres

- Small, uppercase, pill **`rounded-tag`**, **`border-ui`**, **`text-ui-faint`**; hover border shift; **`tag-active`**: **`text-ui-gold`** with **`border-gold-dark dark:border-gold`**.

### Buttons

- **Primary (`btn-primary`):** `bg-gold-dark text-parchment dark:bg-gold dark:text-ink`, hover opacity or `gold-light` on dark.
- **Secondary (`btn-secondary`):** hairline **`border-ui`**, **`text-ui-muted`**; hover border/text shift (e.g. `#b0a898` / `#7a7874`), no scale.
- **Ghost (`btn-ghost`):** **`text-ui-muted`** → **`text-ui-primary`** on hover.

### Inputs

- **`input`:** **`bg-surface`**, **`border-ui`**, **`text-ui-primary`**, **`placeholder:text-ui-faint`**; focus border shift toward muted warm gray — **no** blue ring.

---

## Spacing scale

Use consistently:

| Token | Size | Typical use |
|-------|------|-------------|
| tight | 4px | Star gaps, icon nudges |
| snug | 8px | Tag padding, tight stacks |
| card gap | 12px | Inside cards |
| cover-meta | 20px | Cover to metadata |
| section pad | 32px | Section vertical padding |
| section gap | 48px | Between major page sections |

---

## Radii and elevation

- **`rounded-cover`:** ~4px — cover images.
- **`rounded-card`:** ~10px — cards.
- **`rounded-tag`:** pill — tags, primary pill buttons.
- **Border width:** prefer **hairline** (~`0.5px`) for structure, not thick decorative frames.

**Shadows:** Prefer **`shadow-cover`** on **book covers only**. Avoid heavy drop shadows on chrome (nav, generic cards) in the **target** system—if the current app uses card shadows, migrate toward this rule when aligning with Signatur.

---

## Interaction rules (summary)

1. **Surfaces:** **`bg-page`** / **`bg-surface`** / **`bg-raised`** with **`border-ui`** — maps to ink scale in dark mode and parchment scale in light mode.
2. **Gold only** as the primary interactive accent (see Color palette). Use **`text-ui-gold`** and **`bg-gold-dark dark:bg-gold`** so gold reads correctly on both backgrounds.
3. **Covers are the hero** — no badges, gradients, or overlays on covers **except** intentional hover overlays in grid/shelf views.
4. **Hairline borders** (`border` + **`border-ui`**, typically **`border-hairline`** / 0.5px where configured); avoid thick decorative frames.
5. **Shadows:** **`shadow-cover`** on **book covers only**. **No** drop shadows on general UI chrome; **`shadow-card`** at most very subtle if legacy—prefer border hover only.
6. **Hover:** grid cover = opacity overlay; **interactive cards (`card-hover`) = border color shift only** — **no scale or translate**. Buttons = opacity or one-step gold change.
7. **Dense but readable:** `line-clamp-2` on review previews in lists; full review on detail.
8. **Spacing:** Inner card padding **`p-4`**; cover-to-metadata **`gap-4`**; tag row **`gap-1.5`**.

---

## Implementation in this repository

**Tailwind CSS v4:** This project does **not** use `tailwind.config.js` for theme extension. Theme tokens and sources live in [`resources/css/app.css`](resources/css/app.css): `@import 'tailwindcss'`, `@source` globs, and an `@theme { ... }` block.

**Current vs target:** The **live** `app.css` theme today uses **Instrument Sans** and a **light-oriented** semantic palette (e.g. sand, espresso, `surface-*`, `action-primary`). This document describes the **`target` Signatur** ink / gold / parchment aesthetic. Wireframes and new custom markup can follow `Style.md` first; **migrating** Flux, global surfaces, and tokens to match is a separate, coordinated change—do not assume the running app already matches this file.

### Tailwind v4 `@theme` sketch (colors + fonts)

When implementing, extend `@theme` with CSS variables (names are illustrative—match Tailwind v4 naming for your utilities):

```css
@theme {
    --font-sans: 'DM Sans', ui-sans-serif, system-ui, sans-serif;
    --font-serif: 'Playfair Display', ui-serif, Georgia, serif;
    --font-mono: 'JetBrains Mono', ui-monospace, monospace;

    /* Dark surfaces */
    --color-ink: #141210;
    --color-ink-2: #1e1c1a;
    --color-ink-3: #2a2825;
    --color-ink-4: #3e3b37;

    /* Light surfaces (warm parchment — not pure white) */
    --color-parchment: #f5f0e8;
    --color-parchment-2: #ede7db;
    --color-parchment-3: #ddd5c8;
    --color-parchment-4: #c8bfb0;

    --color-gold: #c9a96e;
    --color-gold-light: #e8c68a;
    --color-gold-dark: #8b5e2a;

    --color-slate-book: #7ba7bc;

    /* Legacy: text on dark-only UIs; prefer semantic text-ui-* utilities for dual theme */
    --color-parchment-text: #c8c4bc;
    --color-parchment-muted: #7a7874;
    --color-parchment-faint: #4a4845;

    /* Optional: map radii/shadows to theme keys your utilities expect */
    --radius-cover: 4px;
    --radius-card: 10px;
    --radius-tag: 9999px;
    --shadow-cover: 0 4px 16px rgb(0 0 0 / 0.4);
    --shadow-card: 0 2px 8px rgb(0 0 0 / 0.15);
}
```

Add **font size** tokens to `@theme` as you adopt utilities like `text-nav`, `text-meta`, etc. (Tailwind v4 uses `@theme` for these; mirror the scale from the legacy snippet below.)

**Naming:** In this sketch, **`--color-parchment`** is the **light page surface**. Older dark-only docs used “parchment” for **primary text** on ink; that role is now **`text-ui-*`** (or `--color-parchment-text` if you need a named token). Rename any existing `text-parchment` utilities when you adopt the dual-theme scale to avoid cream-on-cream bugs in light mode.

**Legacy Tailwind v3-style config:** Older snippets may reference `tailwind.config.js`. Treat them as **conceptual**; **port** `extend.colors`, `fontFamily`, `fontSize`, `borderRadius`, etc., into `@theme` and `@layer components` in `app.css`.

---

## Google Fonts

Add to the main layout `<head>` when DM Sans / Playfair / JetBrains are not yet bundled:

```html
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500&family=Playfair+Display:wght@400;500&family=JetBrains+Mono:wght@400&display=swap" rel="stylesheet">
```

---

## Global CSS utilities (`app.css`)

The following uses `@apply` and assumes Tailwind generates utilities for the token names above (after `@theme` migration). In **`@layer base`**, plain **`html`** is the **light** (parchment) page; **`html.dark`** is the **dark** (ink) page. The **product default** can still be dark via session (apply `class="dark"` on `<html>` when `session('theme', 'dark')`) so most users see ink while CSS without `.dark` remains valid for light mode.

Place in [`resources/css/app.css`](resources/css/app.css) when implementing. Prefer these **semantic** classes in Blade so both themes stay in sync.

```css
@layer base {
    html {
        background-color: #f5f0e8;
        color: #1a1714;
    }

    html.dark {
        background-color: #141210;
        color: #c8c4bc;
    }

    ::-webkit-scrollbar {
        width: 6px;
    }

    html::-webkit-scrollbar-track {
        background: #ede7db;
    }

    html.dark::-webkit-scrollbar-track {
        background: #1e1c1a;
    }

    html::-webkit-scrollbar-thumb {
        background: #c8bfb0;
        border-radius: 3px;
    }

    html.dark::-webkit-scrollbar-thumb {
        background: #3e3b37;
        border-radius: 3px;
    }
}

@layer components {
    /* Page & surface */
    .bg-page {
        @apply bg-parchment dark:bg-ink;
    }

    .bg-surface {
        @apply bg-parchment-2 dark:bg-ink-2;
    }

    .bg-raised {
        @apply bg-parchment-3 dark:bg-ink-3;
    }

    .border-ui {
        @apply border-parchment-4 dark:border-ink-4;
    }

    /* Semantic text */
    .text-ui-primary {
        @apply text-[#1a1714] dark:text-[#c8c4bc];
    }

    .text-ui-muted {
        @apply text-[#6b6560] dark:text-[#7a7874];
    }

    .text-ui-faint {
        @apply text-[#b0a898] dark:text-[#4a4845];
    }

    .text-ui-gold {
        @apply text-gold-dark dark:text-gold;
    }

    /* Navigation shell */
    .nav-bar {
        @apply bg-surface border-b border-ui px-6 py-3 flex items-center gap-6;
    }

    .nav-link {
        @apply font-sans text-nav font-medium uppercase tracking-widest text-ui-faint
            hover:text-ui-primary transition-colors duration-150;
    }

    .nav-link-active {
        @apply font-sans text-nav font-medium uppercase tracking-widest text-ui-primary;
    }

    .nav-logo {
        @apply font-serif text-lg font-medium text-ui-primary tracking-tight;
    }

    .nav-logo .accent {
        @apply text-ui-gold;
    }

    .btn-log {
        @apply font-sans text-meta font-medium px-4 py-1.5 rounded-tag
            bg-gold-dark text-parchment dark:bg-gold dark:text-ink
            hover:opacity-90 transition-opacity duration-150;
    }

    /* Cards */
    .card {
        @apply bg-raised border border-ui rounded-card p-4;
    }

    .card-hover {
        @apply card
            hover:border-[#b0a898] dark:hover:border-[#7a7874]
            transition-colors duration-150 cursor-pointer;
    }

    .book-cover {
        @apply rounded-cover shadow-cover shrink-0 object-cover;
    }

    .book-cover-sm {
        @apply book-cover w-10 h-14;
    }

    .book-cover-md {
        @apply book-cover w-[52px] h-[76px];
    }

    .book-cover-lg {
        @apply book-cover w-24 h-36;
    }

    .cover-grid-item {
        @apply relative aspect-[2/3] rounded-cover overflow-hidden cursor-pointer;
    }

    .cover-grid-item .overlay {
        @apply absolute inset-0 bg-black/60 opacity-0 hover:opacity-100
            transition-opacity duration-150 flex items-end p-2;
    }

    .cover-grid-item .overlay-text {
        @apply text-[10px] text-white/85 leading-snug;
    }

    /* Typography components */
    .book-title {
        @apply font-sans text-title font-medium text-ui-primary leading-snug;
    }

    .book-author {
        @apply font-sans text-meta text-ui-muted mt-0.5;
    }

    .review-body {
        @apply font-serif text-body text-ui-muted italic leading-relaxed;
    }

    .section-heading {
        @apply font-sans text-subhead font-medium text-ui-primary;
    }

    .display-heading {
        @apply font-serif text-display font-medium text-ui-primary;
    }

    .section-label {
        @apply font-sans text-nav font-medium uppercase tracking-widest text-ui-faint;
    }

    /* Stars */
    .star-row {
        @apply flex items-center gap-0.5 mt-2;
    }

    .star-filled {
        @apply text-gold-dark dark:text-gold;
    }

    .star-empty {
        @apply text-parchment-4 dark:text-ink-4;
    }

    /* Tags */
    .tag {
        @apply inline-block font-sans text-nav font-medium uppercase tracking-wider
            text-ui-faint border border-ui px-2 py-0.5 rounded-tag
            hover:text-ui-muted hover:border-[#b0a898] dark:hover:border-[#7a7874]
            transition-colors duration-150 cursor-pointer;
    }

    .tag-active {
        @apply tag text-ui-gold border-gold-dark dark:border-gold;
    }

    /* Buttons */
    .btn-primary {
        @apply font-sans text-meta font-medium px-5 py-2 rounded-tag
            bg-gold-dark text-parchment dark:bg-gold dark:text-ink
            hover:opacity-90 transition-opacity duration-150;
    }

    .btn-secondary {
        @apply font-sans text-meta px-5 py-2 rounded-tag border border-ui text-ui-muted
            hover:text-ui-primary hover:border-[#b0a898] dark:hover:border-[#7a7874]
            transition-colors duration-150;
    }

    .btn-ghost {
        @apply font-sans text-meta text-ui-muted hover:text-ui-primary transition-colors duration-150;
    }

    .divider {
        @apply border-t border-ui;
    }

    .input {
        @apply bg-surface border border-ui rounded-card w-full px-4 py-2.5
            font-sans text-body text-ui-primary placeholder:text-ui-faint
            focus:outline-none focus:border-[#b0a898] dark:focus:border-[#7a7874]
            transition-colors duration-150;
    }

    .rating {
        @apply flex items-center gap-0.5 mt-2;
    }
}
```

### Token cheatsheet (dual theme)

| Use case | Class |
|----------|--------|
| Page background | `bg-page` |
| Nav / secondary surface | `bg-surface` |
| Card / raised surface | `bg-raised` |
| All hairline borders | `border border-ui` |
| Primary text | `text-ui-primary` |
| Author / meta text | `text-ui-muted` |
| Nav labels / faint UI | `text-ui-faint` |
| Stars / gold accent copy | `text-ui-gold` |
| CTA / filled button | `bg-gold-dark text-parchment dark:bg-gold dark:text-ink` |
| Display serif | `font-serif` (headings + reviews only) |
| UI chrome sans | `font-sans` |
| Interactive card | `card-hover` |
| Genre pill | `tag` / `tag-active` |
| Star row spacing | `star-row` or `rating` |

**Grid hover with `group`:** For keyboard/accessibility and parent-hover behavior, prefer Tailwind `group` / `group-hover` on the cover cell instead of `.overlay:hover` only when you implement shelf views.

---

## Blade component examples

### Page layout shell

```blade
{{-- Example: resources/views/layouts/app.blade.php --}}
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}"
      class="{{ session('theme', 'dark') === 'light' ? '' : 'dark' }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title ?? 'Signatur' }}</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500&family=Playfair+Display:wght@400;500&family=JetBrains+Mono:wght@400&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
</head>
<body class="bg-page min-h-screen">
    <x-nav />
    <main class="max-w-6xl mx-auto px-6 py-10">
        {{ $slot }}
    </main>
    @livewireScripts
</body>
</html>
```

### Book card (list view)

```blade
{{-- Example: resources/views/components/book-card.blade.php --}}
<div class="card-hover flex gap-4">
    <img src="{{ $book->cover_url }}" alt="{{ $book->title }}"
         class="book-cover-md" />

    <div class="flex-1 min-w-0">
        <div class="book-title truncate">{{ $book->title }}</div>
        <div class="book-author">{{ $book->author }} · {{ $book->year }}</div>

        <div class="rating">
            @for ($i = 1; $i <= 5; $i++)
                @if ($i <= $book->rating)
                    <x-icon.star class="size-3 star-filled" />
                @else
                    <x-icon.star class="size-3 star-empty" />
                @endif
            @endfor
        </div>

        @if ($book->review)
            <p class="review-body mt-2 line-clamp-2">{{ $book->review }}</p>
        @endif

        <div class="flex flex-wrap gap-1.5 mt-3">
            @foreach ($book->genres as $genre)
                <span class="tag">{{ $genre }}</span>
            @endforeach
        </div>
    </div>
</div>
```

### Book grid (shelf view)

```blade
{{-- Example: resources/views/components/book-grid.blade.php --}}
<div class="grid grid-cols-5 gap-2 sm:grid-cols-7 md:grid-cols-10">
    @foreach ($books as $book)
        <div class="cover-grid-item">
            <img src="{{ $book->cover_url }}" alt="{{ $book->title }}"
                 class="size-full object-cover" />
            <div class="overlay">
                <div class="overlay-text">
                    {{ Str::limit($book->title, 24) }}<br>
                    {{ str_repeat('★', $book->rating) }}
                </div>
            </div>
        </div>
    @endforeach
</div>
```

### Navigation

```blade
{{-- Example: resources/views/components/nav.blade.php --}}
<nav class="nav-bar">
    <a href="/" class="nav-logo">signa<span class="accent">t</span>ur</a>

    <div class="flex items-center gap-5 flex-1">
        <a href="/books"   class="{{ request()->is('books*')   ? 'nav-link-active' : 'nav-link' }}">Books</a>
        <a href="/lists"   class="{{ request()->is('lists*')   ? 'nav-link-active' : 'nav-link' }}">Lists</a>
        <a href="/members" class="{{ request()->is('members*') ? 'nav-link-active' : 'nav-link' }}">Members</a>
        <a href="/journal" class="{{ request()->is('journal*') ? 'nav-link-active' : 'nav-link' }}">Journal</a>
    </div>

    <livewire:theme-toggle />
    <a href="/log" class="btn-log">+ Log</a>
</nav>
```

---

## Checklist for new UI (for agents)

1. **Root:** `<html>` gets **`class="dark"`** for dark mode; omit it for light (e.g. session-backed toggle). Do not rely on `prefers-color-scheme` alone.
2. **Surfaces / text:** Use **`bg-page`**, **`bg-surface`**, **`bg-raised`**, **`border-ui`**, **`text-ui-*`** in templates — no raw hex in Blade for theme colors.
3. **Light page** is warm **`parchment`** (`#f5f0e8`), not white; **dark page** is **`ink`** (`#141210`), not pure/black-blue black.
4. **Gold** only as primary interactive accent; **`text-ui-gold`** / **`bg-gold-dark dark:bg-gold`**; no blue/green CTAs.
5. **Covers** uncluttered except hover overlays where specified.
6. **Serif** for display + reviews; **sans** for UI chrome.
7. **Hairline** **`border-ui`**; **`shadow-cover`** on covers only — not on generic chrome.
8. **Section labels:** `section-label` (small caps, tracked, faint).
9. **Stars:** `star-filled` / `star-empty` (theme-aware); not emoji in final UI.
10. **Card hover:** border shift only — **no** scale or translate.
11. **List reviews:** `line-clamp-2`; full text on detail.
12. **Spacing:** card **`p-4`**, cover row **`gap-4`**, tags **`gap-1.5`**, main **`max-w-6xl mx-auto px-6`**.

---

## Reference: legacy `tailwind.config.js` shape (conceptual only)

If you encounter v3-style config in issues or old docs, the **intent** for **dual theme** is:

- **Dark surfaces:** `ink`, `ink-2`–`ink-4`.
- **Light surfaces:** `parchment` (page `#f5f0e8`), `parchment-2`–`parchment-4` (nav, cards, borders).
- **Gold:** `gold-dark` (`#8b5e2a` on light bg), `gold` (`#c9a96e` on dark), `gold-light` (hover).
- **Semantic text:** Prefer component utilities `text-ui-primary`, `text-ui-muted`, `text-ui-faint`, `text-ui-gold` instead of pairing `parchment-*` text tokens by hand.
- **Info:** `slate.book`.
- **Fonts:** `sans` → DM Sans, `serif` → Playfair Display, `mono` → JetBrains Mono.
- **Font sizes:** `nav`, `meta`, `body`, `title`, `subhead`, `display` with line-height and tracking as in specs.
- **Radii:** `cover` 4px, `tag` pill (~20px in some snippets), `card` 10px.
- **Border:** `hairline` 0.5px.
- **Shadows:** `cover` on covers; `card` subtle if needed (prefer border-only hover on cards).
- **Dark mode:** `darkMode: 'class'` on `<html>`.
- **Background images:** optional placeholder gradients in older snippets only.

Port all of the above into **`resources/css/app.css`** `@theme` and `@layer components` when adopting this system.
