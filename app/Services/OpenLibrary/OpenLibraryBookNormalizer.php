<?php

namespace App\Services\OpenLibrary;

use Illuminate\Support\Collection;

final class OpenLibraryBookNormalizer
{
    public static function coverUrlFromCoverId(?int $coverId, string $size = 'M'): ?string
    {
        if ($coverId === null || $coverId <= 0) {
            return null;
        }

        $size = self::normalizeCoverSize($size);

        return 'https://covers.openlibrary.org/b/id/'.$coverId.'-'.$size.'.jpg';
    }

    /**
     * @param  array<string, mixed>  $work
     */
    public static function firstCoverIdFromWork(array $work): ?int
    {
        $covers = $work['covers'] ?? null;
        if (is_array($covers) && $covers !== []) {
            $first = $covers[0] ?? null;
            if (is_int($first) || (is_string($first) && ctype_digit($first))) {
                return (int) $first;
            }
        }

        return null;
    }

    /**
     * @param  array<string, mixed>  $work
     */
    public static function firstPublishYearFromWork(array $work): ?int
    {
        $yearRaw = $work['first_publish_year'] ?? null;
        if (is_numeric($yearRaw)) {
            return (int) $yearRaw;
        }

        $dateRaw = $work['first_publish_date'] ?? null;
        if (! is_string($dateRaw) || $dateRaw === '') {
            return null;
        }
        if (preg_match('/(\d{4})/', $dateRaw, $m) === 1) {
            return (int) $m[1];
        }

        return null;
    }

    /**
     * Hero image from a stored Open Library cover id (large).
     */
    public static function heroCoverUrlFromCoverId(?int $coverId): ?string
    {
        return self::coverUrlFromCoverId($coverId, 'L');
    }

    /**
     * @param  array<string, mixed>  $work
     */
    public static function coverUrlFromWork(array $work, string $size = 'M'): ?string
    {
        $size = self::normalizeCoverSize($size);

        $id = self::firstCoverIdFromWork($work);
        if ($id !== null) {
            return self::coverUrlFromCoverId($id, $size);
        }

        $editionKey = $work['cover_edition_key'] ?? null;
        if (is_string($editionKey) && str_starts_with($editionKey, '/books/')) {
            $olid = basename($editionKey);

            return 'https://covers.openlibrary.org/b/olid/'.$olid.'-'.$size.'.jpg';
        }

        return null;
    }

    /**
     * Catalog stored a full cover URL; hero layouts should request large when possible.
     */
    public static function heroCoverUrlFromStoredCover(?string $catalogCoverUrl): ?string
    {
        if ($catalogCoverUrl === null || $catalogCoverUrl === '') {
            return null;
        }

        if (! str_contains($catalogCoverUrl, 'covers.openlibrary.org')) {
            return null;
        }

        $upgraded = preg_replace('/-(S|M|L)\.jpg$/i', '-L.jpg', $catalogCoverUrl);

        return is_string($upgraded) && $upgraded !== '' ? $upgraded : null;
    }

    public static function normalizeCoverSize(string $size): string
    {
        $upper = strtoupper($size);

        return in_array($upper, ['S', 'M', 'L'], true) ? $upper : 'M';
    }

    public static function description(mixed $raw): ?string
    {
        if (is_string($raw)) {
            $trimmed = trim($raw);

            return $trimmed === '' ? null : $trimmed;
        }

        if (is_array($raw) && isset($raw['value']) && is_string($raw['value'])) {
            $trimmed = trim($raw['value']);

            return $trimmed === '' ? null : $trimmed;
        }

        return null;
    }

    /**
     * @param  array<string, mixed>  $work
     */
    public static function authorRoleFromWorkAuthorEntry(mixed $item): ?string
    {
        if (! is_array($item)) {
            return null;
        }

        $role = $item['role'] ?? null;
        if (is_string($role) && $role !== '') {
            return mb_substr($role, 0, 255);
        }

        if (is_array($role)) {
            $key = $role['key'] ?? null;
            if (is_string($key) && $key !== '') {
                return mb_substr(basename($key), 0, 255);
            }
        }

        return null;
    }

    public static function authorKeysFromWork(array $work): Collection
    {
        $authors = $work['authors'] ?? [];
        if (! is_array($authors)) {
            return collect();
        }

        return collect($authors)
            ->map(function (mixed $item): ?string {
                if (! is_array($item)) {
                    return null;
                }

                $author = $item['author'] ?? null;
                if (is_array($author) && isset($author['key']) && is_string($author['key'])) {
                    return $author['key'];
                }

                return null;
            })
            ->filter()
            ->unique()
            ->values();
    }

    /**
     * Subject tags from an Open Library work payload (stored locally for display).
     *
     * @param  array<string, mixed>  $work
     * @return list<string>
     */
    /**
     * Language codes extracted from an edition payload (e.g. /languages/eng → eng).
     *
     * @param  array<string, mixed>  $edition
     * @return list<string>
     */
    public static function languageCodesFromEdition(array $edition): array
    {
        $langs = $edition['languages'] ?? null;
        if (! is_array($langs) || $langs === []) {
            return [];
        }

        $out = [];
        foreach ($langs as $lang) {
            if (! is_array($lang)) {
                continue;
            }
            $key = $lang['key'] ?? null;
            if (! is_string($key) || ! str_starts_with($key, '/languages/')) {
                continue;
            }
            $code = basename($key);
            if ($code !== '') {
                $out[] = $code;
            }
        }

        return array_values(array_unique($out, SORT_STRING));
    }

    public static function subjectsFromWork(array $work, int $limit = 12): array
    {
        $raw = $work['subjects'] ?? null;
        if (! is_array($raw)) {
            return [];
        }

        /** @var list<string> $out */
        $out = [];
        foreach ($raw as $item) {
            $label = null;
            if (is_string($item)) {
                $label = $item;
            } elseif (is_array($item)) {
                $name = $item['name'] ?? $item['subject'] ?? null;
                if (is_string($name)) {
                    $label = $name;
                }
            }
            if ($label === null) {
                continue;
            }
            $trimmed = trim($label);
            if ($trimmed === '') {
                continue;
            }
            $out[] = $trimmed;
        }

        $out = array_values(array_unique($out, SORT_STRING));

        return array_slice($out, 0, $limit);
    }

    /**
     * @param  array<string, mixed>  $doc
     */
    public static function coverUrlFromSearchDoc(array $doc): ?string
    {
        $coverI = $doc['cover_i'] ?? null;
        if (is_int($coverI) || (is_string($coverI) && ctype_digit($coverI))) {
            return 'https://covers.openlibrary.org/b/id/'.(int) $coverI.'-M.jpg';
        }

        return null;
    }

    /**
     * @param  array<string, mixed>  $doc
     */
    public static function authorLabelFromSearchDoc(array $doc): ?string
    {
        $name = $doc['author_name'] ?? null;
        if (is_string($name) && $name !== '') {
            return $name;
        }

        if (! is_array($name)) {
            return null;
        }

        $strings = [];
        foreach ($name as $item) {
            if (is_string($item) && $item !== '') {
                $strings[] = $item;
            }
        }

        if ($strings === []) {
            return null;
        }

        return implode(', ', array_unique($strings, SORT_STRING));
    }
}
