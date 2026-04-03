<?php

namespace App\Http\Controllers;

use App\Http\Requests\AuthorIndexRequest;
use App\Models\Author;
use Illuminate\Contracts\View\View;

class AuthorController extends Controller
{
    public function index(AuthorIndexRequest $request): View
    {
        /** @var string|null $letter */
        $letter = $request->validated('letter');

        $authors = Author::query()
            ->when($letter === '#', function ($query): void {
                $query->whereRaw("UPPER(SUBSTR(TRIM(COALESCE(name, '')), 1, 1)) NOT BETWEEN 'A' AND 'Z'")
                    ->whereRaw('LENGTH(TRIM(COALESCE(name, \'\'))) > 0');
            })
            ->when(is_string($letter) && strlen($letter) === 1 && $letter >= 'A' && $letter <= 'Z', function ($query) use ($letter): void {
                $query->whereRaw('UPPER(SUBSTR(TRIM(COALESCE(name, \'\')), 1, 1)) = ?', [$letter]);
            })
            ->orderBy('name')
            ->paginate(24)
            ->withQueryString();

        return view('authors.index', [
            'title' => __('Authors'),
            'authors' => $authors,
            'letter' => $letter,
        ]);
    }

    public function show(Author $author): View
    {
        $author->load(['works' => function ($query): void {
            $query->orderBy('title')->with('authors');
        }]);

        return view('authors.show', [
            'title' => $author->name,
            'author' => $author,
        ]);
    }
}
