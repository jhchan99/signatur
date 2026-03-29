<?php

namespace App\Services\OpenLibrary;

use Illuminate\Support\Collection;

final class OpenLibraryBookNormalizer
{
    /**
     * @param  array<string, mixed>  $work
     */
    public static function coverUrlFromWork(array $work): ?string
    {
        $covers = $work['covers'] ?? null;
        if (is_array($covers) && $covers !== []) {
            $first = $covers[0] ?? null;
            if (is_int($first) || (is_string($first) && ctype_digit($first))) {
                $id = (int) $first;

                return 'https://covers.openlibrary.org/b/id/'.$id.'-M.jpg';
            }
        }

        $editionKey = $work['cover_edition_key'] ?? null;
        if (is_string($editionKey) && str_starts_with($editionKey, '/books/')) {
            $olid = basename($editionKey);

            return 'https://covers.openlibrary.org/b/olid/'.$olid.'-M.jpg';
        }

        return null;
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
}
