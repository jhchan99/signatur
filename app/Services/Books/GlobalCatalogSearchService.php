<?php

namespace App\Services\Books;

use App\Data\GlobalSearchResult;
use App\Models\Author;
use App\Models\Work;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

class GlobalCatalogSearchService
{
    public const int RESULT_LIMIT = 15;

    public function search(?string $query): GlobalSearchResult
    {
        if ($query === null || trim($query) === '') {
            return new GlobalSearchResult(
                books: collect(),
                authors: collect(),
                query: $query,
            );
        }

        $trimmed = trim($query);
        $needle = mb_strtolower($trimmed);

        $books = $this->matchingWorks($needle);
        $authors = $this->matchingAuthors($needle);

        return new GlobalSearchResult(
            books: $books,
            authors: $authors,
            query: $trimmed,
        );
    }

    /**
     * Case-insensitive matching on title, subtitle, subjects (JSON), and linked author names.
     * Relevance: exact title, title prefix, title substring, subtitle, author name, subjects.
     *
     * @return Collection<int, Work>
     */
    protected function matchingWorks(string $needle): Collection
    {
        $prefix = $needle.'%';
        $like = '%'.$needle.'%';

        $rankBindings = [
            $needle,
            $prefix,
            $like,
            $like,
            $like,
            $like,
        ];

        $subjectsLower = $this->lowerWorksSubjectsSql();

        // Ordered strict-to-loose so the first matching arm wins (SQLite has no LEAST() in all builds).
        $rankSql = <<<SQL
            CASE
              WHEN LOWER(works.title) = ? THEN 0
              WHEN LOWER(works.title) LIKE ? THEN 10
              WHEN LOWER(works.title) LIKE ? THEN 20
              WHEN LOWER(COALESCE(works.subtitle, '')) LIKE ? THEN 30
              WHEN EXISTS (
                SELECT 1 FROM author_works
                INNER JOIN authors ON authors.id = author_works.author_id
                WHERE author_works.work_id = works.id
                AND LOWER(authors.name) LIKE ?
              ) THEN 40
              WHEN {$subjectsLower} LIKE ? THEN 50
              ELSE 100
            END AS search_rank
            SQL;

        return Work::query()
            ->select('works.*')
            ->selectRaw($rankSql, $rankBindings)
            ->with('authors')
            ->where(function (Builder $inner) use ($like, $subjectsLower): void {
                $inner
                    ->whereRaw('LOWER(works.title) LIKE ?', [$like])
                    ->orWhereRaw('LOWER(COALESCE(works.subtitle, \'\')) LIKE ?', [$like])
                    ->orWhereRaw("{$subjectsLower} LIKE ?", [$like])
                    ->orWhereHas('authors', function (Builder $authors) use ($like): void {
                        $authors->whereRaw('LOWER(authors.name) LIKE ?', [$like]);
                    });
            })
            ->orderBy('search_rank')
            ->orderBy('works.title')
            ->limit(self::RESULT_LIMIT)
            ->get();
    }

    /**
     * Match primary name, or any serialized alternate name substring in the JSON column.
     *
     * @return Collection<int, Author>
     */
    protected function matchingAuthors(string $needle): Collection
    {
        $like = '%'.$needle.'%';
        $alternatesLower = $this->lowerAuthorsAlternateNamesSql();

        return Author::query()
            ->where(function (Builder $inner) use ($like, $alternatesLower): void {
                $inner
                    ->whereRaw('LOWER(name) LIKE ?', [$like])
                    ->orWhereRaw("{$alternatesLower} LIKE ?", [$like]);
            })
            ->orderBy('name')
            ->limit(self::RESULT_LIMIT)
            ->get();
    }

    /**
     * Lowercase JSON/text for `works.subjects` LIKE matching.
     * Production: PostgreSQL. Tests: SQLite (default branch).
     */
    protected function lowerWorksSubjectsSql(): string
    {
        return match (DB::connection()->getDriverName()) {
            'pgsql' => 'LOWER(COALESCE(works.subjects::text, \'\'))',
            default => 'LOWER(COALESCE(CAST(works.subjects AS TEXT), \'\'))',
        };
    }

    /**
     * Lowercase JSON/text for `authors.alternate_names` LIKE matching.
     * Production: PostgreSQL. Tests: SQLite (default branch).
     */
    protected function lowerAuthorsAlternateNamesSql(): string
    {
        return match (DB::connection()->getDriverName()) {
            'pgsql' => 'LOWER(COALESCE(alternate_names::text, \'\'))',
            default => 'LOWER(COALESCE(CAST(alternate_names AS TEXT), \'\'))',
        };
    }
}
