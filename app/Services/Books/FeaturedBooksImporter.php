<?php

namespace App\Services\Books;

use App\Models\BookFeaturedEntry;
use App\Models\Work;
use App\Services\OpenLibrary\OpenLibraryService;
use App\Services\OpenLibrary\OpenLibraryWorkSyncService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class FeaturedBooksImporter
{
    public function __construct(
        protected OpenLibraryService $openLibrary,
        protected OpenLibraryWorkSyncService $workSync,
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
                $work = $this->importWorkFromSeed($seed);
                if ($work === null) {
                    continue;
                }

                BookFeaturedEntry::query()->create([
                    'import_batch' => $batch,
                    'work_id' => $work->id,
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
    protected function importWorkFromSeed(array $seed): ?Work
    {
        $workKey = $this->resolveWorkKey($seed);
        if ($workKey === null) {
            return null;
        }

        return $this->workSync->syncFromWorkKey($workKey);
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

        $documents = $this->openLibrary->searchDocuments("{$title} {$author}");
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
