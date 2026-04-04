<?php

namespace App\Services\Books;

use App\Models\Author;
use App\Models\Work;
use App\Services\OpenLibrary\OpenLibraryBookNormalizer;
use App\Services\OpenLibrary\OpenLibraryService;
use Illuminate\Support\Collection;

class WorkEnrichmentService
{
    public function __construct(
        protected OpenLibraryService $openLibrary,
    ) {}

    /**
     * @return array{status: string, reason?: string, work_id?: int}
     */
    public function enrichWorkById(int $workId): array
    {
        $work = Work::query()->with('authors')->find($workId);
        if ($work === null) {
            return [
                'status' => 'missing',
                'reason' => 'work_not_found',
            ];
        }

        return $this->enrichWork($work);
    }

    /**
     * @return array{status: string, reason?: string, work_id: int}
     */
    public function enrichWork(Work $work): array
    {
        $work->loadMissing('authors');

        $title = trim((string) $work->title);
        if ($title === '') {
            return [
                'status' => 'skipped',
                'reason' => 'missing_title',
                'work_id' => (int) $work->getKey(),
            ];
        }

        $primaryAuthorName = $this->primaryAuthorName($work);
        $query = $primaryAuthorName !== null ? "{$title} {$primaryAuthorName}" : $title;
        $docs = $this->openLibrary->searchDocuments($query, 25);

        $bestDoc = $this->selectBestWorkDocument($docs);

        if ($bestDoc === null) {
            return [
                'status' => 'skipped',
                'reason' => 'no_confident_match',
                'work_id' => (int) $work->getKey(),
            ];
        }

        $work->forceFill([
            'open_library_key' => OpenLibraryBookNormalizer::workKeyFromSearchDoc($bestDoc) ?? $work->open_library_key,
            'cover_id' => OpenLibraryBookNormalizer::firstCoverIdFromSearchDoc($bestDoc) ?? $work->cover_id,
            'first_publish_year' => $work->first_publish_year ?: OpenLibraryBookNormalizer::firstPublishYearFromSearchDoc($bestDoc),
            'open_library_search_doc' => $bestDoc,
            'open_library_match_source' => 'search.json',
            'open_library_enriched_at' => now(),
        ])->save();

        $primaryAuthor = $work->authors->first();
        if ($primaryAuthor instanceof Author) {
            $this->enrichAuthor($primaryAuthor, $bestDoc);
        }

        return [
            'status' => 'enriched',
            'work_id' => (int) $work->getKey(),
        ];
    }

    /**
     * @param  array<string, mixed>  $matchedWorkDoc
     */
    public function enrichAuthor(Author $author, array $matchedWorkDoc): bool
    {
        $candidateKey = OpenLibraryBookNormalizer::firstAuthorKeyFromSearchDoc($matchedWorkDoc);
        $candidateName = OpenLibraryBookNormalizer::firstAuthorNameFromSearchDoc($matchedWorkDoc);
        $authorName = trim((string) $author->name);

        $authorSearchDoc = $authorName !== ''
            ? $this->selectBestAuthorDocument(
                $this->openLibrary->searchAuthorsByName($authorName, 10),
                $authorName,
            )
            : null;

        $resolvedKey = OpenLibraryBookNormalizer::authorKeyFromAuthorSearchDoc($authorSearchDoc ?? []);

        if ($resolvedKey === null && $candidateKey !== null && $candidateName !== null && $this->authorNamesAreConfidentMatch($author->name, $candidateName)) {
            $resolvedKey = $candidateKey;
        }

        if ($resolvedKey === null) {
            return false;
        }

        $author->forceFill([
            'open_library_id' => $resolvedKey,
            'open_library_author_search_doc' => $authorSearchDoc,
            'open_library_author_enriched_at' => now(),
        ])->save();

        return true;
    }

    /**
     * @param  Collection<int, array<string, mixed>>  $docs
     * @return array<string, mixed>|null
     */
    protected function selectBestWorkDocument(Collection $docs): ?array
    {
        $first = $docs->first();
        if (! is_array($first)) {
            return null;
        }

        if (OpenLibraryBookNormalizer::workKeyFromSearchDoc($first) === null) {
            return null;
        }

        return $first;
    }

    /**
     * @param  Collection<int, array<string, mixed>>  $docs
     * @return array<string, mixed>|null
     */
    protected function selectBestAuthorDocument(Collection $docs, string $localAuthorName): ?array
    {
        $ranked = $docs
            ->map(function (array $doc) use ($localAuthorName): array {
                $score = 0;
                $docKey = OpenLibraryBookNormalizer::authorKeyFromAuthorSearchDoc($doc);
                $docName = OpenLibraryBookNormalizer::authorNameFromAuthorSearchDoc($doc);

                if ($docKey === null || $docName === null) {
                    return ['doc' => $doc, 'score' => -100];
                }

                if ($this->authorNamesAreConfidentMatch($localAuthorName, $docName)) {
                    $score += 35;
                }

                $score += min(20, OpenLibraryBookNormalizer::authorWorkCountFromAuthorSearchDoc($doc));

                return ['doc' => $doc, 'score' => $score];
            })
            ->filter(fn (array $row): bool => $row['score'] >= 45)
            ->sortByDesc('score')
            ->values();

        if ($ranked->isEmpty()) {
            return null;
        }

        $best = $ranked->first();

        return is_array($best['doc'] ?? null) ? $best['doc'] : null;
    }

    protected function primaryAuthorName(Work $work): ?string
    {
        $primary = $work->authors->first();
        if (! $primary instanceof Author) {
            return null;
        }

        $name = trim((string) $primary->name);

        return $name !== '' ? $name : null;
    }

    protected function authorNamesAreConfidentMatch(string $left, string $right): bool
    {
        $normalizedLeft = OpenLibraryBookNormalizer::normalizeTextForMatching($left);
        $normalizedRight = OpenLibraryBookNormalizer::normalizeTextForMatching($right);

        if ($normalizedLeft === '' || $normalizedRight === '') {
            return false;
        }

        return $normalizedLeft === $normalizedRight
            || $this->containsAllWords($normalizedLeft, $normalizedRight)
            || $this->containsAllWords($normalizedRight, $normalizedLeft);
    }

    protected function containsAllWords(string $haystack, string $needle): bool
    {
        $words = array_filter(explode(' ', $needle), fn (string $word): bool => $word !== '');
        if ($words === []) {
            return false;
        }

        foreach ($words as $word) {
            if (! str_contains($haystack, $word)) {
                return false;
            }
        }

        return true;
    }
}
