<?php

namespace App\Http\Controllers;

use App\Models\Author;
use Illuminate\Contracts\View\View;

class AuthorController extends Controller
{
    public function index(): View
    {
        $authors = Author::query()
            ->orderBy('name')
            ->paginate(24)
            ->withQueryString();

        return view('authors.index', [
            'title' => __('Authors'),
            'authors' => $authors,
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
