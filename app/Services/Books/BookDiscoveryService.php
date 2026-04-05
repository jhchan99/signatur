<?php

namespace App\Services\Books;

use App\Data\BookDiscoveryResult;
use App\Enums\BookSearchMode;
use App\Models\Work;
use Illuminate\Database\Eloquent\Builder;

class BookDiscoveryService
{
    public function __construct(
        private readonly BookFilterMetadataService $filterMetadata,
    ) {}

    /**
     * @param  array{q?: string|null, subject?: string|null, year?: int|null, mode?: string|null}  $validated
     */
    public function discover(array $validated): BookDiscoveryResult
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

        return new BookDiscoveryResult(
            books: $books,
            mode: $mode,
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
                        $authors
                            ->where('author_works.position', 1)
                            ->where('name', 'like', '%'.$searchQuery.'%');
                    });
                } else {
                    $query->where(function (Builder $inner) use ($searchQuery): void {
                        $inner
                            ->where('title', 'like', '%'.$searchQuery.'%')
                            ->orWhereHas('authors', function (Builder $authors) use ($searchQuery): void {
                                $authors
                                    ->where('author_works.position', 1)
                                    ->where('name', 'like', '%'.$searchQuery.'%');
                            });
                    });
                }
            })
            ->when($subjectFilter !== null, function (Builder $query) use ($subjectFilter): void {
                $subjectsToMatch = $this->filterMetadata
                    ->subjectsForFilter($subjectFilter)
                    ->filter(fn (mixed $subject): bool => is_string($subject) && $subject !== '')
                    ->unique()
                    ->values();

                if ($subjectsToMatch->isEmpty()) {
                    $query->whereRaw('0 = 1');

                    return;
                }

                $query->where(function (Builder $subjectQuery) use ($subjectsToMatch): void {
                    foreach ($subjectsToMatch as $subject) {
                        $subjectQuery->orWhereJsonContains('subjects', $subject);
                    }
                });
            })
            ->when($yearFilter !== null, function (Builder $query) use ($yearFilter): void {
                $query->where('first_publish_year', $yearFilter);
            });
    }
}
