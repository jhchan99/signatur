<?php

namespace App\Services\Books;

use App\Data\BookDiscoveryResult;
use App\Data\BookSearchResultItem;
use App\Enums\BookSearchMode;
use App\Jobs\SyncWorkFromOpenLibraryJob;
use App\Models\Work;
use App\Services\OpenLibrary\OpenLibraryService;
use App\Services\OpenLibrary\OpenLibraryWorkSyncService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\RateLimiter;

class BookDiscoveryService
{
    public const int FALLBACK_SYNC_CAP = 10;

    public const int FALLBACK_RATE_LIMIT_MAX = 20;

    public function __construct(
        protected OpenLibraryService $openLibrary,
    ) {}

    /**
     * @param  array{q?: string|null, subject?: string|null, year?: int|null, mode?: string|null}  $validated
     */
    public function discover(array $validated, string $rateLimitKey): BookDiscoveryResult
    {
        $mode = BookSearchMode::tryFrom((string) ($validated['mode'] ?? BookSearchMode::Books->value))
            ?? BookSearchMode::Books;

        $searchQuery = filled($validated['q'] ?? null) ? (string) $validated['q'] : null;
        $subjectFilter = filled($validated['subject'] ?? null) ? (string) $validated['subject'] : null;
        $yearFilter = isset($validated['year']) && $validated['year'] !== null ? (int) $validated['year'] : null;

        $books = $this->buildLocalQuery($mode, $searchQuery, $subjectFilter, $yearFilter)
            ->orderBy('title')
            ->paginate(15)
            ->withQueryString();

        /** @var list<BookSearchResultItem> $openLibraryItems */
        $openLibraryItems = [];
        $usedFallback = false;
        $rateLimited = false;

        if ($searchQuery !== null && $books->total() === 0) {
            if (RateLimiter::tooManyAttempts($rateLimitKey, self::FALLBACK_RATE_LIMIT_MAX)) {
                $rateLimited = true;
            } else {
                RateLimiter::hit($rateLimitKey, 60);
                $documents = $this->fetchOpenLibraryDocuments($mode, $searchQuery);
                $openLibraryItems = $this->normalizeAndDedupeDocuments($documents);
                $this->dispatchSyncJobs($openLibraryItems);
                $usedFallback = true;
            }
        }

        return new BookDiscoveryResult(
            books: $books,
            openLibraryItems: $openLibraryItems,
            mode: $mode,
            usedOpenLibraryFallback: $usedFallback,
            rateLimitedFallback: $rateLimited,
        );
    }

    protected function buildLocalQuery(
        BookSearchMode $mode,
        ?string $searchQuery,
        ?string $subjectFilter,
        ?int $yearFilter,
    ): Builder {
        return Work::query()
            ->with('authors')
            ->when($searchQuery !== null, function (Builder $query) use ($searchQuery, $mode): void {
                if ($mode === BookSearchMode::Author) {
                    $query->whereHas('authors', function (Builder $authors) use ($searchQuery): void {
                        $authors->where('name', 'like', '%'.$searchQuery.'%');
                    });
                } else {
                    $query->where(function (Builder $inner) use ($searchQuery): void {
                        $inner
                            ->where('title', 'like', '%'.$searchQuery.'%')
                            ->orWhereHas('authors', function (Builder $authors) use ($searchQuery): void {
                                $authors->where('name', 'like', '%'.$searchQuery.'%');
                            });
                    });
                }
            })
            ->when($subjectFilter !== null, function (Builder $query) use ($subjectFilter): void {
                $query->whereJsonContains('subjects', $subjectFilter);
            })
            ->when($yearFilter !== null, function (Builder $query) use ($yearFilter): void {
                $query->where('first_publish_year', $yearFilter);
            });
    }

    protected function fetchOpenLibraryDocuments(BookSearchMode $mode, string $q): Collection
    {
        if ($mode === BookSearchMode::Author) {
            $authors = $this->openLibrary->searchAuthorDocuments($q);
            $first = $authors->first();
            if (is_array($first) && isset($first['name']) && is_string($first['name']) && $first['name'] !== '') {
                $works = $this->openLibrary->searchDocumentsWithAuthorField($first['name']);
                if ($works->isNotEmpty()) {
                    return $works;
                }
            }

            return $this->openLibrary->searchDocuments($q);
        }

        return $this->openLibrary->searchDocuments($q);
    }

    /**
     * @param  Collection<int, array<string, mixed>>  $documents
     * @return list<BookSearchResultItem>
     */
    protected function normalizeAndDedupeDocuments(Collection $documents): array
    {
        $normalizedKeys = [];
        foreach ($documents as $doc) {
            if (! is_array($doc)) {
                continue;
            }
            $key = $doc['key'] ?? null;
            if (! is_string($key) || ! str_starts_with($key, '/works/')) {
                continue;
            }
            $normalizedKeys[] = OpenLibraryWorkSyncService::normalizeWorkKey($key);
        }

        $uniqueKeys = array_values(array_unique($normalizedKeys));
        if ($uniqueKeys === []) {
            return [];
        }

        $existing = Work::query()
            ->whereIn('open_library_key', $uniqueKeys)
            ->pluck('open_library_key')
            ->all();

        $existingSet = array_flip($existing);

        $items = [];
        foreach ($documents as $doc) {
            if (! is_array($doc)) {
                continue;
            }
            $item = BookSearchResultItem::fromOpenLibrarySearchDoc($doc);
            if ($item === null) {
                continue;
            }
            if ($item->openLibraryId !== null && isset($existingSet[$item->openLibraryId])) {
                continue;
            }
            $items[] = $item;
        }

        $seen = [];
        $deduped = [];
        foreach ($items as $item) {
            if ($item->openLibraryId === null) {
                continue;
            }
            if (isset($seen[$item->openLibraryId])) {
                continue;
            }
            $seen[$item->openLibraryId] = true;
            $deduped[] = $item;
        }

        return $deduped;
    }

    /**
     * @param  list<BookSearchResultItem>  $items
     */
    protected function dispatchSyncJobs(array $items): void
    {
        $count = 0;
        foreach ($items as $item) {
            if ($item->openLibraryId === null) {
                continue;
            }
            if ($count >= self::FALLBACK_SYNC_CAP) {
                break;
            }
            SyncWorkFromOpenLibraryJob::dispatch($item->openLibraryId);
            $count++;
        }
    }
}
