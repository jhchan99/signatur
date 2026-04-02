<?php

namespace App\Services\Books;

use App\Data\GlobalSearchResult;
use App\Models\Author;
use App\Models\Work;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

class GlobalCatalogSearchService
{
    public const int RESULT_LIMIT = 15;

    public function search(?string $query): GlobalSearchResult
    {
        if ($query === null || $query === '') {
            return new GlobalSearchResult(
                books: collect(),
                authors: collect(),
                query: $query,
            );
        }

        $pattern = '%'.$query.'%';

        $books = $this->matchingWorks($pattern);
        $authors = $this->matchingAuthors($pattern);

        return new GlobalSearchResult(
            books: $books,
            authors: $authors,
            query: $query,
        );
    }

    /**
     * @return Collection<int, Work>
     */
    protected function matchingWorks(string $pattern)
    {
        return Work::query()
            ->with('authors')
            ->where(function (Builder $inner) use ($pattern): void {
                $inner
                    ->where('title', 'like', $pattern)
                    ->orWhereHas('authors', function (Builder $authors) use ($pattern): void {
                        $authors->where('name', 'like', $pattern);
                    });
            })
            ->orderBy('title')
            ->limit(self::RESULT_LIMIT)
            ->get();
    }

    /**
     * Match primary name, or any serialized alternate name substring in the JSON column.
     *
     * @return Collection<int, Author>
     */
    protected function matchingAuthors(string $pattern)
    {
        return Author::query()
            ->where(function (Builder $inner) use ($pattern): void {
                $inner
                    ->where('name', 'like', $pattern)
                    ->orWhere('alternate_names', 'like', $pattern);
            })
            ->orderBy('name')
            ->limit(self::RESULT_LIMIT)
            ->get();
    }
}
