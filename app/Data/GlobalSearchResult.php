<?php

namespace App\Data;

use App\Models\Author;
use App\Models\Work;
use Illuminate\Support\Collection;

final readonly class GlobalSearchResult
{
    /**
     * @param  Collection<int, Work>  $books
     * @param  Collection<int, Author>  $authors
     */
    public function __construct(
        public Collection $books,
        public Collection $authors,
        public ?string $query,
    ) {}

    public function hasAnyResults(): bool
    {
        return $this->books->isNotEmpty() || $this->authors->isNotEmpty();
    }
}
