<?php

namespace App\Http\Controllers;

use App\Models\Book;
use App\Models\ReadingLog;
use Illuminate\Contracts\View\View;

class BookController extends Controller
{
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
