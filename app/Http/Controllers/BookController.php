<?php

namespace App\Http\Controllers;

use App\Http\Requests\BookIndexRequest;
use App\Models\Book;
use App\Models\ReadingLog;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;

class BookController extends Controller
{
    public function index(BookIndexRequest $request): View
    {
        /** @var array{q?: string|null, subject?: string|null, year?: int|null} $validated */
        $validated = array_merge(
            ['q' => null, 'subject' => null, 'year' => null],
            $request->validated(),
        );

        $searchQuery = filled($validated['q']) ? $validated['q'] : null;
        $subjectFilter = filled($validated['subject']) ? $validated['subject'] : null;
        $yearFilter = $validated['year'] !== null ? (int) $validated['year'] : null;

        $books = Book::query()
            ->when($searchQuery, function (Builder $query) use ($searchQuery): void {
                $query->where(function (Builder $inner) use ($searchQuery): void {
                    $inner
                        ->where('title', 'like', '%'.$searchQuery.'%')
                        ->orWhere('author', 'like', '%'.$searchQuery.'%');
                });
            })
            ->when($subjectFilter, function (Builder $query) use ($subjectFilter): void {
                $query->whereJsonContains('subjects', $subjectFilter);
            })
            ->when($yearFilter, function (Builder $query) use ($yearFilter): void {
                $query->where('publish_year', $yearFilter);
            })
            ->orderBy('title')
            ->paginate(15)
            ->withQueryString();

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
            'books' => $books,
            'filters' => [
                'q' => $searchQuery ?? '',
                'subject' => $subjectFilter ?? '',
                'year' => $yearFilter !== null ? (string) $yearFilter : '',
            ],
            'subjectOptions' => $subjectOptions,
            'yearOptions' => $yearOptions,
        ]);
    }

    public function show(Book $book): View
    {
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
