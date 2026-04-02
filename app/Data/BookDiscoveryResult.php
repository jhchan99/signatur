<?php

namespace App\Data;

use App\Enums\BookSearchMode;
use App\Models\Work;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

final readonly class BookDiscoveryResult
{
    /**
     * @param  LengthAwarePaginator<int, Work>  $books
     */
    public function __construct(
        public LengthAwarePaginator $books,
        public BookSearchMode $mode,
    ) {}
}
