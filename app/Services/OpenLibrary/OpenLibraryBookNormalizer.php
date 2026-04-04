<?php

namespace App\Services\OpenLibrary;

use Illuminate\Support\Collection;
use Illuminate\Support\Str;

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
        if (is_int($yearRaw)) {
            return self::yearIntIsPlausible($yearRaw) ? $yearRaw : null;
        }

        if (is_float($yearRaw)) {
            $asInt = (int) round($yearRaw);

            return self::yearIntIsPlausible($asInt) ? $asInt : null;
        }

        if (is_string($yearRaw) && $yearRaw !== '') {
            if (ctype_digit($yearRaw)) {
                $asInt = (int) $yearRaw;

                return self::yearIntIsPlausible($asInt) ? $asInt : null;
            }

            $fromYearField = self::extractFirstPlausibleYearFromString($yearRaw);
            if ($fromYearField !== null) {
                return $fromYearField;
            }
        }

        $dateRaw = $work['first_publish_date'] ?? null;
        if (! is_string($dateRaw) || $dateRaw === '') {
            return null;
        }

        return self::extractFirstPlausibleYearFromString($dateRaw);
    }

    /**
     * First valid 4-digit year in range 1000–2100 from noisy Open Library date text, or null.
     */
    private static function extractFirstPlausibleYearFromString(string $trimmed): ?int
    {
        $trimmed = trim($trimmed);
        if ($trimmed === '') {
            return null;
        }

        if (preg_match_all('/\b(1[0-9]{3}|20[0-9]{2}|2100)\b/', $trimmed, $matches) === 0) {
            return null;
        }

        foreach ($matches[1] as $candidate) {
            $year = (int) $candidate;
            if (self::yearIntIsPlausible($year)) {
                return $year;
            }
        }

        return null;
    }

    protected static function yearIntIsPlausible(int $year): bool
    {
        return $year >= 1000 && $year <= 2100;
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
    public static function workKeyFromSearchDoc(array $doc): ?string
    {
        $key = $doc['key'] ?? null;
        if (! is_string($key)) {
            return null;
        }

        $trimmed = trim($key);
        if (! str_starts_with($trimmed, '/works/')) {
            return null;
        }

        return $trimmed;
    }

    /**
     * @param  array<string, mixed>  $doc
     */
    public static function firstCoverIdFromSearchDoc(array $doc): ?int
    {
        $coverI = $doc['cover_i'] ?? null;
        if (is_int($coverI) || (is_string($coverI) && ctype_digit($coverI))) {
            $id = (int) $coverI;

            return $id > 0 ? $id : null;
        }

        return null;
    }

    /**
     * @param  array<string, mixed>  $doc
     */
    public static function firstPublishYearFromSearchDoc(array $doc): ?int
    {
        $year = $doc['first_publish_year'] ?? null;
        if (is_int($year)) {
            return self::yearIntIsPlausible($year) ? $year : null;
        }
        if (is_string($year) && ctype_digit($year)) {
            $asInt = (int) $year;

            return self::yearIntIsPlausible($asInt) ? $asInt : null;
        }

        return null;
    }

    /**
     * @param  array<string, mixed>  $doc
     */
    public static function firstAuthorKeyFromSearchDoc(array $doc): ?string
    {
        $keys = $doc['author_key'] ?? null;
        if (! is_array($keys) || $keys === []) {
            return null;
        }

        $first = $keys[0] ?? null;
        if (! is_string($first) || trim($first) === '') {
            return null;
        }

        $normalized = trim($first);
        if (str_starts_with($normalized, '/authors/')) {
            return $normalized;
        }
        if (str_starts_with($normalized, 'OL')) {
            return '/authors/'.$normalized;
        }

        return null;
    }

    /**
     * @param  array<string, mixed>  $doc
     */
    public static function firstAuthorNameFromSearchDoc(array $doc): ?string
    {
        $names = $doc['author_name'] ?? null;
        if (is_string($names) && trim($names) !== '') {
            return trim($names);
        }
        if (! is_array($names) || $names === []) {
            return null;
        }

        $first = $names[0] ?? null;

        return is_string($first) && trim($first) !== '' ? trim($first) : null;
    }

    /**
     * @param  array<string, mixed>  $doc
     */
    public static function searchDocLooksLikeNonBook(array $doc): bool
    {
        $title = $doc['title'] ?? null;
        if (! is_string($title) || trim($title) === '') {
            return true;
        }

        $normalizedTitle = self::normalizeTextForMatching($title);
        $blockedPhrases = [
            'study guide',
            'companion',
            'summary',
            'handbook',
            'workbook',
            'boxed set',
            'box set',
            'collection',
            'saga',
            'omnibus',
            'sampler',
            'board game',
            'card game',
            'game',
            'rpg',
            'roleplaying',
        ];

        foreach ($blockedPhrases as $phrase) {
            if (str_contains($normalizedTitle, $phrase)) {
                return true;
            }
        }

        return false;
    }

    public static function normalizeTextForMatching(string $value): string
    {
        $lower = mb_strtolower($value);
        $ascii = Str::ascii($lower);
        $collapsed = preg_replace('/[^a-z0-9]+/i', ' ', $ascii);
        if (! is_string($collapsed)) {
            return '';
        }

        return trim(preg_replace('/\s+/', ' ', $collapsed) ?? '');
    }

    /**
     * @param  array<string, mixed>  $doc
     */
    public static function authorKeyFromAuthorSearchDoc(array $doc): ?string
    {
        $key = $doc['key'] ?? null;
        if (! is_string($key) || trim($key) === '') {
            return null;
        }

        $trimmed = trim($key);
        if (str_starts_with($trimmed, '/authors/')) {
            return $trimmed;
        }
        if (str_starts_with($trimmed, 'OL')) {
            return '/authors/'.$trimmed;
        }

        return null;
    }

    /**
     * @param  array<string, mixed>  $doc
     */
    public static function authorNameFromAuthorSearchDoc(array $doc): ?string
    {
        $name = $doc['name'] ?? null;
        if (! is_string($name)) {
            return null;
        }

        $trimmed = trim($name);

        return $trimmed !== '' ? $trimmed : null;
    }

    /**
     * @param  array<string, mixed>  $doc
     */
    public static function authorWorkCountFromAuthorSearchDoc(array $doc): int
    {
        $count = $doc['work_count'] ?? 0;
        if (is_int($count) && $count >= 0) {
            return $count;
        }
        if (is_string($count) && ctype_digit($count)) {
            return (int) $count;
        }

        return 0;
    }

    /**
     * @param  array<string, mixed>  $doc
     */
    public static function coverUrlFromSearchDoc(array $doc): ?string
    {
        return self::coverUrlFromCoverId(self::firstCoverIdFromSearchDoc($doc), 'M');
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
