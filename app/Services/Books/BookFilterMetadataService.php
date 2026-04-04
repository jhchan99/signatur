<?php

namespace App\Services\Books;

use App\Models\Work;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

class BookFilterMetadataService
{
    /**
     * Cache TTL in seconds (24 hours). Long-lived because the catalog
     * changes infrequently; cache is intentionally not invalidated on every
     * import run — stale-while-revalidate is acceptable for filter dropdowns.
     */
    private const int CACHE_TTL = 86400;

    /**
     * Returns an alphabetically sorted, deduplicated list of all subjects
     * found across the catalog's works.
     *
     * @return Collection<int, string>
     */
    public function subjectOptions(): Collection
    {
        /** @var list<string> $subjects */
        $subjects = Cache::remember('book_filter_subjects', self::CACHE_TTL, function (): array {
            return Work::query()
                ->whereNotNull('subjects')
                ->pluck('subjects')
                ->flatten()
                ->filter(fn (mixed $tag): bool => is_string($tag) && $tag !== '')
                ->unique()
                ->sort()
                ->values()
                ->all();
        });

        return collect($subjects);
    }

    /**
     * Returns distinct publish years, ordered newest-first.
     *
     * @return Collection<int, int>
     */
    public function yearOptions(): Collection
    {
        /** @var list<int> $years */
        $years = Cache::remember('book_filter_years', self::CACHE_TTL, function (): array {
            return Work::query()
                ->whereNotNull('first_publish_year')
                ->distinct()
                ->orderByDesc('first_publish_year')
                ->pluck('first_publish_year')
                ->values()
                ->all();
        });

        return collect($years);
    }
}
