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

    private const string SUBJECT_OPTIONS_CACHE_KEY = 'book_filter_subject_options_v2';

    private const string SUBJECT_BUCKETS_CACHE_KEY = 'book_filter_subject_buckets_v2';

    /**
     * Umbrella subject categories used for high-signal filtering.
     *
     * @var array<string, list<string>>
     */
    private const array SUBJECT_UMBRELLAS = [
        'Mystery & Thriller' => ['mystery', 'thriller', 'crime', 'detective', 'suspense', 'noir'],
        'Fantasy & Sci-Fi' => ['fantasy', 'science fiction', 'sci-fi', 'speculative', 'dystopia', 'magic'],
        'Fiction & Literature' => ['fiction', 'literature', 'novel', 'classic', 'short story', 'drama'],
        'Romance' => ['romance', 'love story', 'chick lit'],
        'History' => ['history', 'historical'],
        'Biography & Memoir' => ['biography', 'memoir', 'autobiography'],
        'Nonfiction & Essays' => ['essay', 'nonfiction', 'journalism', 'reportage'],
        'Politics & Society' => ['politic', 'government', 'society', 'social', 'law', 'civics'],
        'Science & Technology' => ['science', 'technology', 'computer', 'engineering', 'math', 'physics'],
        'Business & Economics' => ['business', 'economics', 'finance', 'leadership', 'management', 'investing'],
        'Philosophy & Religion' => ['philosophy', 'religion', 'spiritual', 'theology'],
        'Psychology & Self-Help' => ['psychology', 'self-help', 'mental health', 'habit', 'productivity'],
        'Kids & Young Adult' => ['children', 'childrens', 'juvenile', 'young adult', 'ya'],
        'Poetry' => ['poetry', 'poem'],
        'Comics & Graphic Novels' => ['comic', 'graphic novel', 'manga'],
        'Art, Film & Music' => ['art', 'film', 'cinema', 'music', 'design', 'photography'],
        'Travel & Adventure' => ['travel', 'adventure'],
    ];

    /**
     * Returns an alphabetically sorted, deduplicated list of all subjects
     * found across the catalog's works.
     *
     * @return Collection<int, string>
     */
    public function subjectOptions(): Collection
    {
        /** @var list<string> $subjectOptions */
        $subjectOptions = Cache::remember(self::SUBJECT_OPTIONS_CACHE_KEY, self::CACHE_TTL, function (): array {
            return array_keys($this->subjectBuckets());
        });

        return collect($subjectOptions);
    }

    /**
     * Resolve a selected filter value to the raw subjects it should match.
     * Falls back to exact matching for backwards-compatible deep links.
     *
     * @return Collection<int, string>
     */
    public function subjectsForFilter(string $selectedFilter): Collection
    {
        $subjectBuckets = $this->subjectBuckets();

        if (array_key_exists($selectedFilter, $subjectBuckets)) {
            return collect($subjectBuckets[$selectedFilter]);
        }

        return collect([$selectedFilter]);
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

    /**
     * Returns present umbrella filters mapped to matching raw subjects.
     *
     * @return array<string, list<string>>
     */
    private function subjectBuckets(): array
    {
        /** @var array<string, list<string>> $subjectBuckets */
        $subjectBuckets = Cache::remember(self::SUBJECT_BUCKETS_CACHE_KEY, self::CACHE_TTL, function (): array {
            /** @var list<string> $subjects */
            $subjects = Work::query()
                ->whereNotNull('subjects')
                ->pluck('subjects')
                ->flatten()
                ->filter(fn (mixed $tag): bool => is_string($tag) && trim($tag) !== '')
                ->map(fn (string $tag): string => trim($tag))
                ->unique()
                ->values()
                ->all();

            /** @var array<string, array<string, bool>> $groups */
            $groups = [];

            foreach ($subjects as $subject) {
                $bucket = $this->bucketForSubject($subject) ?? 'Other';
                $groups[$bucket][$subject] = true;
            }

            $orderedGroups = [];
            foreach (array_keys(self::SUBJECT_UMBRELLAS) as $bucket) {
                if (! isset($groups[$bucket])) {
                    continue;
                }

                $rawSubjects = array_keys($groups[$bucket]);
                sort($rawSubjects);
                $orderedGroups[$bucket] = $rawSubjects;
            }

            if (isset($groups['Other'])) {
                $otherSubjects = array_keys($groups['Other']);
                sort($otherSubjects);
                $orderedGroups['Other'] = $otherSubjects;
            }

            return $orderedGroups;
        });

        return $subjectBuckets;
    }

    private function bucketForSubject(string $subject): ?string
    {
        $normalized = mb_strtolower($subject);

        foreach (self::SUBJECT_UMBRELLAS as $bucket => $keywords) {
            foreach ($keywords as $keyword) {
                if (str_contains($normalized, $keyword)) {
                    return $bucket;
                }
            }
        }

        return null;
    }
}
