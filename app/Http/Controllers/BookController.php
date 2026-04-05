<?php

namespace App\Http\Controllers;

use App\Enums\BookSearchMode;
use App\Http\Requests\BookIndexRequest;
use App\Models\ReadingLog;
use App\Models\Work;
use App\Services\Books\BookDiscoveryService;
use App\Services\Books\BookFilterMetadataService;
use Illuminate\Contracts\View\View;

class BookController extends Controller
{
    public function index(BookIndexRequest $request, BookDiscoveryService $discovery, BookFilterMetadataService $filterMetadata): View
    {
        // set default validations
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

        $discoveryResult = $discovery->discover($validated);

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
            'subjectOptions' => $filterMetadata->subjectOptions(),
            'yearOptions' => $filterMetadata->yearOptions(),
        ]);
    }

    public function show(Work $work): View
    {
        $work->load('authors');

        $reviews = ReadingLog::query()
            ->where('work_id', $work->getKey())
            ->where('is_private', false)
            ->whereNotNull('review_text')
            ->where('review_text', '!=', '')
            ->with('user')
            ->latest('updated_at')
            ->get();

        return view('books.show', [
            'book' => $work,
            'reviews' => $reviews,
        ]);
    }
}
