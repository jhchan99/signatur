<?php

namespace App\Services\Books;

use App\Models\Book;
use App\Models\BookFeaturedEntry;
use App\Services\OpenLibrary\OpenLibraryBookNormalizer;
use App\Services\OpenLibrary\OpenLibraryService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class FeaturedBooksImporter
{
    public function __construct(
        protected OpenLibraryService $openLibrary,
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

        $work = $this->openLibrary->getWork($workKey);
        if ($work === []) {
            return null;
        }

        $authorNames = $this->resolveAuthorNames($work);
        $normalizedKey = $this->normalizeWorkKey($workKey);
        $title = $work['title'] ?? null;
        $title = is_string($title) && $title !== '' ? $title : 'Unknown title';

        $yearRaw = $work['first_publish_year'] ?? null;
        $year = is_numeric($yearRaw) ? (int) $yearRaw : null;

        return Book::query()->updateOrCreate(
            ['open_library_id' => $normalizedKey],
            [
                'title' => $title,
                'author' => $authorNames,
                'cover_url' => OpenLibraryBookNormalizer::coverUrlFromWork($work),
                'publish_year' => $year,
                'description' => OpenLibraryBookNormalizer::description($work['description'] ?? null),
            ],
        );
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

    protected function normalizeWorkKey(string $workKey): string
    {
        $trimmed = trim($workKey);

        if (str_starts_with($trimmed, '/works/')) {
            return $trimmed;
        }

        if (str_starts_with($trimmed, 'works/')) {
            return '/'.$trimmed;
        }

        return '/works/'.ltrim($trimmed, '/');
    }

    /**
     * @param  array<string, mixed>  $work
     */
    protected function resolveAuthorNames(array $work): ?string
    {
        $keys = OpenLibraryBookNormalizer::authorKeysFromWork($work)->take(5);
        if ($keys->isEmpty()) {
            return null;
        }

        $names = [];
        foreach ($keys as $authorKey) {
            $payload = $this->openLibrary->getAuthor($authorKey);
            if ($payload !== [] && isset($payload['name']) && is_string($payload['name'])) {
                $names[] = $payload['name'];
            }
        }

        if ($names === []) {
            return null;
        }

        return implode(', ', $names);
    }
}
