<?php

namespace App\Data;

use App\Enums\BookSearchMode;
use App\Models\Book;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

final readonly class BookDiscoveryResult
{
    /**
     * @param  LengthAwarePaginator<int, Book>  $books
     * @param  list<BookSearchResultItem>  $openLibraryItems
     */
    public function __construct(
        public LengthAwarePaginator $books,
        public array $openLibraryItems,
        public BookSearchMode $mode,
        public bool $usedOpenLibraryFallback,
        public bool $rateLimitedFallback,
    ) {}
}
