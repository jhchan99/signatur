<?php

namespace App\Services\Books;

use App\Models\Book;
use App\Models\BookFeaturedEntry;
use App\Services\OpenLibrary\OpenLibraryBookSyncService;
use App\Services\OpenLibrary\OpenLibraryService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class FeaturedBooksImporter
{
    public function __construct(
        protected OpenLibraryService $openLibrary,
        protected OpenLibraryBookSyncService $bookSync,
    ) {}

    public function import(): void
    {
        $batch = (string) Str::uuid();
        $importedAt = now();
        $source = (string) config('books.featured.source');
        $listName = config('books.featured.list_name');
        $listName = is_string($listName) ? $listName : null;
        /** @var list<array<string, mixed>>|mixed $seeds */
        $seeds = config('books.featured.seeds', []);

        if (! is_array($seeds)) {
            return;
        }

        DB::transaction(function () use ($batch, $importedAt, $source, $listName, $seeds): void {
            foreach ($seeds as $index => $seed) {
                if (! is_array($seed)) {
                    continue;
                }

                $position = (int) $index + 1;
                $book = $this->importBookFromSeed($seed);
                if ($book === null) {
                    continue;
                }

                BookFeaturedEntry::query()->create([
                    'import_batch' => $batch,
                    'book_id' => $book->id,
                    'position' => $position,
                    'source' => $source,
                    'list_name' => $listName,
                    'payload' => $seed,
                    'imported_at' => $importedAt,
                ]);
            }
        });

        Cache::forget('home.featured_books');
    }

    /**
     * @param  array<string, mixed>  $seed
     */
    protected function importBookFromSeed(array $seed): ?Book
    {
        $workKey = $this->resolveWorkKey($seed);
        if ($workKey === null) {
            return null;
        }

        return $this->bookSync->syncFromWorkKey($workKey);
    }

    /**
     * @param  array<string, mixed>  $seed
     */
    protected function resolveWorkKey(array $seed): ?string
    {
        if (isset($seed['work_key']) && is_string($seed['work_key']) && $seed['work_key'] !== '') {
            return $seed['work_key'];
        }

        $title = $seed['title'] ?? null;
        $author = $seed['author'] ?? null;
        if (! is_string($title) || $title === '' || ! is_string($author) || $author === '') {
            return null;
        }

        $documents = $this->openLibrary->searchDocumentsByTitleAndAuthor($title, $author);
        $first = $documents->first();
        if (! is_array($first)) {
            return null;
        }

        $key = $first['key'] ?? null;
        if (! is_string($key) || ! str_starts_with($key, '/works/')) {
            return null;
        }

        return $key;
    }
}
