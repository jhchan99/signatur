<?php

namespace App\Http\Controllers;

use App\Enums\BookSearchMode;
use App\Http\Requests\BookIndexRequest;
use App\Models\Book;
use App\Models\ReadingLog;
use App\Services\Books\BookDiscoveryService;
use Illuminate\Contracts\View\View;

class BookController extends Controller
{
    public function index(BookIndexRequest $request, BookDiscoveryService $discovery): View
    {
        /** @var array{q?: string|null, subject?: string|null, year?: int|null, mode?: string|null} $validated */
        $validated = array_merge(
            [
                'q' => null,
                'subject' => null,
                'year' => null,
                'mode' => BookSearchMode::Books->value,
            ],
            $request->validated(),
        );

        $rateKey = 'openlibrary-fallback:'.($request->user()?->getKey() ?? 'guest').':'.sha1($request->ip());

        $discoveryResult = $discovery->discover($validated, $rateKey);

        $subjectOptions = Book::query()
            ->whereNotNull('subjects')
            ->pluck('subjects')
            ->flatten()
            ->filter(fn ($tag): bool => is_string($tag) && $tag !== '')
            ->unique()
            ->sort()
            ->values();

        $yearOptions = Book::query()
            ->whereNotNull('publish_year')
            ->distinct()
            ->orderByDesc('publish_year')
            ->pluck('publish_year')
            ->values();

        return view('books.index', [
            'title' => __('Books'),
            'books' => $discoveryResult->books,
            'discovery' => $discoveryResult,
            'filters' => [
                'q' => filled($validated['q'] ?? null) ? (string) $validated['q'] : '',
                'subject' => filled($validated['subject'] ?? null) ? (string) $validated['subject'] : '',
                'year' => isset($validated['year']) && $validated['year'] !== null ? (string) (int) $validated['year'] : '',
                'mode' => $discoveryResult->mode->value,
            ],
            'subjectOptions' => $subjectOptions,
            'yearOptions' => $yearOptions,
        ]);
    }

    public function show(Book $book): View
    {
        $book->load('authors');

        $reviews = ReadingLog::query()
            ->where('book_id', $book->getKey())
            ->where('is_private', false)
            ->whereNotNull('review_text')
            ->where('review_text', '!=', '')
            ->with('user')
            ->latest('updated_at')
            ->get();

        return view('books.show', [
            'book' => $book,
            'reviews' => $reviews,
        ]);
    }
}
